<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Referral commission. Called after any successful spend (a number bought, an
 * account bought). It tracks the user's cumulative spend and, once they cross
 * the admin-set threshold, pays their referrer a one-time commission —
 * percentage or flat, whichever the admin configured.
 *
 * Settings (live from the settings table):
 *   referral.enabled    bool
 *   referral.threshold  float   spend required before commission fires
 *   referral.mode       percent|flat
 *   referral.value      float   percent (e.g. 2) or a flat amount
 */
class Referral
{
    public static function recordSpend(int $userId, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        if (! self::setting('referral.enabled', true)) {
            DB::table('users')->where('id', $userId)->increment('total_spent', $amount);
            return;
        }

        DB::transaction(function () use ($userId, $amount) {
            $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            if (! $user) {
                return;
            }

            $newSpent = round((float) $user->total_spent + $amount, 2);
            DB::table('users')->where('id', $userId)->update(['total_spent' => $newSpent]);

            if ($user->referral_paid || ! $user->referred_by) {
                return;
            }

            $threshold = (float) self::setting('referral.threshold', 20000);
            if ($newSpent < $threshold) {
                return;
            }

            $mode  = (string) self::setting('referral.mode', 'percent');
            $value = (float) self::setting('referral.value', 2);
            $commission = $mode === 'flat'
                ? $value
                : round($threshold * $value / 100, 2);

            if ($commission <= 0) {
                return;
            }

            // Mark paid FIRST, inside the lock, so it fires exactly once.
            DB::table('users')->where('id', $userId)->update(['referral_paid' => true]);

            $refId   = (int) $user->referred_by;
            $balance = (float) DB::table('users')->where('id', $refId)->lockForUpdate()->value('wallet_balance');
            $after   = round($balance + $commission, 2);

            DB::table('users')->where('id', $refId)->update(['wallet_balance' => $after]);
            DB::table('users')->where('id', $refId)->increment('affiliate_earned', $commission);

            DB::table('wallet_transactions')->insert([
                'user_id'       => $refId,
                'reference'     => 'REF' . strtoupper(Str::random(9)),
                'type'          => 'adjustment',
                'status'        => 'settled',
                'amount'        => $commission,
                'balance_after' => $after,
                'currency'      => (string) self::setting('numbers.currency.code', 'NGN'),
                'note'          => 'Referral commission',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            if (DB::getSchemaBuilder()->hasTable('affiliate_conversions')) {
                DB::table('affiliate_conversions')->insert([
                    'user_id'          => $refId,
                    'referred_user_id' => $userId,
                    'type'             => 'spend',
                    'amount'           => $newSpent,
                    'commission'       => $commission,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        });
    }

    private static function setting(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember('settings', 300, function () {
            try {
                return DB::table('settings')->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                        'int' => (int) $r->value, 'float' => (float) $r->value,
                        'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                    }])->all();
            } catch (\Throwable) { return []; }
        });

        return $all[$key] ?? $default;
    }
}
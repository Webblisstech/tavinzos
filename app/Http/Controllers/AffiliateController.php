<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The affiliate dashboard: referral link, visit/conversion/earnings stats,
 * tier + commission rate, and recent activity.
 */
class AffiliateController extends Controller
{
    /** Commission tiers by lifetime earnings (₦). Rate is a percentage. */
    private const TIERS = [
        ['name' => 'Bronze',   'min' => 0,       'rate' => 5],
        ['name' => 'Silver',   'min' => 50000,   'rate' => 7],
        ['name' => 'Gold',     'min' => 250000,  'rate' => 10],
        ['name' => 'Platinum', 'min' => 1000000, 'rate' => 12],
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        // Give the user a referral code on first visit.
        if (! $user->ref_code) {
            $code = $this->makeCode();
            DB::table('users')->where('id', $user->id)->update(['ref_code' => $code]);
            $user->ref_code = $code;
        }

        $uid   = $user->id;
        $today = now()->startOfDay();
        $d7    = now()->subDays(7);
        $d30   = now()->subDays(30);

        $earned = (float) $user->affiliate_earned;
        $tier   = $this->tierFor($earned);

        // The affiliate feature spans three tables. If a migration hasn't run
        // (e.g. affiliate_conversions is missing), degrade to zeros instead of
        // white-screening the whole page.
        $hasVisits = DB::getSchemaBuilder()->hasTable('affiliate_visits');
        $hasConvs  = DB::getSchemaBuilder()->hasTable('affiliate_conversions');

        $visits = $hasVisits ? DB::table('affiliate_visits')->where('user_id', $uid) : null;
        $convs  = $hasConvs  ? DB::table('affiliate_conversions')->where('user_id', $uid) : null;

        $stats = [
            'visits'       => $visits ? (clone $visits)->count() : 0,
            'unique'       => $visits ? (clone $visits)->distinct('visitor')->count('visitor') : 0,
            'conversions'  => $convs ? (clone $convs)->count() : 0,
            'referred'     => DB::table('users')->where('referred_by', $uid)->count(),
            'earned'       => $this->money($earned),
            'ltv'          => $this->money($convs ? (float) (clone $convs)->sum('amount') : 0),
        ];

        // Conversion rate: conversions ÷ visits.
        $stats['rate'] = $stats['visits']
            ? round($stats['conversions'] / $stats['visits'] * 100, 1)
            : 0.0;

        $window = fn ($since) => [
            'visits'      => $visits ? (clone $visits)->where('created_at', '>=', $since)->count() : 0,
            'conversions' => $convs ? (clone $convs)->where('created_at', '>=', $since)->count() : 0,
            'earned'      => $this->money($convs ? (float) (clone $convs)->where('created_at', '>=', $since)->sum('commission') : 0),
        ];

        return view('affiliates.index', [
            'refLink'   => rtrim(config('app.url'), '/') . '/?ref=' . $user->ref_code,
            'refCode'   => $user->ref_code,
            'tier'      => $tier,
            'nextTier'  => $this->nextTier($earned),
            'stats'     => $stats,
            'today'     => $window($today),
            'last7'     => $window($d7),
            'last30'    => $window($d30),
            'recent'    => $hasConvs ? $this->recentConversions($uid) : collect(),
            'referrals' => $this->recentReferrals($uid),
        ]);
    }

    private function recentConversions(int $uid)
    {
        return DB::table('affiliate_conversions')
            ->leftJoin('users', 'users.id', '=', 'affiliate_conversions.referred_user_id')
            ->where('affiliate_conversions.user_id', $uid)
            ->orderByDesc('affiliate_conversions.id')
            ->limit(10)
            ->get([
                'affiliate_conversions.*',
                'users.name AS user_name',
            ])
            ->map(fn ($c) => [
                'at'         => $c->created_at,
                'type'       => $c->type,
                'user'       => $c->user_name ? Str::of($c->user_name)->before(' ') . ' ' . Str::substr($c->user_name, -1) : 'User',
                'amount'     => $this->money((float) $c->amount),
                'commission' => $this->money((float) $c->commission),
            ]);
    }

    private function recentReferrals(int $uid)
    {
        return DB::table('users')
            ->where('referred_by', $uid)
            ->orderByDesc('id')
            ->limit(8)
            ->get(['name', 'created_at'])
            ->map(fn ($u) => [
                // Privacy: first name + last initial only.
                'name' => $u->name ? Str::of($u->name)->before(' ') . ' ' . Str::substr(Str::afterLast($u->name, ' '), 0, 1) . '.' : 'User',
                'at'   => $u->created_at,
            ]);
    }

    private function tierFor(float $earned): array
    {
        $tier = self::TIERS[0];
        foreach (self::TIERS as $t) {
            if ($earned >= $t['min']) $tier = $t;
        }
        return $tier;
    }

    private function nextTier(float $earned): ?array
    {
        foreach (self::TIERS as $t) {
            if ($earned < $t['min']) {
                return $t + ['remaining' => $this->money($t['min'] - $earned)];
            }
        }
        return null; // already at top
    }

    private function makeCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (DB::table('users')->where('ref_code', $code)->exists());
        return $code;
    }

    // ── money helpers (same pattern as other controllers) ────────────
    private ?array $settingsCache = null;
    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                            'int' => (int) $r->value, 'float' => (float) $r->value,
                            'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                        }])->all();
                } catch (\Throwable) { return []; }
            });
        }
        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }
    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol')
            . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
    }
}
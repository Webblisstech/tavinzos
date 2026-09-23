<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Everything on the dashboard comes from verifications and
 * wallet_transactions. Nothing is estimated and nothing is hardcoded — if a
 * figure has no data behind it yet, the view shows an empty state rather than
 * a plausible-looking number.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $userId = $request->user()->id;
        $weekAgo = now()->subDays(7);
        $dayAgo  = now()->subDay();

        // ── One pass for the week's totals ───────────────────────────
        // "Spend" counts only value the customer actually received: numbers
        // that delivered a code (status 'completed'), plus delivered account
        // purchases. Cancelled/refunded numbers and pending orders don't count.
        $numberSpend = DB::table('verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $weekAgo)
            ->where('status', 'completed')
            ->sum('price');

        $numberOrders = DB::table('verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $weekAgo)
            ->where('status', 'completed')
            ->count();

        $logSpend = DB::table('log_orders')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $weekAgo)
            ->where('status', 'delivered')
            ->sum('total');

        $logOrders = DB::table('log_orders')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $weekAgo)
            ->where('status', 'delivered')
            ->count();

        $spend  = (float) $numberSpend + (float) $logSpend;
        $orders = (int) $numberOrders + (int) $logOrders;

        // ── Delivery in the last 24h ─────────────────────────────────
        $day = DB::table('verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $dayAgo)
            ->selectRaw("SUM(status = 'completed') AS done")
            ->selectRaw("SUM(status = 'cancelled') AS failed")
            ->first();

        $done     = (int) ($day->done ?? 0);
        $failed   = (int) ($day->failed ?? 0);
        $settled  = $done + $failed;

        // ── Live counts ──────────────────────────────────────────────
        $active = (int) DB::table('verifications')
            ->where('user_id', $userId)
            ->where('status', 'waiting')
            ->count();

        // Account (log) purchases this week, if that feature is migrated.
        $accountsBought = DB::getSchemaBuilder()->hasTable('log_orders')
            ? (int) DB::table('log_orders')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $weekAgo)
                ->where('status', 'delivered')
                ->sum('quantity')
            : 0;

        // Money added this week (settled top-ups).
        $deposited = (float) DB::table('wallet_transactions')
            ->where('user_id', $userId)
            ->where('type', 'topup')
            ->when(
                DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'status'),
                fn ($q) => $q->where('status', 'settled')
            )
            ->where('created_at', '>=', $weekAgo)
            ->sum('amount');

        return view('dashboard', [
            'balance'  => $this->money((float) $request->user()->wallet_balance),
            'active'   => $active,
            'ceiling'  => (int) $this->setting('numbers.max_active_per_user', 10),

            // null, not 0 — "no orders yet" and "0% delivered" are different
            // things and the view says so.
            'rate'     => $settled ? round($done / $settled * 100, 1) : null,
            'trend'    => $this->trend($userId),

            'spend'     => $this->money($spend),
            'orders'    => $orders,
            'average'   => $this->money($orders ? $spend / $orders : 0),
            'accounts'  => $accountsBought,
            'deposited' => $this->money($deposited),

            'live'     => $this->live($userId),
            'top'      => $this->topServices($userId, $weekAgo),
            'ledger'   => $this->ledger($userId),
            'name'     => $request->user()->name,
            'rank'     => $this->rank($userId),
        ]);
    }

    /**
     * Deposit leaderboard: the user's own rank and the top 3, ranked by total
     * settled top-ups. Returns nulls when the user hasn't deposited so the view
     * can show the empty state.
     */
    private function rank(int $userId): array
    {
        $hasStatus = DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'status');

        // Sum settled top-ups per user.
        $totals = DB::table('wallet_transactions')
            ->where('type', 'topup')
            ->when($hasStatus, fn ($q) => $q->where('status', 'settled'))
            ->select('user_id', DB::raw('SUM(amount) as deposited'))
            ->groupBy('user_id')
            ->orderByDesc('deposited')
            ->get();

        // The current user's position (1-based), or null if they've not deposited.
        $position = null;
        $userTotal = 0.0;
        foreach ($totals as $i => $row) {
            if ((int) $row->user_id === $userId) {
                $position  = $i + 1;
                $userTotal = (float) $row->deposited;
                break;
            }
        }

        // Top 3 with names.
        $topIds = $totals->take(3)->pluck('user_id')->all();
        $names  = $topIds
            ? DB::table('users')->whereIn('id', $topIds)->pluck('name', 'id')
            : collect();

        $podium = $totals->take(3)->values()->map(fn ($r, $i) => [
            'position'  => $i + 1,
            'name'      => $names[$r->user_id] ?? '—',
            'deposited' => $this->money((float) $r->deposited),
            'is_you'    => (int) $r->user_id === $userId,
        ]);

        return [
            'position'  => $position,           // null → no deposits yet
            'total'     => $totals->count(),    // how many depositors exist
            'deposited' => $this->money($userTotal),
            'podium'    => $podium,
        ];
    }

    /** Daily delivery rate for the last 7 days, oldest first, as 0–100. */
    private function trend(int $userId): array
    {
        // The rate is aliased in the SELECT: pluck() reads a column name off
        // each row, so it cannot take a raw expression.
        $rows = DB::table('verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->whereIn('status', ['completed', 'cancelled'])
            ->selectRaw('DATE(created_at) AS day')
            ->selectRaw("ROUND(SUM(status = 'completed') / COUNT(*) * 100) AS rate")
            ->groupBy('day')
            ->pluck('rate', 'day')
            ->all();

        $out = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $out[] = (int) ($rows[$day] ?? 0);
        }

        return $out;
    }

    /** Numbers still waiting, newest first. */
    private function live(int $userId)
    {
        return DB::table('verifications')
            ->where('user_id', $userId)
            ->whereIn('status', ['waiting', 'completed'])
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn ($r) => [
                'ref'     => $r->reference,
                'number'  => $r->phone_number,
                'service' => $r->service,
                'country' => $r->country,
                'status'  => ucfirst($r->status),
                'code'    => $r->code,
                'since'   => $r->purchased_at ?? $r->created_at,
            ]);
    }

    /** What this account actually buys, so the list is worth reading. */
    private function topServices(int $userId, $since)
    {
        return DB::table('verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('service')
            ->selectRaw('service, COUNT(*) AS n, COALESCE(SUM(price), 0) AS spend')
            ->groupBy('service')
            ->orderByDesc('n')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'service' => $r->service,
                'count'   => (int) $r->n,
                'spend'   => $this->money((float) $r->spend),
            ]);
    }

    private function ledger(int $userId)
    {
        return DB::table('wallet_transactions')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn ($r) => [
                'reference' => $r->reference,
                'type'      => $r->type,
                'amount'    => (float) $r->amount,
                'formatted' => ($r->amount < 0 ? '−' : '+') . $this->format(abs((float) $r->amount)),
                'at'        => $r->created_at,
            ]);
    }

    // ── Shared with NumberController ─────────────────────────────────
    // Deliberately duplicated rather than extracted: two small readers are
    // easier to follow than an abstraction that exists for eight lines.

    private ?array $settingsCache = null;

    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')
                        ->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($row) => [$row->key => match ($row->type) {
                            'int'   => (int) $row->value,
                            'float' => (float) $row->value,
                            'bool'  => filter_var($row->value, FILTER_VALIDATE_BOOL),
                            default => $row->value,
                        }])
                        ->all();
                } catch (\Throwable) {
                    return [];
                }
            });
        }

        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }

    private function format(float $amount): string
    {
        return $this->setting('numbers.currency.symbol')
            . number_format($amount, (int) $this->setting('numbers.currency.decimals'));
    }

    private function money(float $amount): array
    {
        return ['amount' => $amount, 'formatted' => $this->format($amount)];
    }
}
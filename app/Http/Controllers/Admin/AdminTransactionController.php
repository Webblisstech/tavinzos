<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin view of every wallet transaction across all users, with money-in /
 * money-out stats and filters. Read-only — adjustments/refunds happen on
 * their own flows so the money logic lives in one place.
 */
class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $type   = $request->query('type');        // purchase|refund|reversal|topup|adjustment
        $status = $request->query('status');      // settled|pending|failed
        $q      = trim((string) $request->query('q'));

        // ── Stats across the whole ledger (settled only for money totals) ──
        $hasStatus = DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'status');

        // Fresh settled-scoped base each call — cheap, and avoids builder-clone
        // footguns from reusing one instance across several sums.
        $base = fn () => DB::table('wallet_transactions')
            ->when($hasStatus, fn ($x) => $x->where('status', 'settled'));

        // "Money in" is real funding only — top-ups. Refunds are money returning
        // to a wallet, not new money, so they don't count as inflow.
        $topups = (float) $base()->where('type', 'topup')->sum('amount');

        // "Money out" is REAL revenue: what customers spent and did NOT get back.
        // A cancelled number is a purchase (debit) then a refund (credit) — a
        // wash. So: gross spend, minus refunds and reversals. Only a completed
        // number (code received) leaves its purchase standing as revenue.
        $spent    = abs((float) $base()->where('type', 'purchase')->sum('amount'));
        $refunded = (float) $base()->where('type', 'refund')->sum('amount');
        $reversed = (float) $base()->where('type', 'reversal')->sum('amount');
        $realOut  = max(0, $spent - $refunded - $reversed);

        $stats = [
            'in'       => $this->money($topups),
            'out'      => $this->money($realOut),
            'topups'   => $this->money($topups),
            'refunds'  => $this->money($refunded),
            'count'    => DB::table('wallet_transactions')->count(),
            'pending'  => $hasStatus ? DB::table('wallet_transactions')->where('status', 'pending')->count() : 0,
            'held'     => $this->money((float) DB::table('users')->sum('wallet_balance')),
            'today'    => $this->money((float) $base()->where('type', 'topup')->whereDate('created_at', today())->sum('amount')),
        ];

        // ── The list ─────────────────────────────────────────────────
        $rows = DB::table('wallet_transactions')
            ->leftJoin('users', 'users.id', '=', 'wallet_transactions.user_id')
            ->when($type, fn ($x) => $x->where('wallet_transactions.type', $type))
            ->when($status && $hasStatus,
                   fn ($x) => $x->where('wallet_transactions.status', $status))
            ->when($q, fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('wallet_transactions.reference', 'like', "%$q%")
                  ->orWhere('wallet_transactions.note', 'like', "%$q%")
                  ->orWhere('users.email', 'like', "%$q%")
                  ->orWhere('users.name', 'like', "%$q%");
            }))
            ->orderByDesc('wallet_transactions.id')
            ->limit(400)
            ->get([
                'wallet_transactions.*',
                'users.name AS user_name', 'users.email AS user_email',
            ])
            ->map(fn ($t) => [
                'ref'     => $t->reference,
                'type'    => $t->type,
                'status'  => $t->status ?? 'settled',
                'amount'  => (float) $t->amount,
                'money'   => $this->money((float) $t->amount),
                'before'  => $this->money(round((float) $t->balance_after - (float) $t->amount, 2)),
                'balance' => $this->money((float) $t->balance_after),
                'note'    => $t->note,
                'user'    => $t->user_name ?: '—',
                'email'   => $t->user_email ?: '—',
                'at'      => $t->created_at,
            ]);

        return view('admin.transactions.index', [
            'stats'   => $stats,
            'rows'    => $rows,
            'filters' => ['type' => $type, 'status' => $status, 'q' => $q],
        ]);
    }

    // ── money helpers ────────────────────────────────────────────────
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
    /** One transaction in detail, with the wallet balance before and after. */
    public function show(Request $request, string $ref)
    {
        $t = DB::table('wallet_transactions')
            ->leftJoin('users', 'users.id', '=', 'wallet_transactions.user_id')
            ->where('wallet_transactions.reference', $ref)
            ->first([
                'wallet_transactions.*',
                'users.name AS user_name', 'users.email AS user_email',
                'users.wallet_balance AS current_balance',
            ]);

        abort_unless($t, 404);

        $amount = (float) $t->amount;
        $after  = (float) $t->balance_after;
        // Balance before this entry is simply the after minus what it moved.
        $before = round($after - $amount, 2);

        return view('admin.transactions.show', [
            'tx' => [
                'ref'      => $t->reference,
                'gateway'  => $t->gateway_ref ?? null,
                'type'     => $t->type,
                'status'   => $t->status ?? 'settled',
                'amount'   => $this->money($amount),
                'credit'   => $amount >= 0,
                'before'   => $this->money($before),
                'after'    => $this->money($after),
                'current'  => $this->money((float) ($t->current_balance ?? 0)),
                'note'     => $t->note,
                'user'     => $t->user_name ?: '—',
                'email'    => $t->user_email ?: '—',
                'at'       => $t->created_at,
            ],
        ]);
    }

    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol')
            . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
    }
}
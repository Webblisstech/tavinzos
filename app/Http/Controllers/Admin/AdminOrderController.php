<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin view of every order across both product lines — virtual numbers
 * (verifications) and account logs (log_orders) — with headline stats and
 * filters. Read-only oversight; refunds/cancels happen on their own flows.
 */
class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $type   = $request->query('type', 'all');      // all | numbers | accounts
        $status = $request->query('status');           // per-type raw status
        $q      = trim((string) $request->query('q'));
        $since  = now()->subDays(30);

        // ── Headline stats (all-time + 30-day revenue) ───────────────
        $numRevenue = (float) DB::table('verifications')->where('status', '!=', 'cancelled')->sum('price');
        $logRevenue = (float) DB::table('log_orders')->where('status', 'delivered')->sum('total');

        $stats = [
            'orders'     => DB::table('verifications')->count() + DB::table('log_orders')->count(),
            'revenue'    => $this->money($numRevenue + $logRevenue),
            'numbers'    => DB::table('verifications')->count(),
            'accounts'   => DB::table('log_orders')->count(),
            'waiting'    => DB::table('verifications')->where('status', 'waiting')->count(),
            'refunded'   => $this->money(
                (float) DB::table('verifications')->whereNotNull('refunded_at')->sum('refund_amount')
            ),
            'today'      => $this->money(
                (float) DB::table('verifications')->where('status', '!=', 'cancelled')->whereDate('created_at', today())->sum('price')
                + (float) DB::table('log_orders')->where('status', 'delivered')->whereDate('created_at', today())->sum('total')
            ),
            'customers'  => DB::table('users')->count(),
        ];

        // ── Build the two streams, normalised ────────────────────────
        $rows = collect();

        if ($type !== 'accounts') {
            $numbers = DB::table('verifications')
                ->leftJoin('users', 'users.id', '=', 'verifications.user_id')
                ->when($status && $type === 'numbers', fn ($x) => $x->where('verifications.status', $status))
                ->when($q, fn ($x) => $x->where(function ($w) use ($q) {
                    $w->where('verifications.reference', 'like', "%$q%")
                      ->orWhere('verifications.service', 'like', "%$q%")
                      ->orWhere('verifications.phone_number', 'like', "%$q%")
                      ->orWhere('users.email', 'like', "%$q%");
                }))
                ->orderByDesc('verifications.id')
                ->limit(300)
                ->get([
                    'verifications.reference', 'verifications.service', 'verifications.country',
                    'verifications.phone_number', 'verifications.status', 'verifications.price',
                    'verifications.code', 'verifications.created_at', 'verifications.refunded_at',
                    'users.name AS user_name', 'users.email AS user_email',
                ])
                ->map(fn ($r) => [
                    'kind'   => 'number',
                    'ref'    => $r->reference,
                    'title'  => $r->service ?: 'Number',
                    'sub'    => trim(($r->country ? $r->country . ' · ' : '') . ($r->phone_number ?? '')),
                    'user'   => $r->user_name ?: '—',
                    'email'  => $r->user_email ?: '—',
                    'status' => ucfirst($r->status),
                    'amount' => $this->money((float) $r->price),
                    'at'     => $r->created_at,
                    'extra'  => $r->code,
                ]);
            $rows = $rows->concat($numbers);
        }

        if ($type !== 'numbers') {
            $accounts = DB::table('log_orders')
                ->leftJoin('users', 'users.id', '=', 'log_orders.user_id')
                ->when($status && $type === 'accounts', fn ($x) => $x->where('log_orders.status', $status))
                ->when($q, fn ($x) => $x->where(function ($w) use ($q) {
                    $w->where('log_orders.reference', 'like', "%$q%")
                      ->orWhere('log_orders.product_name', 'like', "%$q%")
                      ->orWhere('users.email', 'like', "%$q%");
                }))
                ->orderByDesc('log_orders.id')
                ->limit(300)
                ->get([
                    'log_orders.reference', 'log_orders.product_name', 'log_orders.quantity',
                    'log_orders.unit_price', 'log_orders.total', 'log_orders.status', 'log_orders.created_at',
                    'users.name AS user_name', 'users.email AS user_email',
                ])
                ->map(fn ($o) => [
                    'kind'   => 'account',
                    'ref'    => $o->reference,
                    'title'  => $o->product_name,
                    'sub'    => $o->quantity . ' × ' . $this->money((float) $o->unit_price)['formatted'],
                    'user'   => $o->user_name ?: '—',
                    'email'  => $o->user_email ?: '—',
                    'status' => ucfirst($o->status),
                    'amount' => $this->money((float) $o->total),
                    'at'     => $o->created_at,
                    'extra'  => null,
                ]);
            $rows = $rows->concat($accounts);
        }

        $rows = $rows->sortByDesc('at')->values();

        return view('admin.orders.index', [
            'stats'   => $stats,
            'rows'    => $rows,
            'filters' => ['type' => $type, 'status' => $status, 'q' => $q],
            'counts'  => [
                'all'      => $stats['orders'],
                'numbers'  => $stats['numbers'],
                'accounts' => $stats['accounts'],
            ],
        ]);
    }

    /** One order in detail, either kind, with its refund state. */
    public function show(Request $request, string $ref)
    {
        $order = $this->findOrder($ref);
        abort_unless($order, 404);

        return view('admin.orders.show', ['order' => $order]);
    }

    /**
     * Refund an order to the customer's wallet. Idempotent and locked: the
     * order row is claimed inside a transaction and flagged, so a double
     * submit can never credit twice. Reuses the same credit shape as the
     * customer-facing flows.
     */
    public function refund(Request $request, string $ref)
    {
        $data = $request->validate([
            'kind'   => ['required', 'in:number,account'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $data['kind'] === 'number'
            ? $this->refundNumber($ref, $data['reason'] ?? null)
            : $this->refundAccount($ref, $data['reason'] ?? null);

        return redirect()
            ->route('admin.orders.show', $ref)
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    private function refundNumber(string $ref, ?string $reason): array
    {
        return DB::transaction(function () use ($ref, $reason) {
            $order = DB::table('verifications')->where('reference', $ref)->lockForUpdate()->first();
            if (! $order) {
                return ['ok' => false, 'message' => __('Order not found.')];
            }
            if ($order->refunded_at) {
                return ['ok' => false, 'message' => __('This order was already refunded.')];
            }

            $amount = (float) $order->price;

            DB::table('verifications')->where('id', $order->id)->update([
                'status'        => 'cancelled',
                'refund_amount' => $amount,
                'refunded_at'   => now(),
                'updated_at'    => now(),
            ]);

            $this->creditWallet($order->user_id, $amount, 'refund', $order->id,
                'Admin refund · ' . $ref . ($reason ? ' · ' . $reason : ''));

            return ['ok' => true, 'message' => __('Refunded :amt to the customer.', ['amt' => $this->money($amount)['formatted']])];
        });
    }

    private function refundAccount(string $ref, ?string $reason): array
    {
        return DB::transaction(function () use ($ref, $reason) {
            $order = DB::table('log_orders')->where('reference', $ref)->lockForUpdate()->first();
            if (! $order) {
                return ['ok' => false, 'message' => __('Order not found.')];
            }
            if ($order->status === 'refunded') {
                return ['ok' => false, 'message' => __('This order was already refunded.')];
            }

            $amount = (float) $order->total;

            DB::table('log_orders')->where('id', $order->id)->update([
                'status'     => 'refunded',
                'updated_at' => now(),
            ]);

            $this->creditWallet($order->user_id, $amount, 'refund', null,
                'Admin refund · ' . $ref . ($reason ? ' · ' . $reason : ''));

            return ['ok' => true, 'message' => __('Refunded :amt to the customer.', ['amt' => $this->money($amount)['formatted']])];
        });
    }

    /** Credit a wallet — same shape the number/wallet controllers use. */
    private function creditWallet(int $userId, float $amount, string $type, ?int $verificationId, string $note): void
    {
        $balance = (float) DB::table('users')->where('id', $userId)->lockForUpdate()->value('wallet_balance');
        $after   = round($balance + $amount, 2);

        DB::table('users')->where('id', $userId)->update(['wallet_balance' => $after]);

        DB::table('wallet_transactions')->insert([
            'user_id'         => $userId,
            'verification_id' => $verificationId,
            'reference'       => 'RFND' . strtoupper(\Illuminate\Support\Str::random(8)),
            'type'            => $type,
            'amount'          => $amount,
            'balance_after'   => $after,
            'currency'        => (string) $this->setting('numbers.currency.code', 'NGN'),
            'note'            => $note,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /** Locate an order by reference across both tables, normalised for the view. */
    private function findOrder(string $ref): ?array
    {
        $v = DB::table('verifications')
            ->leftJoin('users', 'users.id', '=', 'verifications.user_id')
            ->where('verifications.reference', $ref)
            ->first([
                'verifications.*',
                'users.name AS user_name', 'users.email AS user_email',
            ]);

        if ($v) {
            return [
                'kind'      => 'number',
                'ref'       => $v->reference,
                'title'     => $v->service ?: 'Number',
                'meta'      => array_filter([
                    __('Country') => $v->country,
                    __('Number')  => $v->phone_number,
                    __('Code')    => $v->code,
                    __('Gateway') => strtoupper($v->gateway ?? ''),
                ]),
                'user'      => $v->user_name ?: '—',
                'email'     => $v->user_email ?: '—',
                'status'    => ucfirst($v->status),
                'amount'    => $this->money((float) $v->price),
                'at'        => $v->created_at,
                'refunded'  => (bool) $v->refunded_at,
                'refund'    => $v->refund_amount ? $this->money((float) $v->refund_amount) : null,
            ];
        }

        $o = DB::table('log_orders')
            ->leftJoin('users', 'users.id', '=', 'log_orders.user_id')
            ->where('log_orders.reference', $ref)
            ->first([
                'log_orders.*',
                'users.name AS user_name', 'users.email AS user_email',
            ]);

        if ($o) {
            return [
                'kind'      => 'account',
                'ref'       => $o->reference,
                'title'     => $o->product_name,
                'meta'      => [
                    __('Quantity')   => $o->quantity,
                    __('Unit price') => $this->money((float) $o->unit_price)['formatted'],
                ],
                'user'      => $o->user_name ?: '—',
                'email'     => $o->user_email ?: '—',
                'status'    => ucfirst($o->status),
                'amount'    => $this->money((float) $o->total),
                'at'        => $o->created_at,
                'refunded'  => $o->status === 'refunded',
                'refund'    => $o->status === 'refunded' ? $this->money((float) $o->total) : null,
            ];
        }

        return null;
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
    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol')
            . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
    }
}
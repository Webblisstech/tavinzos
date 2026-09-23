<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The customer's own wallet transaction history — every credit and debit, with
 * a running balance, filterable by type and paginated.
 */
class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $filter = $request->query('type', 'all');

        $q = DB::table('wallet_transactions')->where('user_id', $userId);

        // Group the filter into money-in vs money-out plus specific types.
        match ($filter) {
            'in'       => $q->where('amount', '>=', 0),
            'out'      => $q->where('amount', '<', 0),
            'topup'    => $q->where('type', 'topup'),
            'purchase' => $q->where('type', 'purchase'),
            'refund'   => $q->whereIn('type', ['refund', 'reversal']),
            default    => null,
        };

        $rows = $q->orderByDesc('id')->paginate(20)->withQueryString();

        $tx = collect($rows->items())->map(fn ($t) => [
            'reference'     => $t->reference,
            'type'          => $t->type,
            'status'        => $t->status ?? 'settled',
            'amount'        => (float) $t->amount,
            'formatted'     => ($t->amount >= 0 ? '+' : '−') . $this->format(abs((float) $t->amount)),
            'balance_after' => $this->format((float) $t->balance_after),
            'note'          => $t->note,
            'at'            => $t->created_at,
        ]);

        // Totals for the header (all-time, not just this page).
        // Money IN = actual funding only (top-ups). Refunds from cancelled
        // numbers are money returning, not money the customer put in, so they
        // don't count here.
        $inTotal = (float) DB::table('wallet_transactions')
            ->where('user_id', $userId)
            ->where('type', 'topup')
            ->when(
                DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'status'),
                fn ($q) => $q->where('status', 'settled')
            )
            ->sum('amount');

        // Money OUT = only what was actually delivered: numbers that received a
        // code (verifications.status = 'completed') plus delivered account
        // purchases (log_orders.status = 'delivered'). A number that never got a
        // code — even if not formally refunded — is NOT counted.
        $numberSpend = (float) DB::table('verifications')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('price');

        $accountSpend = DB::getSchemaBuilder()->hasTable('log_orders')
            ? (float) DB::table('log_orders')
                ->where('user_id', $userId)
                ->where('status', 'delivered')
                ->sum('total')
            : 0.0;

        $outTotal = $numberSpend + $accountSpend;

        return view('transactions.index', [
            'tx'       => $tx,
            'paginator'=> $rows,
            'filter'   => $filter,
            'balance'  => $this->money((float) $request->user()->wallet_balance),
            'in'       => $this->money($inTotal),
            'out'      => $this->money($outTotal),
        ]);
    }

    private function format(float $a): string
    {
        return $this->setting('numbers.currency.symbol', '₦') . number_format($a, (int) $this->setting('numbers.currency.decimals', 0));
    }

    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->format($a)];
    }

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
        return $this->settingsCache[$key] ?? $default;
    }
}
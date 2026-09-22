<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * A customer's order history across both product lines — virtual numbers and
 * account logs — merged into one timeline. Read-only: the actual actions
 * (cancel a number, download a log) live on their own pages/endpoints.
 */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $tab = in_array($request->query('tab'), ['numbers', 'accounts']) ? $request->query('tab') : 'all';

        $numbers  = $this->numbers($userId);
        $accounts = $this->accounts($userId);

        // A merged, newest-first feed for the "All" tab.
        $all = $numbers->concat($accounts)
            ->sortByDesc('at')
            ->values();

        return view('orders.index', [
            'tab'      => $tab,
            'all'      => $all,
            'numbers'  => $numbers,
            'accounts' => $accounts,
            'counts'   => [
                'all'      => $all->count(),
                'numbers'  => $numbers->count(),
                'accounts' => $accounts->count(),
            ],
        ]);
    }

    /** Virtual-number rentals, normalised to the shared shape. */
    private function numbers(int $userId)
    {
        return DB::table('verifications')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn ($v) => [
                'kind'        => 'number',
                'ref'         => $v->reference,
                'title'       => $v->service ?: 'Number',
                'sub'         => trim(($v->country ? $v->country . ' · ' : '') . ($v->phone_number ?? '')),
                'code'        => $v->code,
                'status'      => ucfirst($v->status),
                'amount'      => $this->money((float) $v->price),
                'at'          => $v->created_at,
                // Cancel eligibility, mirroring the numbers page: only a
                // still-waiting order past its lock window can be cancelled.
                'cancellable' => $v->status === 'waiting' && ! $v->refunded_at,
                'bought_at'   => \Illuminate\Support\Carbon::parse($v->created_at)->timestamp,
                'lock'        => (int) $this->setting('numbers.cancel_lock', 180),
                'flag'        => $this->isoFor($v->country),
            ]);
    }

    /** Account-log purchases. */
    private function accounts(int $userId)
    {
        return DB::table('log_orders')
            ->where('log_orders.user_id', $userId)
            ->leftJoin('log_products', 'log_products.id', '=', 'log_orders.log_product_id')
            ->leftJoin('log_categories', 'log_categories.id', '=', 'log_products.log_category_id')
            ->orderByDesc('log_orders.id')
            ->limit(200)
            ->select('log_orders.*')
            ->selectRaw('log_products.image AS p_image')
            ->selectRaw('log_products.flag AS p_flag')
            ->selectRaw('log_categories.icon AS p_icon')
            ->get()
            ->map(fn ($o) => [
                'kind'      => 'account',
                'ref'       => $o->reference,
                'title'     => $o->product_name,
                'sub'       => $o->quantity . ' × ' . $this->money((float) $o->unit_price)['formatted'],
                'quantity'  => $o->quantity,
                'status'    => ucfirst($o->status),
                'amount'    => $this->money((float) $o->total),
                'at'        => $o->created_at,
                // Product artwork, so the row shows the real image/logo. The
                // product may be gone (nullOnDelete) — all three can be null.
                'image'     => $o->p_image ? route('media.show', $o->p_image) : null,
                'flag'      => $o->p_flag,
                'icon'      => $o->p_icon,
            ]);
    }

    /** Country name → ISO2 for the flag. Null if unknown. */
    private function isoFor(?string $country): ?string
    {
        static $map = [
            'USA' => 'us', 'United States' => 'us', 'United Kingdom' => 'gb', 'UK' => 'gb',
            'Canada' => 'ca', 'Spain' => 'es', 'Germany' => 'de', 'France' => 'fr',
            'Italy' => 'it', 'Netherlands' => 'nl', 'Poland' => 'pl', 'Portugal' => 'pt',
            'Indonesia' => 'id', 'India' => 'in', 'Philippines' => 'ph', 'Nigeria' => 'ng',
            'Brazil' => 'br', 'Mexico' => 'mx', 'Russia' => 'ru', 'Ukraine' => 'ua',
        ];
        return $country ? ($map[trim($country)] ?? null) : null;
    }

    /** One order's delivered accounts — the receipt view. */
    public function show(Request $request, string $ref)
    {
        $order = DB::table('log_orders')
            ->where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($order, 404);

        $items = DB::table('log_items')
            ->where('log_order_id', $order->id)
            ->orderBy('id')
            ->get(['content', 'label', 'preview_url']);

        return view('orders.show', [
            'order' => $order,
            'items' => $items,
            'total' => $this->money((float) $order->total),
        ]);
    }

    // ── Shared money helpers ─────────────────────────────────────────

    private ?array $settingsCache = null;

    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                            'int'   => (int) $r->value,
                            'float' => (float) $r->value,
                            'bool'  => filter_var($r->value, FILTER_VALIDATE_BOOL),
                            default => $r->value,
                        }])->all();
                } catch (\Throwable) {
                    return [];
                }
            });
        }

        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }

    private function money(float $amount): array
    {
        return [
            'amount'    => $amount,
            'formatted' => $this->setting('numbers.currency.symbol')
                . number_format($amount, (int) $this->setting('numbers.currency.decimals')),
        ];
    }
}
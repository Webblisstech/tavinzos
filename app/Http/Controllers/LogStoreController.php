<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFunds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The account store: pre-made accounts the admin uploads, sold from stock.
 *
 * The one hard rule: an item is delivered to exactly one buyer. Claiming
 * locks the rows, so two people buying the last three accounts at the same
 * instant can never receive overlapping sets.
 */
class LogStoreController extends Controller
{
    public function index(Request $request)
    {
        // Admin can close the store from settings — numbers stay open.
        if ((bool) $this->setting('store.maintenance', false)) {
            return view('logs.maintenance', [
                'balance' => $this->money((float) $request->user()->wallet_balance),
            ]);
        }

        $stock = DB::table('log_items')
            ->selectRaw('COUNT(*)')
            ->whereColumn('log_items.log_product_id', 'log_products.id')
            ->where('log_items.status', 'available');

        $products = DB::table('log_products')
            ->where('log_products.is_active', true)
            ->leftJoin('log_categories', 'log_categories.id', '=', 'log_products.log_category_id')
            ->select('log_products.*')
            ->selectSub($stock, 'stock')
            ->selectRaw('COALESCE(log_products.previewable, 0) AS previewable')
            ->selectRaw('EXISTS (SELECT 1 FROM log_items WHERE log_items.log_product_id = log_products.id AND log_items.status = ? AND log_items.preview_url IS NOT NULL) AS has_previews', ['available'])
            ->selectRaw('log_categories.icon AS cat_icon')
            ->orderBy('log_products.sort')
            ->orderBy('log_products.category')
            ->orderBy('log_products.name')
            ->get()
            ->map(fn ($p) => $this->present($p));

        // Group the products under their category, keeping the admin's order.
        // Anything with no category falls into an "Other" bucket at the end.
        $catRows = DB::table('log_categories')
            ->where('is_active', true)
            ->orderBy('sort')->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon'])
            ->keyBy('id');

        $groups = [];

        foreach ($products as $product) {
            $cid = $product['category_id'] ?? 0;
            if (! isset($groups[$cid])) {
                $cat = $catRows[$cid] ?? null;
                $groups[$cid] = [
                    'name'     => $cat->name ?? $product['category'] ?? __('Other'),
                    'icon'     => $cat->icon ?? null,
                    'products' => [],
                ];
            }
            $groups[$cid]['products'][] = $product;
        }

        // Order the sections by the category's sort, categories first.
        $order = $catRows->keys()->push(0)->all();
        uksort($groups, fn ($a, $b) => array_search($a, $order) <=> array_search($b, $order));

        return view('logs.index', [
            'groups'  => $groups,
            'balance' => $this->money((float) $request->user()->wallet_balance),
        ]);
    }

    /** The buy modal's live detail — fresh stock, in case it moved. */
    public function show(Request $request, string $slug): JsonResponse
    {
        $product = DB::table('log_products')
            ->where('log_products.slug', $slug)
            ->where('log_products.is_active', true)
            ->leftJoin('log_categories', 'log_categories.id', '=', 'log_products.log_category_id')
            ->select('log_products.*')
            ->selectRaw('log_categories.icon AS cat_icon')
            ->first();

        abort_unless($product, 404);

        return $this->ok($this->present($product, $this->stockOf($product->id)));
    }

    /**
     * The browsable, previewable accounts for a product — token, label, and
     * the preview URL only. Never the credentials; those are handed over only
     * after purchase.
     */
    public function items(Request $request, string $slug): JsonResponse
    {
        // Serve items whenever this product actually has previewable stock —
        // the same condition the buy modal uses to show the list. The
        // `previewable` flag is a hint for the picker, not a gate here.
        $product = DB::table('log_products')
            ->where('slug', $slug)->where('is_active', true)
            ->first();

        abort_unless($product, 404);

        $items = DB::table('log_items')
            ->where('log_product_id', $product->id)
            ->where('status', 'available')
            ->orderBy('id')
            ->limit(200)
            ->get(['token', 'label', 'preview_url']);

        return $this->ok([
            'slug'  => $product->slug,
            'name'  => $product->name,
            'price' => $this->money((float) $product->price),
            'items' => $items->map(fn ($i) => [
                'token'   => $i->token,
                'label'   => $i->label,
                'preview' => $i->preview_url,
            ])->all(),
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        // Store closed — refuse buys even if the UI was bypassed.
        if ((bool) $this->setting('store.maintenance', false)) {
            return $this->fail('The store is under maintenance. Please check back soon.', 503);
        }

        $data = $request->validate([
            'slug'     => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'tokens'   => ['nullable', 'array', 'max:500'],
            'tokens.*' => ['string', 'size:20'],
            'delivery' => ['nullable', 'in:screen,file'],
        ]);

        // Either the customer picked specific previewed items, or they asked
        // for a plain quantity from the pool. Chosen tokens win.
        $chosen = array_values(array_unique($data['tokens'] ?? []));

        $product = DB::table('log_products')->where('slug', $data['slug'])->where('is_active', true)->first();

        if (! $product) {
            return $this->fail('That product is no longer available.', 404);
        }

        $qty = $chosen
            ? count($chosen)
            : min((int) ($data['quantity'] ?? 0), $product->max_per_order);

        if ($qty < 1) {
            return $this->fail('Choose at least one.', 422);
        }
        $qty = min($qty, $product->max_per_order);

        $total = round((float) $product->price * $qty, 2);

        // ── Everything that moves money or stock happens in one transaction:
        //    debit, claim the items, write the order. Any failure rolls the
        //    whole thing back — no half-sold order, no orphaned debit.
        try {
            $result = DB::transaction(function () use ($request, $product, $qty, $total, $chosen) {

                // 1. Lock and claim the items. Chosen tokens select exactly
                //    those; otherwise the oldest N in the pool.
                $query = DB::table('log_items')
                    ->where('log_product_id', $product->id)
                    ->where('status', 'available')
                    ->orderBy('id')
                    ->lockForUpdate();

                if ($chosen) {
                    $query->whereIn('token', $chosen);
                } else {
                    $query->limit($qty);
                }

                $items = $query->get();

                if ($items->count() < $qty) {
                    // Not enough stock — someone bought it first, or it never
                    // existed. Nothing is charged.
                    throw new OutOfStock($items->count());
                }

                // 2. Take the money (its own lock on the user row).
                $balanceAfter = $this->debit($request->user()->id, $total, $product->name);

                // 3. Write the order.
                $ref     = strtoupper(Str::random(10));
                $orderId = DB::table('log_orders')->insertGetId([
                    'user_id'        => $request->user()->id,
                    'log_product_id' => $product->id,
                    'reference'      => $ref,
                    'product_name'   => $product->name,
                    'quantity'       => $qty,
                    'unit_price'     => $product->price,
                    'total'          => $total,
                    'currency'       => $product->currency,
                    'status'         => 'delivered',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // 4. Mark the items sold and tie them to this order.
                DB::table('log_items')
                    ->whereIn('id', $items->pluck('id'))
                    ->update([
                        'status'       => 'sold',
                        'user_id'      => $request->user()->id,
                        'log_order_id' => $orderId,
                        'sold_at'      => now(),
                        'updated_at'   => now(),
                    ]);

                return [
                    'ref'     => $ref,
                    'items'   => $items,
                    'balance' => $balanceAfter,
                ];
            });
        } catch (OutOfStock $e) {
            return $this->fail(
                $e->available === 0
                    ? 'This just sold out.'
                    : 'Only ' . $e->available . ' left — reduce the quantity.',
                422,
                'OUT_OF_STOCK'
            );
        } catch (InsufficientFunds $e) {
            return response()->json([
                'success' => false,
                'code'    => 'INSUFFICIENT_FUNDS',
                'message' => __('Short by :gap. This costs :price and your balance is :have.', [
                    'gap'   => $this->format(max(0, $total - $e->balance)),
                    'price' => $this->format($total),
                    'have'  => $this->format($e->balance),
                ]),
            ], 422);
        }

        // The purchase is committed — count it toward the buyer's spend, which
        // may trigger their referrer's one-time commission.
        \App\Support\Referral::recordSpend($request->user()->id, $total);

        return $this->ok([
            'ref'      => $result['ref'],
            'product'  => $product->name,
            'quantity' => $qty,
            'total'    => $this->money($total),
            'balance'  => $this->money($result['balance']),
            // The accounts themselves. Shown on screen and/or downloaded.
            'items'    => $result['items']->pluck('content')->all(),
        ], 201);
    }

    /** A signed-in customer downloading their own past order — txt or pdf. */
    public function download(Request $request, string $ref)
    {
        $format = $request->query('format') === 'pdf' ? 'pdf' : 'txt';

        $order = DB::table('log_orders')
            ->where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($order, 404);

        $items = DB::table('log_items')
            ->where('log_order_id', $order->id)
            ->orderBy('id')
            ->get(['content', 'label', 'preview_url']);

        $base = Str::slug($order->product_name) . '-' . $order->reference;

        if ($format === 'pdf') {
            return $this->downloadPdf($order, $items, $base . '.pdf');
        }

        $body = $items->pluck('content')->implode("\n\n" . str_repeat('─', 40) . "\n\n");

        return response($body, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $base . '.txt"',
        ]);
    }

    /**
     * Render the order to a PDF. Uses barryvdh/laravel-dompdf when installed;
     * if it isn't, falls back to the .txt so the button never dead-ends.
     */
    private function downloadPdf(object $order, $items, string $filename)
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            // Library not installed yet — hand back the text version instead
            // of a 500, and say how to enable PDFs.
            \Illuminate\Support\Facades\Log::warning('PDF requested but dompdf is not installed');

            $body = $items->pluck('content')->implode("\n\n" . str_repeat('-', 40) . "\n\n");

            return response($body, 200, [
                'Content-Type'        => 'text/plain; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . str_replace('.pdf', '.txt', $filename) . '"',
            ]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.pdf', [
            'order'    => $order,
            'items'    => $items,
            'total'    => $this->money((float) $order->total),
            'siteName' => (string) $this->setting('site.name', config('app.name', 'Numera')),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }

    // ═══════════════════════ Internals ═══════════════════════

    private function present(object $p, ?int $stock = null): array
    {
        $stock ??= (int) ($p->stock ?? $this->stockOf($p->id));

        return [
            'slug'         => $p->slug,
            'name'         => $p->name,
            'category'     => $p->category,
            'category_id'  => $p->log_category_id ?? 0,
            'icon'         => $p->cat_icon ?? null,
            'image'        => $p->image ? route('media.show', $p->image) : null,
            'country'      => $p->country,
            'flag'         => $p->flag,
            'description'  => $p->description,
            'instructions' => $p->instructions,
            'price'        => $this->money((float) $p->price),
            'max'          => (int) $p->max_per_order,
            'stock'        => $stock,
            'pre_order'    => (bool) $p->pre_order,
            'previewable'  => (bool) ($p->previewable ?? false),
            // True when any available item carries a preview link. Computed in
            // the list query (below) so this stays one query, not N.
            'has_previews' => (bool) ($p->has_previews ?? false),
        ];
    }

    private function stockOf(int $productId): int
    {
        return (int) DB::table('log_items')
            ->where('log_product_id', $productId)
            ->where('status', 'available')
            ->count();
    }

    /** @throws InsufficientFunds */
    private function debit(int $userId, float $amount, string $note): float
    {
        $balance = (float) DB::table('users')
            ->where('id', $userId)
            ->lockForUpdate()
            ->value('wallet_balance');

        if ($balance - $amount < 0) {
            throw new InsufficientFunds($balance);
        }

        $after = round($balance - $amount, 2);

        DB::table('users')->where('id', $userId)->update(['wallet_balance' => $after]);

        DB::table('wallet_transactions')->insert([
            'user_id'       => $userId,
            'reference'     => 'LOG' . strtoupper(Str::random(9)),
            'type'          => 'purchase',
            'amount'        => -$amount,
            'balance_after' => $after,
            'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
            'note'          => $note,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $after;
    }

    // ── Shared readers (kept local, same as the other controllers) ──

    private ?array $settingsCache = null;

    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = \Illuminate\Support\Facades\Cache::remember('settings', 300, function () {
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

    private function format(float $amount): string
    {
        return $this->setting('numbers.currency.symbol')
            . number_format($amount, (int) $this->setting('numbers.currency.decimals'));
    }

    private function money(float $amount): array
    {
        return ['amount' => $amount, 'formatted' => $this->format($amount)];
    }

    private function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function fail(string $message, int $status = 422, ?string $code = null): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code], $status);
    }
}

/** Thrown inside the purchase transaction when stock ran short. */
class OutOfStock extends \RuntimeException
{
    public function __construct(public readonly int $available)
    {
        parent::__construct('Out of stock.');
    }
}
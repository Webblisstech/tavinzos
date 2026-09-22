<?php

namespace App\Http\Controllers;

use App\Support\ShopVia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reseller store: lists ShopVia's products live (priced with our markup and
 * FX) and passes purchases through, delivering the accounts they return and
 * debiting the customer's wallet — with the same locking/idempotency as our
 * own-stock store.
 */
class ShopViaController extends Controller
{
    public function index(Request $request)
    {
        $products = collect(ShopVia::products())
            ->filter(fn ($p) => $p['stock'] > 0)         // only in-stock
            ->map(fn ($p) => [
                'id'       => $p['id'],
                'name'     => $p['name'],
                'category' => $p['category'],
                'stock'    => $p['stock'],
                'price'    => $this->retail($p['price']),   // their price → our retail
                'note'     => $p['note'],
            ])
            ->groupBy('category');

        return view('shopvia.index', [
            'groups'  => $products,
            'balance' => $this->money((float) $request->user()->wallet_balance),
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'       => ['required', 'string'],
            'amount'   => ['required', 'integer', 'min:1', 'max:100'],
            'coupon'   => ['nullable', 'string', 'max:64'],
        ]);

        // Look up the product live for its current price/stock.
        $product = collect(ShopVia::products())->firstWhere('id', $data['id']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'That product is no longer available.'], 422);
        }
        if ($product['stock'] < $data['amount']) {
            return response()->json(['success' => false, 'message' => 'Not enough stock. Only ' . $product['stock'] . ' left.'], 422);
        }

        $unit  = $this->retail($product['price'])['amount'];
        $total = round($unit * $data['amount'], 2);
        $user  = $request->user();

        // Debit first (locked), then buy. If the buy fails, refund.
        $balanceAfter = DB::transaction(function () use ($user, $total) {
            $u = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if ((float) $u->wallet_balance < $total) {
                throw new \RuntimeException('INSUFFICIENT_FUNDS');
            }
            $after = round((float) $u->wallet_balance - $total, 2);
            DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $after]);
            DB::table('wallet_transactions')->insert([
                'user_id' => $user->id, 'reference' => 'SVP' . strtoupper(Str::random(9)),
                'type' => 'purchase', 'status' => 'settled', 'amount' => -$total,
                'balance_after' => $after, 'currency' => (string) $this->setting('numbers.currency.code', 'NGN'),
                'note' => $product['name'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $after;
        });

        // Call the provider.
        $result = ShopVia::buy($data['id'], $data['amount'], $data['coupon'] ?? null);

        if (! $result['ok']) {
            // Refund — provider didn't deliver.
            DB::transaction(function () use ($user, $total, $product) {
                $u = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
                $after = round((float) $u->wallet_balance + $total, 2);
                DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $after]);
                DB::table('wallet_transactions')->insert([
                    'user_id' => $user->id, 'reference' => 'SVR' . strtoupper(Str::random(9)),
                    'type' => 'refund', 'status' => 'settled', 'amount' => $total,
                    'balance_after' => $after, 'currency' => (string) $this->setting('numbers.currency.code', 'NGN'),
                    'note' => 'Refund: ' . $product['name'], 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            return response()->json([
                'success' => false,
                'message' => __('This purchase could not be completed and you have not been charged. Please try again.'),
            ], 422);
        }

        // Delivered. Save the order + the accounts.
        $ref = 'SV' . strtoupper(Str::random(10));
        DB::table('log_orders')->insert([
            'reference'    => $ref,
            'user_id'      => $user->id,
            'product_name' => $product['name'],
            'quantity'     => $data['amount'],
            'unit_price'   => $unit,
            'total'        => $total,
            'currency'     => (string) $this->setting('numbers.currency.code', 'NGN'),
            'status'       => 'delivered',
            'meta'         => json_encode(['source' => 'shopvia', 'trans_id' => $result['trans_id']]),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Count toward referral spend.
        \App\Support\Referral::recordSpend($user->id, $total);

        // Refresh catalog cache so stock reflects the purchase.
        Cache::forget('shopvia:products');

        return response()->json([
            'success' => true,
            'data'    => [
                'ref'      => $ref,
                'accounts' => $result['accounts'],   // ["login|pass", ...]
                'balance'  => $this->money($balanceAfter),
            ],
        ], 201);
    }

    // ── pricing ──────────────────────────────────────────────────────
    /**
     * Their price → our retail. Their prices are in the provider's currency
     * (VND/USD); convert with a rate and add markup, both admin-set.
     */
    private function retail(float $providerPrice): array
    {
        $rate   = (float) $this->setting('shopvia.rate', 1);           // provider unit → local
        $mode   = (string) $this->setting('shopvia.markup_mode', $this->setting('numbers.markup.mode', 'percent'));
        $value  = (float) $this->setting('shopvia.markup_value', $this->setting('numbers.markup.value', 35));

        $local = $providerPrice * $rate;
        $price = $mode === 'flat' ? $local + $value : $local * (1 + $value / 100);

        return $this->money(round($price, (int) $this->setting('numbers.currency.decimals')));
    }

    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol') . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
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
        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }
}
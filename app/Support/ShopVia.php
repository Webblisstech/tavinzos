<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ShopViaClone reseller API — lists their catalog live and passes purchases
 * through. Their field names aren't fully documented, so the mappers below
 * accept several common English/Vietnamese key names and fall back gracefully.
 *
 * Purchase response delivers accounts immediately as "login|password" strings.
 */
class ShopVia
{
    private static function base(): string
    {
        return rtrim(Gateway::shopviaBase(), '/');
    }

    private static function key(): string
    {
        return Gateway::shopviaKey();
    }

    /** GET helper against the reseller. */
    private static function get(string $path, array $query = []): ?array
    {
        try {
            $res = Http::acceptJson()->timeout(20)
                ->get(self::base() . '/' . ltrim($path, '/'), array_merge(['api_key' => self::key()], $query));
            return $res->successful() ? (array) $res->json() : null;
        } catch (\Throwable $e) {
            Log::warning('ShopVia GET failed', ['path' => $path, 'err' => $e->getMessage()]);
            return null;
        }
    }

    /** First non-empty of several possible keys. */
    private static function pick(array $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) {
                return $row[$k];
            }
        }
        return $default;
    }

    /** The catalog: normalized products, cached briefly. */
    public static function products(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('shopvia:products');
        }
        return Cache::remember('shopvia:products', 300, function () {
            $raw = self::get('products.php');
            if (! $raw) {
                return [];
            }

            // The payload may be a flat list, or grouped by category. Flatten
            // whatever shape it is into a list of product rows.
            $rows = self::flattenProducts($raw);

            return collect($rows)->map(function ($p) {
                return [
                    'id'       => (string) self::pick($p, ['id', 'product_id', 'ID']),
                    'name'     => (string) self::pick($p, ['name', 'title', 'ten', 'product_name'], 'Account'),
                    'category' => (string) self::pick($p, ['category', 'category_name', 'danh_muc', 'cat'], 'Accounts'),
                    'price'    => (float) self::pick($p, ['price', 'gia', 'amount', 'cost'], 0),
                    'stock'    => (int) self::pick($p, ['stock', 'so_luong', 'quantity', 'available', 'inventory'], 0),
                    'note'     => (string) self::pick($p, ['note', 'description', 'mo_ta', 'desc'], ''),
                    'raw'      => $p,
                ];
            })->filter(fn ($p) => $p['id'] !== '')->values()->all();
        });
    }

    /** One product's details. */
    public static function product(string $id): ?array
    {
        $raw = self::get('product.php', ['product' => $id]);
        return $raw ?: null;
    }

    /** An order's details (for saving/verifying). */
    public static function order(string $ref): ?array
    {
        $raw = self::get('order.php', ['order' => $ref]);
        return $raw ?: null;
    }

    /** Provider account balance. */
    public static function profile(): ?array
    {
        return self::get('profile.php');
    }

    /**
     * Buy `amount` of product `id`. Returns:
     *   ['ok'=>true, 'trans_id'=>..., 'accounts'=>['login|pass', ...]]
     * or ['ok'=>false, 'message'=>...].
     */
    public static function buy(string $id, int $amount, ?string $coupon = null): array
    {
        try {
            $res = Http::asForm()->acceptJson()->timeout(30)
                ->post(self::base() . '/buy_product', array_filter([
                    'action'  => 'buyProduct',
                    'id'      => $id,
                    'amount'  => $amount,
                    'coupon'  => $coupon,
                    'api_key' => self::key(),
                ], fn ($v) => $v !== null && $v !== ''));

            $body = (array) $res->json();

            if (($body['status'] ?? null) === 'success' && ! empty($body['data'])) {
                return [
                    'ok'       => true,
                    'trans_id' => (string) ($body['trans_id'] ?? ''),
                    'accounts' => array_values((array) $body['data']),
                ];
            }

            // Log their raw (possibly Vietnamese) message for us; never surface
            // it to the customer — they must not learn about the provider.
            Log::warning('ShopVia buy declined', ['msg' => $body['msg'] ?? null, 'id' => $id]);

            return ['ok' => false, 'message' => 'unavailable'];
        } catch (\Throwable $e) {
            Log::warning('ShopVia buy failed', ['id' => $id, 'err' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Could not reach the provider. Try again.'];
        }
    }

    /** Turn whatever products.php returns into a flat list of product arrays. */
    private static function flattenProducts(array $raw): array
    {
        // Common shapes: {data:[...]}, {products:[...]}, {categories:[{products:[...]}]},
        // or a bare list.
        $list = $raw['data'] ?? $raw['products'] ?? $raw['categories'] ?? $raw;

        if (! is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            // A category node carrying its own products?
            $nested = $item['products'] ?? $item['items'] ?? null;
            if (is_array($nested)) {
                $catName = self::pick($item, ['category', 'name', 'ten', 'title'], 'Accounts');
                foreach ($nested as $p) {
                    if (is_array($p)) {
                        $p['category'] = $p['category'] ?? $catName;
                        $out[] = $p;
                    }
                }
            } else {
                $out[] = $item;
            }
        }
        return $out;
    }
}
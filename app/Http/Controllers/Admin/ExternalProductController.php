<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ShopVia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin management of the external catalog: see every product with its auto
 * (FX + markup) price, set an exact price override, or hide it. Overrides live
 * in external_overrides; the store reads them first.
 */
class ExternalProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $overrides = DB::table('external_overrides')->get()->keyBy('ext_id');

        $products = collect(ShopVia::products())
            ->when($q, fn ($c) => $c->filter(fn ($p) =>
                str_contains(mb_strtolower($p['name']), mb_strtolower($q)) ||
                str_contains(mb_strtolower($p['category']), mb_strtolower($q))
            ))
            ->map(function ($p) use ($overrides) {
                $o = $overrides[$p['id']] ?? null;
                return [
                    'id'        => $p['id'],
                    'name'      => $p['name'],
                    'category'  => $p['category'],
                    'stock'     => $p['stock'],
                    'cost'      => $p['price'],                       // provider price (USD)
                    'auto'      => $this->autoPrice($p['price']),     // computed retail
                    'override'  => $o->price ?? null,                 // admin's exact price
                    'hidden'    => (bool) ($o->hidden ?? false),
                ];
            })
            ->sortBy([['category', 'asc'], ['name', 'asc']])
            ->values();

        $symbol = (string) $this->setting('numbers.currency.symbol', '₦');

        return view('admin.external.index', [
            'products' => $products,
            'symbol'   => $symbol,
            'q'        => $q,
            'count'    => $products->count(),
            'rate'     => (float) $this->setting('shopvia.rate', (float) $this->setting('numbers.currency.usd_rate', 1600)),
        ]);
    }

    /** Save price + hidden for one product. Empty price = revert to auto. */
    public function save(Request $request, string $extId)
    {
        $data = $request->validate([
            'price'  => ['nullable', 'numeric', 'min:0'],
            'hidden' => ['nullable', 'boolean'],
        ]);

        $price  = ($data['price'] ?? null) === null || $data['price'] === '' ? null : round((float) $data['price'], 2);
        $hidden = $request->boolean('hidden');

        // If nothing to override, remove the row entirely.
        if ($price === null && ! $hidden) {
            DB::table('external_overrides')->where('ext_id', $extId)->delete();
        } else {
            DB::table('external_overrides')->updateOrInsert(
                ['ext_id' => $extId],
                ['price' => $price, 'hidden' => $hidden, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('status', __('Saved.'));
    }

    /** Auto price = provider price × rate + markup (same as the store). */
    private function autoPrice(float $providerPrice): float
    {
        $rate  = (float) $this->setting('shopvia.rate', (float) $this->setting('numbers.currency.usd_rate', 1600));
        $mode  = (string) $this->setting('shopvia.markup_mode', $this->setting('numbers.markup.mode', 'percent'));
        $value = (float) $this->setting('shopvia.markup_value', $this->setting('numbers.markup.value', 35));

        $local = $providerPrice * $rate;
        $price = $mode === 'flat' ? $local + $value : $local * (1 + $value / 100);

        return round($price, (int) $this->setting('numbers.currency.decimals'));
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
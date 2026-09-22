<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin: create products and load stock into them.
 *
 * Stock arrives two ways — a pasted block or an uploaded file. Both split into
 * one item per account; the delimiter decides where one account ends and the
 * next begins, because "a whole file per account" means a single credential
 * blob can itself span several lines.
 */
class LogAdminController extends Controller
{
    /** At or below this many available items, a product is "low stock". */
    private const LOW_STOCK = 3;

    /** Common countries → ISO2. Add rows as your catalogue grows. */
    private const COUNTRY_ISO = [
        'USA' => 'us', 'United States' => 'us', 'United Kingdom' => 'gb', 'UK' => 'gb',
        'Canada' => 'ca', 'Spain' => 'es', 'Germany' => 'de', 'France' => 'fr',
        'Italy' => 'it', 'Netherlands' => 'nl', 'Poland' => 'pl', 'Portugal' => 'pt',
        'Indonesia' => 'id', 'India' => 'in', 'Philippines' => 'ph', 'Vietnam' => 'vn',
        'Thailand' => 'th', 'Malaysia' => 'my', 'Nigeria' => 'ng', 'Ghana' => 'gh',
        'Kenya' => 'ke', 'South Africa' => 'za', 'Egypt' => 'eg', 'Morocco' => 'ma',
        'Brazil' => 'br', 'Mexico' => 'mx', 'Argentina' => 'ar', 'Colombia' => 'co',
        'Turkey' => 'tr', 'Russia' => 'ru', 'Ukraine' => 'ua', 'Australia' => 'au',
        'Japan' => 'jp', 'Korea' => 'kr', 'China' => 'cn', 'Pakistan' => 'pk',
        'Bangladesh' => 'bd', 'Saudi Arabia' => 'sa', 'UAE' => 'ae',
    ];

    public function index(Request $request)
    {
        // Per-product available + sold counts via correlated subqueries, so it
        // stays one query and works under MySQL's ONLY_FULL_GROUP_BY.
        $available = DB::table('log_items')
            ->selectRaw('COUNT(*)')
            ->whereColumn('log_items.log_product_id', 'log_products.id')
            ->where('log_items.status', 'available');

        $soldSub = DB::table('log_items')
            ->selectRaw('COUNT(*)')
            ->whereColumn('log_items.log_product_id', 'log_products.id')
            ->where('log_items.status', 'sold');

        $query = DB::table('log_products')
            ->leftJoin('log_categories', 'log_categories.id', '=', 'log_products.log_category_id')
            ->select('log_products.*')
            ->selectSub($available, 'stock')
            ->selectSub($soldSub, 'sold_count')
            ->selectRaw('log_categories.name AS category_name')
            ->selectRaw('log_categories.icon AS category_icon');

        // ── Filters ──────────────────────────────────────────────────
        if ($q = trim((string) $request->query('q'))) {
            $query->where('log_products.name', 'like', '%' . $q . '%');
        }

        if ($cat = $request->query('category')) {
            $query->where('log_products.log_category_id', (int) $cat);
        }

        $status = $request->query('status');   // active | hidden
        if ($status === 'active')  $query->where('log_products.is_active', true);
        if ($status === 'hidden')  $query->where('log_products.is_active', false);

        // ── Sort (default: lowest stock first, so the shelf that needs
        //    restocking is at the top) ─────────────────────────────────
        $sort = $request->query('sort', 'stock_asc');
        match ($sort) {
            'stock_desc' => $query->orderByDesc('stock'),
            'sold_desc'  => $query->orderByDesc('sold_count'),
            'price_desc' => $query->orderByDesc('log_products.price'),
            'price_asc'  => $query->orderBy('log_products.price'),
            'newest'     => $query->orderByDesc('log_products.id'),
            'name'       => $query->orderBy('log_products.name'),
            default      => $query->orderBy('stock')->orderByDesc('log_products.id'), // stock_asc
        };

        $products = $query->get();

        // ── Headline stats across the whole catalogue ────────────────
        $stats = [
            'products'  => DB::table('log_products')->count(),
            'active'    => DB::table('log_products')->where('is_active', true)->count(),
            'in_stock'  => DB::table('log_items')->where('status', 'available')->count(),
            'sold'      => DB::table('log_items')->where('status', 'sold')->count(),
            'revenue'   => (float) DB::table('log_orders')->where('status', 'delivered')->sum('total'),
            // Products at or below the low-stock line but not empty.
            'low'       => $products->filter(fn ($p) => $p->stock > 0 && $p->stock <= self::LOW_STOCK)->count(),
            'empty'     => $products->filter(fn ($p) => $p->stock === 0 && ! $p->pre_order)->count(),
        ];

        $categories = DB::table('log_categories')->orderBy('name')->get(['id', 'name']);

        return view('admin.logs.index', [
            'products'   => $products,
            'stats'      => $stats,
            'categories' => $categories,
            'filters'    => ['q' => $q ?? '', 'category' => $cat, 'status' => $status, 'sort' => $sort],
            'lowStock'   => self::LOW_STOCK,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image'] = $this->handleImage($request);

        DB::table('log_products')->insert(array_filter($data, fn ($v) => $v !== null) + [
            'slug'       => $this->uniqueSlug($data['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Product created.');
    }

    public function update(Request $request, int $product)
    {
        $data = $this->validated($request);

        // Only replace the image if a new one was uploaded; otherwise keep it.
        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImage($request);
        } else {
            unset($data['image']);
        }

        DB::table('log_products')->where('id', $product)->update($data + ['updated_at' => now()]);

        return back()->with('status', 'Product updated.');
    }

    /** Stores an uploaded thumbnail on the public disk, returns its path. */
    private function handleImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        // public disk → storage/app/public, exposed via `php artisan storage:link`
        return $request->file('image')->store('products', 'public');
    }

    /**
     * Load stock. Accepts a pasted block, an uploaded file, or both, and
     * splits into one item per account on a chosen delimiter.
     */
    public function addStock(Request $request, int $product)
    {
        $exists = DB::table('log_products')->where('id', $product)->exists();
        abort_unless($exists, 404);

        $request->validate([
            'paste'     => ['nullable', 'string'],
            'file'      => ['nullable', 'file', 'mimes:txt,csv', 'max:10240'],
            'delimiter' => ['required', Rule::in(['line', 'blank', 'comma'])],
        ]);

        $raw = (string) $request->input('paste', '');

        if ($request->hasFile('file')) {
            // Prepend the file so a paste can top it up in the same submit.
            $raw = $request->file('file')->get() . "\n" . $raw;
        }

        $items = $this->split($raw, $request->input('delimiter'));

        if (empty($items)) {
            return back()->withErrors(['paste' => 'Nothing to import — the input was empty.']);
        }

        $now  = now();
        $rows = array_map(function ($item) use ($product, $now) {
            return [
                'log_product_id' => $product,
                'content'        => $item['content'],
                'preview_url'    => $item['preview_url'],
                'label'          => $item['label'],
                'token'          => (string) \Illuminate\Support\Str::random(20),
                'status'         => 'available',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }, $items);

        // Chunked so a 50k-line paste doesn't build one enormous query.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('log_items')->insert($chunk);
        }

        return back()->with('status', count($items) . ' accounts added to stock.');
    }

    /** The stock items of one product, for the admin's manage panel. */
    public function items(int $product)
    {
        $items = DB::table('log_items')
            ->where('log_product_id', $product)
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'label', 'preview_url', 'status', 'content', 'sold_at']);

        return response()->json([
            'items' => $items->map(fn ($i) => [
                'id'      => $i->id,
                'label'   => $i->label,
                'preview' => $i->preview_url,
                'status'  => $i->status,
                'sold'    => $i->status === 'sold',
                'excerpt' => \Illuminate\Support\Str::limit(str_replace(["\n", "\r"], ' ', $i->content), 60),
                // Full content only for unsold items — a sold one is the
                // customer's, and the admin has no reason to see it back.
                'content' => $i->status === 'sold' ? null : $i->content,
            ]),
        ]);
    }

    /** Edit one item — label, preview URL, and the credential content. */
    public function updateItem(Request $request, int $item)
    {
        $data = $request->validate([
            'label'       => ['nullable', 'string', 'max:191'],
            'preview_url' => ['nullable', 'url', 'max:2000'],
            'content'     => ['required', 'string'],
        ]);

        $row = DB::table('log_items')->where('id', $item)->first();
        abort_unless($row, 404);

        // A sold item is a customer's receipt — its content must not change.
        if ($row->status === 'sold') {
            return back()->withErrors(['item' => 'Sold items cannot be edited.']);
        }

        DB::table('log_items')->where('id', $item)->update([
            'label'       => $data['label'] ?: null,
            'preview_url' => $data['preview_url'] ?: null,
            'content'     => $data['content'],
            'updated_at'  => now(),
        ]);

        return back()->with('status', 'Account updated.');
    }

    /** Remove a single unsold item. */
    public function destroyItem(int $item)
    {
        $row = DB::table('log_items')->where('id', $item)->first();
        abort_unless($row, 404);

        if ($row->status === 'sold') {
            return back()->withErrors(['item' => 'Sold items cannot be deleted.']);
        }

        DB::table('log_items')->where('id', $item)->delete();

        return back()->with('status', 'Account removed from stock.');
    }

    public function destroy(int $product)
    {
        // Only ever remove unsold stock — a sold item is someone's receipt.
        DB::table('log_items')->where('log_product_id', $product)->where('status', 'available')->delete();
        DB::table('log_products')->where('id', $product)->update(['is_active' => false, 'updated_at' => now()]);

        return back()->with('status', 'Product hidden and unsold stock cleared.');
    }

    // ═══════════════════════ Internals ═══════════════════════

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:191'],
            'log_category_id'  => ['required', 'integer', 'exists:log_categories,id'],
            'country'          => ['nullable', 'string', 'max:60'],
            'image'            => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'instructions'     => ['nullable', 'string', 'max:4000'],
            'price'            => ['required', 'numeric', 'min:0'],
            'max_per_order'    => ['required', 'integer', 'min:1', 'max:1000'],
            'pre_order'        => ['nullable', 'boolean'],
            'previewable'      => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
            'sort'             => ['nullable', 'integer'],
        ]);

        // The flag is derived from the country, never typed — admins shouldn't
        // need to know ISO codes. Blank if the country isn't recognised.
        $data['flag'] = $this->flagFor($data['country'] ?? null);

        // image is a file, not a column value at this point.
        unset($data['image']);

        // Keep the display string in step with the chosen category, so the
        // storefront (which reads `category`) needs no join to show a label.
        $data['category'] = DB::table('log_categories')->where('id', $data['log_category_id'])->value('name');

        return $data;
    }

    /**
     * One entry per account, each with optional preview URL and label.
     *
     *   line  — each line is an account. A preview URL may be appended after
     *           a pipe:  user:pass | https://facebook.com/user
     *   blank — accounts separated by a blank line. A line inside the block
     *           starting http(s) is taken as that account's preview URL.
     *   comma — CSV rows: content, preview_url (2nd column optional).
     *
     * @return array<int, array{content: string, preview_url: ?string, label: ?string}>
     */
    private function split(string $raw, string $delimiter): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));

        if ($raw === '') {
            return [];
        }

        $out = [];

        if ($delimiter === 'comma') {
            foreach (preg_split('/\n+/', $raw) as $line) {
                $cols = str_getcsv($line);
                $content = trim($cols[0] ?? '');
                if ($content === '') {
                    continue;
                }
                $url = isset($cols[1]) ? trim($cols[1]) : null;
                $out[] = $this->makeItem($content, $url);
            }
            return $out;
        }

        if ($delimiter === 'blank') {
            foreach (preg_split('/\n\s*\n/', $raw) as $block) {
                $block = trim($block);
                if ($block === '') {
                    continue;
                }
                // Pull a URL line out of the block, keep the rest as content.
                $url = null;
                $lines = [];
                foreach (explode("\n", $block) as $ln) {
                    if ($url === null && preg_match('~^https?://\S+$~i', trim($ln))) {
                        $url = trim($ln);
                    } else {
                        $lines[] = $ln;
                    }
                }
                $out[] = $this->makeItem(trim(implode("\n", $lines)), $url);
            }
            return $out;
        }

        // line mode: content | url   (the pipe and url are optional)
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $url = null;
            if (str_contains($line, '|')) {
                [$content, $maybeUrl] = array_map('trim', explode('|', $line, 2));
                if (preg_match('~^https?://~i', $maybeUrl)) {
                    $url = $maybeUrl;
                    $line = $content;
                }
            }
            $out[] = $this->makeItem($line, $url);
        }

        return $out;
    }

    /** Normalises one parsed account and derives a human label from the URL. */
    private function makeItem(string $content, ?string $url): array
    {
        $url = $url ?: null;

        // Label = the last path segment of the URL (the username), if any.
        $label = null;
        if ($url) {
            $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $label = $path !== '' ? \Illuminate\Support\Str::afterLast($path, '/') : parse_url($url, PHP_URL_HOST);
        }

        return ['content' => $content, 'preview_url' => $url, 'label' => $label];
    }

    /** Country name → ISO 3166-1 alpha-2, for the flag. Null if unknown. */
    private function flagFor(?string $country): ?string
    {
        if (! $country) {
            return null;
        }

        return self::COUNTRY_ISO[trim($country)] ?? null;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;

        while (DB::table('log_products')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
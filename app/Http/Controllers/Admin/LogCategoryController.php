<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin CRUD for the category list products are grouped under.
 */
class LogCategoryController extends Controller
{
    public function index()
    {
        // Each category with how many active products sit under it.
        $count = DB::table('log_products')
            ->selectRaw('COUNT(*)')
            ->whereColumn('log_products.log_category_id', 'log_categories.id')
            ->where('log_products.is_active', true);

        $categories = DB::table('log_categories')
            ->select('log_categories.*')
            ->selectSub($count, 'products')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::table('log_categories')->insert($data + [
            'slug'       => $this->uniqueSlug($data['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, int $category)
    {
        $data = $this->validated($request, $category);

        DB::table('log_categories')->where('id', $category)->update($data + ['updated_at' => now()]);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(int $category)
    {
        $inUse = DB::table('log_products')->where('log_category_id', $category)->exists();

        if ($inUse) {
            // Never orphan products — hide it instead of deleting.
            DB::table('log_categories')->where('id', $category)->update(['is_active' => false, 'updated_at' => now()]);

            return back()->with('status', 'Category is in use — hidden instead of deleted.');
        }

        DB::table('log_categories')->where('id', $category)->delete();

        return back()->with('status', 'Category deleted.');
    }

    // ── Internals ───────────────────────────────────────────────────

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'name'      => ['required', 'string', 'max:60', Rule::unique('log_categories', 'name')->ignore($ignore)],
            'icon'      => ['nullable', 'string', 'max:40'],
            'sort'      => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;

        while (DB::table('log_categories')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
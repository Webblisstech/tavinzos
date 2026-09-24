<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Admin management of dashboard sliding banners. */
class BannerController extends Controller
{
    public function index()
    {
        $banners = DB::table('banners')->orderBy('sort')->orderByDesc('id')->get();
        return view('admin.banners.index', ['banners' => $banners]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:4096'],   // up to 4MB
            'link'  => ['nullable', 'url', 'max:500'],
            'title' => ['nullable', 'string', 'max:120'],
            'sort'  => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('image')->store('banners', 'public');

        DB::table('banners')->insert([
            'image'      => $path,
            'link'       => $data['link'] ?? null,
            'title'      => $data['title'] ?? null,
            'sort'       => $data['sort'] ?? 0,
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', __('Banner uploaded.'));
    }

    public function toggle(int $id)
    {
        $b = DB::table('banners')->where('id', $id)->first();
        abort_unless($b, 404);
        DB::table('banners')->where('id', $id)->update(['active' => ! $b->active, 'updated_at' => now()]);
        return back()->with('status', __('Banner updated.'));
    }

    public function destroy(int $id)
    {
        $b = DB::table('banners')->where('id', $id)->first();
        abort_unless($b, 404);

        // Remove the file too.
        try { Storage::disk('public')->delete($b->image); } catch (\Throwable) {}

        DB::table('banners')->where('id', $id)->delete();
        return back()->with('status', __('Banner removed.'));
    }
}
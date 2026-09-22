<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves uploaded media straight from storage/app/public, bypassing the
 * public/storage symlink — which DirectAdmin and some shared hosts don't
 * follow. The path is sanitised so it can never climb out of the disk.
 */
class MediaController extends Controller
{
    public function show(Request $request, string $path)
    {
        // Block traversal: no "..", no leading slash, stay inside the disk.
        $path = ltrim(str_replace('..', '', $path), '/');

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        // Long cache — uploaded files are content-addressed by random name and
        // never change in place, so a year is safe.
        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
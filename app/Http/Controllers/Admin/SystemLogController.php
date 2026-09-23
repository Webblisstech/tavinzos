<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Reads the Laravel log so an admin can see errors happening in the system —
 * parsed into entries, filterable by level, newest first. Read-only.
 */
class SystemLogController extends Controller
{
    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function index(Request $request)
    {
        $level = strtolower((string) $request->query('level', 'all'));
        $q     = trim((string) $request->query('q', ''));

        $path = storage_path('logs/laravel.log');
        $entries = collect();
        $sizeKb = 0;

        if (File::exists($path)) {
            $sizeKb = (int) round(File::size($path) / 1024);
            $entries = $this->parse($path);
        }

        // Filter by level.
        if (in_array($level, self::LEVELS, true)) {
            $entries = $entries->filter(fn ($e) => $e['level'] === $level);
        }
        // Filter by search text.
        if ($q !== '') {
            $entries = $entries->filter(fn ($e) =>
                str_contains(mb_strtolower($e['message']), mb_strtolower($q)));
        }

        // Newest first, cap at 300 shown.
        $entries = $entries->reverse()->take(300)->values();

        // Counts per level (for the filter chips), from the full set.
        $counts = $this->parse($path)->groupBy('level')->map->count();

        return view('admin.logs.system', [
            'entries' => $entries,
            'level'   => $level,
            'q'       => $q,
            'counts'  => $counts,
            'levels'  => self::LEVELS,
            'sizeKb'  => $sizeKb,
            'exists'  => File::exists($path),
        ]);
    }

    /** Clear the log file (truncate). */
    public function clear(Request $request)
    {
        $path = storage_path('logs/laravel.log');
        if (File::exists($path)) {
            File::put($path, '');
        }
        return back()->with('status', __('Log cleared.'));
    }

    /**
     * Parse the log into entries. Laravel lines start with a timestamp like
     * [2026-09-23 10:21:00] production.ERROR: message … followed by an
     * optional multi-line stack trace, which we attach to the entry.
     */
    private function parse(string $path)
    {
        // Read only the tail of large files (last ~2MB) to stay fast.
        $max = 2 * 1024 * 1024;
        $size = File::size($path);
        $content = $size > $max
            ? file_get_contents($path, false, null, $size - $max)
            : File::get($path);

        $lines = preg_split('/\r?\n/', $content);
        $entries = collect();
        $current = null;

        $head = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s+([a-z0-9_-]+)\.([A-Z]+):\s?(.*)$/i';

        foreach ($lines as $line) {
            if (preg_match($head, $line, $m)) {
                if ($current) {
                    $entries->push($current);
                }
                $current = [
                    'time'    => $m[1],
                    'env'     => $m[2],
                    'level'   => strtolower($m[3]),
                    'message' => $m[4],
                    'trace'   => '',
                ];
            } elseif ($current !== null && trim($line) !== '') {
                // Continuation / stack-trace line.
                $current['trace'] .= ($current['trace'] === '' ? '' : "\n") . $line;
            }
        }
        if ($current) {
            $entries->push($current);
        }

        return $entries;
    }
}
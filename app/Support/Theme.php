<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads the admin's theme settings and produces the values the head partial
 * needs: a full brand color scale derived from one primary hex, the chosen
 * font, base font size, and corner radius. Cached with the rest of settings.
 */
class Theme
{
    /** All theme values, ready for the <head>. */
    public static function tokens(): array
    {
        $primary = self::hex(self::setting('theme.primary', '#D91F2C'), '#D91F2C');
        $accent  = self::hex(self::setting('theme.accent', '#F5A623'), '#F5A623');

        $font = (string) self::setting('theme.font', 'Bricolage Grotesque');
        $scale = match ((string) self::setting('theme.font_scale', 'normal')) {
            'small' => '15px',
            'large' => '17.5px',
            default => '16px',
        };
        $radius = match ((string) self::setting('theme.radius', 'normal')) {
            'tight' => '0.5rem',
            'round' => '1.25rem',
            default => '0.85rem',
        };

        return [
            'brand'        => self::scale($primary),
            'primary'      => $primary,
            'accent'       => $accent,
            'accent_hover' => self::shade($accent, -0.10),
            'accent_active'=> self::shade($accent, -0.18),
            'accent_text'  => self::readableOn($accent),
            'font'         => $font,
            'font_stack'   => "'{$font}', 'Trebuchet MS', system-ui, sans-serif",
            'font_url'     => 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font) . ':wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap',
            'scale'        => $scale,
            'radius'       => $radius,
        ];
    }

    /** Darken (t<0) or lighten (t>0) a hex color by a fraction. */
    private static function shade(string $hex, float $t): string
    {
        [$r, $g, $b] = self::rgb($hex);
        if ($t < 0) {
            $f = 1 + $t;
            return sprintf('#%02x%02x%02x', (int) round($r * $f), (int) round($g * $f), (int) round($b * $f));
        }
        return sprintf('#%02x%02x%02x',
            (int) round($r + (255 - $r) * $t),
            (int) round($g + (255 - $g) * $t),
            (int) round($b + (255 - $b) * $t));
    }

    /** Black or white text, whichever reads better on the given background. */
    private static function readableOn(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);
        // Perceived luminance.
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $lum > 0.6 ? '#14100f' : '#ffffff';
    }

    /** Build a 50–900 scale by tinting/shading a base hex. */
    private static function scale(string $hex): array
    {
        [$r, $g, $b] = self::rgb($hex);
        // Lightness targets per step (mix with white above the base, black below).
        $steps = [
            50 => 0.95, 100 => 0.88, 200 => 0.74, 300 => 0.55,
            400 => 0.30, 500 => 0.12, 600 => 0.0,
            700 => -0.14, 800 => -0.24, 900 => -0.34,
        ];
        $out = [];
        foreach ($steps as $k => $t) {
            if ($t >= 0) {
                // mix toward white
                $nr = $r + (255 - $r) * $t;
                $ng = $g + (255 - $g) * $t;
                $nb = $b + (255 - $b) * $t;
            } else {
                // mix toward black
                $f = 1 + $t; // 0.86 … 0.66
                $nr = $r * $f; $ng = $g * $f; $nb = $b * $f;
            }
            $out[$k] = sprintf('#%02x%02x%02x', (int) round($nr), (int) round($ng), (int) round($nb));
        }
        return $out;
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Validate a hex color, fall back if malformed. */
    private static function hex(?string $value, string $fallback): string
    {
        $v = trim((string) $value);
        return preg_match('/^#?[0-9a-fA-F]{6}$/', $v) ? '#' . ltrim($v, '#') : $fallback;
    }

    private static function setting(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember('settings', 300, function () {
            try {
                return DB::table('settings')->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                        'int' => (int) $r->value, 'float' => (float) $r->value,
                        'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                    }])->all();
            } catch (\Throwable) { return []; }
        });
        return $all[$key] ?? $default;
    }
}
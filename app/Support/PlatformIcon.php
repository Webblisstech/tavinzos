<?php

namespace App\Support;

/**
 * Guesses a platform icon key from a product's name/category text, so external
 * products with no image still show a recognizable logo. The returned key maps
 * to resources/views/partials/brand-icon.blade.php.
 */
class PlatformIcon
{
    /** Keyword → brand-icon key. First match wins, so order matters. */
    private const MAP = [
        'facebook'  => 'facebook', ' fb '  => 'facebook', 'faceb00k' => 'facebook',
        'instagram' => 'instagram', ' ig '  => 'instagram', 'insta' => 'instagram',
        'tiktok'    => 'tiktok',
        'twitter'   => 'twitter', ' x '   => 'x', 'x account' => 'x',
        'telegram'  => 'telegram', 'tele ' => 'telegram',
        'discord'   => 'discord',
        'snapchat'  => 'snapchat', 'snap ' => 'snapchat',
        'linkedin'  => 'linkedin',
        'youtube'   => 'youtube', 'yt '   => 'youtube',
        'twitch'    => 'twitch',
        'reddit'    => 'reddit',
        'quora'     => 'quora',
        'pinterest' => 'quora',      // no pinterest glyph; quora tag is neutral
        'netflix'   => 'netflix',
        'gmail'     => 'google', 'google' => 'google',
        'outlook'   => 'mail', 'hotmail' => 'mail', 'email' => 'mail', 'mail' => 'mail',
        'vpn'       => 'vpn',
        'tradingview' => 'google', 'capcut' => 'tiktok',   // closest neutral marks
        'dating'    => 'facebook',
    ];

    /** Return an icon key for the given product text, or null (falls back to tag). */
    public static function forText(?string ...$texts): ?string
    {
        $hay = ' ' . mb_strtolower(implode(' ', array_filter($texts))) . ' ';

        foreach (self::MAP as $needle => $icon) {
            if (str_contains($hay, $needle)) {
                return $icon;
            }
        }
        return null;
    }
}
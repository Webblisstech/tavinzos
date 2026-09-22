<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads the number_overrides rules (cached) and answers three questions the
 * NumberController asks: is a service blocked, is a country blocked, and what
 * extra price adjustment applies. Kept tiny and static so presenters can call
 * it cheaply per-row.
 */
class NumberOverrides
{
    private const KEY = 'number_overrides';

    /** All active rules, cached for 5 minutes. */
    private static function rules(): array
    {
        return Cache::remember(self::KEY, 300, function () {
            try {
                return DB::table('number_overrides')->where('active', true)
                    ->get()->map(fn ($r) => (array) $r)->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }
/**
 * Is this specific price tier blocked? Target format: "countryId:serviceCode:tier",
 * where tier is the upstream route handle, or the tier's index when it has none.
 */
public static function tierBlocked(int $country, ?string $service, string|int|null $tier): bool
{
    if ($tier === null || $service === null) {
        return false;
    }

    $key = mb_strtolower($country . ':' . trim($service) . ':' . trim((string) $tier));

    foreach (self::rules() as $r) {
        if ($r['type'] === 'block_tier'
            && mb_strtolower(trim((string) $r['target'])) === $key) {
            return true;
        }
    }
    return false;
}
    /** Is this service blocked? Matches its name OR code, case-insensitive. */
    public static function serviceBlocked(?string $name, ?string $code = null): bool
    {
        $n = mb_strtolower(trim((string) $name));
        $c = mb_strtolower(trim((string) $code));
        foreach (self::rules() as $r) {
            if ($r['type'] === 'block_service') {
                $t = mb_strtolower(trim((string) $r['target']));
                if ($t !== '' && ($t === $n || $t === $c)) {
                    return true;
                }
            }
        }
        return false;
    }

    /** Is this country blocked? Matches its name, case-insensitive. */
    public static function countryBlocked(?string $name): bool
    {
        $n = mb_strtolower(trim((string) $name));
        foreach (self::rules() as $r) {
            if ($r['type'] === 'block_country'
                && mb_strtolower(trim((string) $r['target'])) === $n) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extra price adjustment to apply on top of the base markup, for a given
     * service and/or country. Global (price_all) applies always; per-service
     * and per-country stack on matches. Returns the adjusted price.
     */
    public static function adjustPrice(float $price, ?string $service = null, ?string $country = null): float
    {
        $svc = mb_strtolower(trim((string) $service));
        $cty = mb_strtolower(trim((string) $country));

        foreach (self::rules() as $r) {
            if (! str_starts_with($r['type'], 'price')) {
                continue;
            }
            $matches = match ($r['type']) {
                'price_all'     => true,
                'price_service' => mb_strtolower(trim((string) $r['target'])) === $svc && $svc !== '',
                'price_country' => mb_strtolower(trim((string) $r['target'])) === $cty && $cty !== '',
                default         => false,
            };
            if (! $matches) {
                continue;
            }
            $price = $r['price_mode'] === 'flat'
                ? $price + (float) $r['price_value']
                : $price * (1 + (float) $r['price_value'] / 100);
        }

        return max(0, $price);
    }
}
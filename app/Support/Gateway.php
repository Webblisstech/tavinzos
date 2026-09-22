<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Gateway credentials, admin-first. Returns the value an admin set in settings
 * if present, otherwise falls back to the .env/config value. So keys can live
 * in .env (more secure) OR be overridden from the admin panel.
 */
class Gateway
{
    public static function webblissBase(): string
    {
        return self::val('gateway.webbliss_base', (string) config('services.webblisspay.base'));
    }
    public static function webblissSecret(): string
    {
        return self::val('gateway.webbliss_secret', (string) config('services.webblisspay.secret'));
    }

    public static function paymentPointBase(): string
    {
        return self::val('gateway.paymentpoint_base', (string) config('services.paymentpoint.base'));
    }
    public static function paymentPointToken(): string
    {
        return self::val('gateway.paymentpoint_token', (string) config('services.paymentpoint.token'));
    }
    public static function paymentPointKey(): string
    {
        return self::val('gateway.paymentpoint_key', (string) config('services.paymentpoint.api_key'));
    }
    public static function paymentPointSecret(): string
    {
        return self::val('gateway.paymentpoint_secret', (string) config('services.paymentpoint.secret'));
    }

    public static function numbersKey(): string
    {
        return self::val('gateway.numbers_key', (string) config('services.numbers.key'));
    }
    public static function numbersUsaBase(): string
    {
        return self::val('gateway.numbers_usa_base', (string) config('services.numbers.usa.base'));
    }
    public static function numbersGlobalBase(): string
    {
        return self::val('gateway.numbers_global_base', (string) config('services.numbers.global.base'));
    }

    public static function shopviaKey(): string
    {
        return self::val('gateway.shopvia_key', (string) env('SHOPVIA_KEY', ''));
    }
    public static function shopviaBase(): string
    {
        return self::val('gateway.shopvia_base', (string) env('SHOPVIA_BASE', 'https://shopviaclone22.com/api'));
    }

    /** Setting value if non-empty, else the fallback. */
    private static function val(string $key, string $fallback): string
    {
        $all = Cache::remember('settings', 300, function () {
            try {
                return DB::table('settings')->pluck('value', 'key')->all();
            } catch (\Throwable) { return []; }
        });
        $v = trim((string) ($all[$key] ?? ''));
        return $v !== '' ? $v : $fallback;
    }
}
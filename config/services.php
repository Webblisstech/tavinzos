<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtual numbers
    |--------------------------------------------------------------------------
    |
    | Upstream credentials and retail pricing. Nothing here is ever rendered
    | or returned to a customer — keep every value in .env so the supplier's
    | hosts never land in version control.
    |
    */

    'numbers' => [

        'key' => env('NUMBERS_API_KEY'),

        'usa' => [
            'base' => env('NUMBERS_USA_BASE'),
            'poll' => 15,   // matches the upstream 15s check cache
        ],

        'global' => [
            'base' => env('NUMBERS_GLOBAL_BASE'),
            'poll' => 4,
        ],

        'timeout'     => 20,
        'cancel_lock' => 180,   // seconds before a cancel is allowed

        'cache' => [
            'countries' => 1800,
            'services'  => 240,
            // Longer than the upstream 5-minute quote on purpose: the token
            // only identifies the customer's choice. If the price behind it
            // has lapsed by the time they buy, the controller re-quotes.
            'quote'     => 1800,
            'ref'       => 86400,
        ],

        /*
        | Retail markup. Pick ONE mode:
        |   percent → value is a % added to cost        (35 = cost + 35%)
        |   flat    → value is a fixed gain per number, in the display
        |             currency, added after conversion   (300 = cost + ₦300)
        */
        'markup' => [
            'mode'  => env('NUMBERS_MARKUP_MODE', 'percent'),   // percent | flat
            'value' => env('NUMBERS_MARKUP_VALUE', 35),
        ],

        'currency' => [
            'code'     => env('NUMBERS_CURRENCY', 'NGN'),
            'symbol'   => env('NUMBERS_CURRENCY_SYMBOL', '₦'),
            'usd_rate' => env('NUMBERS_USD_RATE', 1600),        // 1 for USD
            'decimals' => env('NUMBERS_CURRENCY_DECIMALS', 0),
        ],
    ],


    'webblisspay' => [
        'base'       => env('WEBBLISSPAY_BASE', 'https://webblisspay.com/api/v1'),
        'secret'     => env('WEBBLISSPAY_SECRET'),
        'currency'   => env('NUMBERS_CURRENCY', 'NGN'),
    ],

    // Dedicated virtual bank accounts. The endpoint is POST /api/virtual-account
    // on the WebBlissPay DOMAIN ROOT (not the /api/v1 checkout base). We derive
    // the scheme+host from WEBBLISSPAY_BASE, or set VIRTUAL_ACCOUNT_BASE to
    // override. The controller appends /api/virtual-account.
    // PaymentPoint — a second virtual-account provider some customers use.
    'paymentpoint' => [
        'base'    => env('PAYMENTPOINT_BASE', 'https://api.paymentpoint.co'),
        'token'   => env('PAYMENTPOINT_TOKEN'),   // Bearer
        'api_key' => env('PAYMENTPOINT_API_KEY'),
        'secret'  => env('PAYMENTPOINT_SECRET'),  // webhook signing secret
    ],

    'virtualaccount' => [
        'base'  => env('VIRTUAL_ACCOUNT_BASE', preg_replace('#(https?://[^/]+).*#', '$1', env('WEBBLISSPAY_BASE', 'https://webblisspay.com'))),
        'token' => env('VIRTUAL_ACCOUNT_TOKEN', env('WEBBLISSPAY_SECRET')),
    ],

];
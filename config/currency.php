<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This value is the default currency that will be used when no currency
    | preference is set for the user or in the session.
    |
    */
    'default' => env('DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies that are available in the application.
    | You can enable or disable currencies here.
    |
    */
    'supported' => [
        'EUR', // Euro
        'USD', // US Dollar
        'GBP', // British Pound
        'CHF', // Swiss Franc
        'CAD', // Canadian Dollar
        'AUD', // Australian Dollar
        'JPY', // Japanese Yen
        'CNY', // Chinese Yuan
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Exchange Rates
    |--------------------------------------------------------------------------
    |
    | If you want to support currency conversion, you can configure
    | exchange rates here or use an external service.
    |
    */
    'exchange_rates' => [
        'EUR' => 1.00,
        'USD' => env('EXCHANGE_RATE_USD', 1.08),
        'GBP' => env('EXCHANGE_RATE_GBP', 0.86),
        'CHF' => env('EXCHANGE_RATE_CHF', 0.98),
        'CAD' => env('EXCHANGE_RATE_CAD', 1.47),
        'AUD' => env('EXCHANGE_RATE_AUD', 1.66),
        'JPY' => env('EXCHANGE_RATE_JPY', 162.45),
        'CNY' => env('EXCHANGE_RATE_CNY', 7.84),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Cache
    |--------------------------------------------------------------------------
    |
    | Settings for caching currency preferences and exchange rates.
    |
    */
    'cache' => [
        'enabled' => env('CURRENCY_CACHE_ENABLED', true),
        'ttl' => env('CURRENCY_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'currency_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Display Options
    |--------------------------------------------------------------------------
    |
    | Configure how currencies are displayed in the application.
    |
    */
    'display' => [
        'show_code' => true,
        'show_symbol' => true,
        'show_flag' => true,
    ],
];

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Warming Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the cache warming functionality of the
    | application. When enabled, the middleware will redirect users to
    | a cache warming page when the cache is cold.
    |
    */

    'enabled' => env('CACHE_WARMING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Skip Cookie Name
    |--------------------------------------------------------------------------
    |
    | The name of the cookie that allows users to skip cache warming.
    |
    */

    'skip_cookie' => 'skip_cache_warming',

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Route
    |--------------------------------------------------------------------------
    |
    | The route to redirect users to when cache warming is needed.
    |
    */

    'route' => '/cache-warming',

    /*
    |--------------------------------------------------------------------------
    | Skip Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should never trigger cache warming redirect.
    |
    */

    'skip_routes' => [
        '/cache-warming',
        '/api/cache-warming',
        '/health',
        '/ping',
        '/admin',
        '/livewire',
        '/webhooks',
        '/locale',
        '/currency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to keep cached data before it expires.
    |
    */

    'ttl' => env('CACHE_WARMING_TTL', 604800), // 7 days default

    /*
    |--------------------------------------------------------------------------
    | Critical Cache Threshold
    |--------------------------------------------------------------------------
    |
    | The percentage of cache keys that must be cold before redirecting
    | to the cache warming page.
    |
    */

    'cold_threshold' => 0.7, // 70%
];

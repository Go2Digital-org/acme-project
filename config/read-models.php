<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Read Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for read models, caching, and CQRS implementation.
    |
    */

    'caching' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Driver
        |--------------------------------------------------------------------------
        |
        | The cache driver to use for read models. Should support tagging
        | for efficient cache invalidation.
        |
        */
        'driver' => env('READ_MODEL_CACHE_DRIVER', env('CACHE_DRIVER', 'redis')),

        /*
        |--------------------------------------------------------------------------
        | Default TTL
        |--------------------------------------------------------------------------
        |
        | Default cache time-to-live in seconds for read models.
        |
        */
        'default_ttl' => (int) env('READ_MODEL_CACHE_TTL', 3600), // 1 hour

        /*
        |--------------------------------------------------------------------------
        | Cache Prefix
        |--------------------------------------------------------------------------
        |
        | Prefix for all read model cache keys to avoid conflicts.
        |
        */
        'prefix' => env('READ_MODEL_CACHE_PREFIX', 'readmodel'),

        /*
        |--------------------------------------------------------------------------
        | TTL by Read Model Type
        |--------------------------------------------------------------------------
        |
        | Specific TTL values for different read model types.
        |
        */
        'ttl' => [
            'campaign_analytics' => (int) env('READ_MODEL_CAMPAIGN_TTL', 1800), // 30 minutes
            'donation_reports' => (int) env('READ_MODEL_DONATION_TTL', 900),    // 15 minutes
            'organization_dashboard' => (int) env('READ_MODEL_ORG_TTL', 3600),  // 1 hour
            'user_profile' => (int) env('READ_MODEL_USER_TTL', 1800),           // 30 minutes
        ],
    ],

    'repositories' => [
        /*
        |--------------------------------------------------------------------------
        | Repository Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for read model repositories.
        |
        */
        'campaign_analytics' => [
            'cache_enabled' => env('READ_MODEL_CAMPAIGN_CACHE_ENABLED', true),
            'batch_size' => (int) env('READ_MODEL_CAMPAIGN_BATCH_SIZE', 100),
        ],

        'donation_reports' => [
            'cache_enabled' => env('READ_MODEL_DONATION_CACHE_ENABLED', true),
            'batch_size' => (int) env('READ_MODEL_DONATION_BATCH_SIZE', 50),
        ],

        'organization_dashboard' => [
            'cache_enabled' => env('READ_MODEL_ORG_CACHE_ENABLED', true),
            'batch_size' => (int) env('READ_MODEL_ORG_BATCH_SIZE', 25),
        ],
    ],

    'invalidation' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Invalidation
        |--------------------------------------------------------------------------
        |
        | Configuration for automatic cache invalidation on domain events.
        |
        */
        'enabled' => env('READ_MODEL_INVALIDATION_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Event Listeners
        |--------------------------------------------------------------------------
        |
        | Enable/disable specific event listeners.
        |
        */
        'listeners' => [
            'campaign_events' => env('READ_MODEL_CAMPAIGN_EVENTS_ENABLED', true),
            'donation_events' => env('READ_MODEL_DONATION_EVENTS_ENABLED', true),
            'organization_events' => env('READ_MODEL_ORG_EVENTS_ENABLED', true),
            'user_events' => env('READ_MODEL_USER_EVENTS_ENABLED', true),
        ],
    ],

    'refresh' => [
        /*
        |--------------------------------------------------------------------------
        | Refresh Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for read model refresh commands and scheduling.
        |
        */
        'queue' => env('READ_MODEL_REFRESH_QUEUE', 'read-models'),

        /*
        |--------------------------------------------------------------------------
        | Batch Processing
        |--------------------------------------------------------------------------
        |
        | Configuration for batch processing during refresh operations.
        |
        */
        'batch_size' => (int) env('READ_MODEL_REFRESH_BATCH_SIZE', 100),
        'delay_between_batches' => (int) env('READ_MODEL_REFRESH_DELAY', 1), // seconds

        /*
        |--------------------------------------------------------------------------
        | Scheduled Refresh
        |--------------------------------------------------------------------------
        |
        | Enable automatic scheduled refresh of read models.
        |
        */
        'scheduled' => [
            'enabled' => env('READ_MODEL_SCHEDULED_REFRESH_ENABLED', false),
            'cron' => env('READ_MODEL_SCHEDULED_REFRESH_CRON', '0 */6 * * *'), // Every 6 hours
        ],
    ],

    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Monitoring & Metrics
        |--------------------------------------------------------------------------
        |
        | Configuration for read model monitoring and performance metrics.
        |
        */
        'enabled' => env('READ_MODEL_MONITORING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Performance Logging
        |--------------------------------------------------------------------------
        |
        | Log slow read model operations and cache misses.
        |
        */
        'log_slow_queries' => env('READ_MODEL_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => (int) env('READ_MODEL_SLOW_QUERY_THRESHOLD', 1000), // milliseconds

        /*
        |--------------------------------------------------------------------------
        | Cache Hit Ratio Tracking
        |--------------------------------------------------------------------------
        |
        | Track cache hit ratios for optimization.
        |
        */
        'track_hit_ratio' => env('READ_MODEL_TRACK_HIT_RATIO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for development and testing environments.
    |
    */
    'development' => [
        'disable_cache' => env('READ_MODEL_DISABLE_CACHE', false),
        'force_refresh' => env('READ_MODEL_FORCE_REFRESH', false),
        'debug_cache_keys' => env('READ_MODEL_DEBUG_CACHE_KEYS', false),
    ],
];

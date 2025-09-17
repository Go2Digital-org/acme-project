<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Test Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for all test suites to ensure consistency
    | and optimal performance across unit, integration, feature, and browser tests.
    |
    */

    'parallel' => [
        'enabled' => env('PEST_PARALLEL', true),
        'processes' => env('PEST_PROCESSES', 4),
        'worker_id' => env('PARALLEL_WORKER_ID', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),

        'mysql' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'acme_corp_csr_test'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'root'),

            // Connection pool settings
            'pool' => [
                'min' => env('DB_POOL_MIN', 2),
                'max' => env('DB_POOL_MAX', 10),
            ],

            // Timeout settings
            'timeouts' => [
                'connection' => 5,
                'lock_wait' => env('DB_LOCK_TIMEOUT', 5),
                'statement' => 30,
            ],

            // Deadlock handling
            'deadlock' => [
                'max_retries' => env('DB_DEADLOCK_RETRIES', 3),
                'retry_delay' => 100000, // microseconds
            ],

            // Performance optimizations
            'optimizations' => [
                'foreign_key_checks' => false,
                'unique_checks' => false,
                'autocommit' => true,
                'flush_log_at_commit' => 2,
                'sync_binlog' => 0,
            ],
        ],

        'sqlite' => [
            'database' => ':memory:',
            'foreign_key_constraints' => false,
            'journal_mode' => 'MEMORY',
            'synchronous' => 'OFF',
            'temp_store' => 'MEMORY',
            'cache_size' => 10000,
            'page_size' => 4096,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Suite Specific Settings
    |--------------------------------------------------------------------------
    */

    'suites' => [
        'unit' => [
            'database' => 'sqlite', // Use in-memory SQLite for speed
            'parallel' => true,
            'processes' => 8, // More processes for unit tests
            'use_transactions' => false,
        ],

        'integration' => [
            'database' => 'mysql',
            'parallel' => true,
            'processes' => 4,
            'use_transactions' => true,
            'use_lazy_refresh' => true,
        ],

        'feature' => [
            'database' => 'mysql',
            'parallel' => true,
            'processes' => 4,
            'use_transactions' => true,
        ],

        'browser' => [
            'database' => 'mysql',
            'parallel' => false, // Browser tests should run sequentially
            'use_transactions' => false, // Use truncation for browser tests
            'use_database_truncation' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'default' => 'array',
        'prefix' => env('CACHE_PREFIX', 'test_'),
        'ttl' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'default' => 'sync',
        'sync_timeout' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'memory_limit' => '2G',
        'max_execution_time' => 300,
        'enable_query_log' => env('LOG_QUERIES', false),
        'slow_query_threshold' => 100, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Management
    |--------------------------------------------------------------------------
    */

    'schema' => [
        'cache_migrations' => true,
        'dump_path' => database_path('schema/mysql-schema.sql'),
        'use_dump' => file_exists(database_path('schema/mysql-schema.sql')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Factories and Seeders
    |--------------------------------------------------------------------------
    */

    'factories' => [
        'chunk_size' => 500, // Batch insert size
        'use_lazy_attributes' => true,
    ],

    'seeders' => [
        'chunk_size' => 1000,
        'disable_model_events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Testing
    |--------------------------------------------------------------------------
    */

    'browser' => [
        'driver' => env('BROWSER_DRIVER', 'playwright'),
        'headless' => env('BROWSER_HEADLESS', true),
        'timeout' => env('BROWSER_TIMEOUT', 30000),
        'slow_mo' => env('BROWSER_SLOW_MO', 0),
        'devtools' => env('BROWSER_DEVTOOLS', false),

        'viewport' => [
            'width' => env('BROWSER_VIEWPORT_WIDTH', 1920),
            'height' => env('BROWSER_VIEWPORT_HEIGHT', 1080),
        ],

        'screenshots' => [
            'on_failure' => true,
            'path' => storage_path('app/screenshots'),
        ],

        'videos' => [
            'enabled' => env('BROWSER_RECORD_VIDEOS', false),
            'path' => storage_path('app/videos'),
        ],

        'create_test_data' => true,
        'cleanup_after_test' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | External Services
    |--------------------------------------------------------------------------
    */

    'services' => [
        'disable_external' => true,
        'mock_payments' => true,
        'mock_notifications' => true,
        'mock_search' => true,
        'mock_mail' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debugging
    |--------------------------------------------------------------------------
    */

    'debug' => [
        'enabled' => env('TEST_DEBUG', false),
        'dump_sql' => env('TEST_DUMP_SQL', false),
        'verbose' => env('TEST_VERBOSE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'after_parallel' => true,
        'remove_test_databases' => env('CLEANUP_TEST_DATABASES', false),
        'clear_cache' => true,
        'clear_logs' => false,
    ],
];

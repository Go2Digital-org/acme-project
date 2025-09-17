<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'queue'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 300),
            'block_for' => 5,
            'after_commit' => false,
            'memory' => 512,
            'timeout' => 300,
        ],

        // High Priority Queues
        'payments' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'payments',
            'retry_after' => 180,
            'block_for' => 5,
            'after_commit' => false,
            'memory' => 256,
            'timeout' => 180,
            'max_tries' => 5,
            'backoff' => [30, 60, 120, 300, 600], // exponential backoff
        ],

        'notifications' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'notifications',
            'retry_after' => 120,
            'block_for' => 5,
            'after_commit' => false,
            'memory' => 128,
            'timeout' => 120,
            'max_tries' => 3,
            'backoff' => [15, 30, 60],
        ],

        // Medium Priority Queues
        'exports' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'exports',
            'retry_after' => 900, // 15 minutes
            'block_for' => 10,
            'after_commit' => false,
            'memory' => 1024, // Higher memory for large exports
            'timeout' => 900,
            'max_tries' => 2,
            'backoff' => [60, 180],
        ],

        'reports' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'reports',
            'retry_after' => 600, // 10 minutes
            'block_for' => 10,
            'after_commit' => false,
            'memory' => 512,
            'timeout' => 600,
            'max_tries' => 3,
            'backoff' => [30, 120, 300],
        ],

        // Low Priority Queues
        'maintenance' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'maintenance',
            'retry_after' => 1800, // 30 minutes
            'block_for' => 15,
            'after_commit' => false,
            'memory' => 256,
            'timeout' => 1800,
            'max_tries' => 1,
        ],

        'bulk' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'bulk',
            'retry_after' => 3600, // 1 hour
            'block_for' => 20,
            'after_commit' => false,
            'memory' => 1024,
            'timeout' => 3600,
            'max_tries' => 1,
        ],

        // Cache Warming Queues - High Performance Configuration
        'cache-warming' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'cache-warming',
            'retry_after' => 900, // 15 minutes
            'block_for' => 5,
            'after_commit' => false,
            'memory' => 256, // Optimized for chunked processing
            'timeout' => 900, // 15 minutes for large datasets
            'max_tries' => 3,
            'backoff' => [60, 180, 300], // Progressive backoff
        ],

        'cache-warming-orchestrator' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'cache-warming-orchestrator',
            'retry_after' => 60, // 1 minute
            'block_for' => 2,
            'after_commit' => false,
            'memory' => 128, // Low memory for orchestration
            'timeout' => 60,
            'max_tries' => 1, // Don't retry orchestrator
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];

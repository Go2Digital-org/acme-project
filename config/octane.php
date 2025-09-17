<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\FlushArrayCache;
use Laravel\Octane\Listeners\FlushAuthenticationState;
use Laravel\Octane\Listeners\FlushLogContext;
use Laravel\Octane\Listeners\FlushQueuedCookies;
use Laravel\Octane\Listeners\FlushSessionState;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your application server. You
    | are free to change this to the supported server of your choice.
    |
    */

    'server' => env('OCTANE_SERVER', 'frankenphp'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute URLs should be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state after
    | each request. You may even add your own listeners to this list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
        ],

        RequestReceived::class => [
            ...defined('SWOOLE_VERSION') ? [
                CheckForMaintenanceMode::class,
            ] : [],
        ],

        RequestTerminated::class => [
            FlushTemporaryContainerInstances::class,
            DisconnectFromDatabases::class,
            CollectGarbage::class,
        ],

        TaskReceived::class => [
            // ...
        ],

        TaskTerminated::class => [
            FlushTemporaryContainerInstances::class,
            CollectGarbage::class,
        ],

        TickReceived::class => [
            // ...
        ],

        TickTerminated::class => [
            FlushTemporaryContainerInstances::class,
            CollectGarbage::class,
        ],

        OperationTerminated::class => [
            FlushArrayCache::class,
            FlushAuthenticationState::class,
            FlushLogContext::class,
            FlushQueuedCookies::class,
            FlushSessionState::class,
            FlushUploadedFiles::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            // ...
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        'auth', 'auth.driver', 'blade.compiler', 'cache', 'cache.store', 'config', 'cookie',
        'encrypter', 'db', 'db.factory', 'hash', 'hash.driver', 'translator', 'log',
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Octane, you may leverage the Octane cache, which is powered
    | by a Redis backend. You may specify the cache table used to store the
    | cached values. This table will be used when the "octane" driver is used.
    |
    */

    'cache' => [
        'driver' => env('OCTANE_CACHE_DRIVER', 'octane'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage Octane's ability to manage tables
    | in memory. You may define a list of tables below along with their field
    | definitions where you may store application state across requests.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config',
        'database',
        'modules',
        'resources/**/*.php',
        'routes',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP processes such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if the application surpasses the given threshold (in MB).
    |
    */

    'garbage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | processed by Octane. You may set this value to 0 to indicate that there
    | shouldn't be a time limit on Octane request execution time.
    |
    */

    'max_execution_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | FrankenPHP Settings
    |--------------------------------------------------------------------------
    |
    | The following settings are specific to the FrankenPHP server. You may
    | adjust these settings based on your application's requirements.
    |
    */

    'frankenphp' => [
        'host' => env('OCTANE_HOST', '0.0.0.0'),
        'port' => env('OCTANE_PORT', 8000),
        'admin' => [
            'host' => env('OCTANE_ADMIN_HOST', '127.0.0.1'),
            'port' => env('OCTANE_ADMIN_PORT', 2019),
        ],
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
        'caddyfile' => base_path('Caddyfile'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RoadRunner Settings
    |--------------------------------------------------------------------------
    |
    | The following settings are specific to the RoadRunner server. You may
    | adjust these settings based on your application's requirements when
    | using RoadRunner to power your Octane application.
    |
    */

    'roadrunner' => [
        'host' => env('OCTANE_HOST', '0.0.0.0'),
        'port' => env('OCTANE_PORT', 8000),
        'rpc_host' => env('OCTANE_RPC_HOST', '127.0.0.1'),
        'rpc_port' => env('OCTANE_RPC_PORT', 6001),
        'workers' => env('OCTANE_WORKERS', 'auto'),
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
        'poll_interval' => env('OCTANE_POLL_INTERVAL', 1000),
        'memory_limit' => env('OCTANE_MEMORY_LIMIT', 128),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Settings
    |--------------------------------------------------------------------------
    |
    | The following settings are specific to the Swoole server. You may adjust
    | these settings based on your application's requirements when using the
    | Swoole server to power your Octane application.
    |
    */

    'swoole' => [
        'host' => env('OCTANE_HOST', '0.0.0.0'),
        'port' => env('OCTANE_PORT', 8000),
        'workers' => env('OCTANE_WORKERS', 'auto'),
        'task_workers' => env('OCTANE_TASK_WORKERS', 'auto'),
        'max_requests' => env('OCTANE_MAX_REQUESTS', 500),
        'public_path' => public_path(),
        'document_root' => env('SWOOLE_HTTP_DOCUMENT_ROOT', public_path()),
        'enable_static_handler' => env('SWOOLE_HTTP_ENABLE_STATIC_HANDLER', true),
        'options' => [
            'log_file' => storage_path('logs/swoole_http.log'),
            'package_max_length' => 10 * 1024 * 1024,
        ],
    ],

];

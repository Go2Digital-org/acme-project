<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If the
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'admin/horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'queue',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug((string) env('APP_NAME', 'acme-csr'), '_') . '_horizon:',
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web', 'auth', 'role:super_admin'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:payments' => 60,
        'redis:notifications' => 120,
        'redis:reports' => 300,
        'redis:exports' => 600,
        'redis:default' => 300,
        'redis:bulk' => 900,
        'redis:maintenance' => 1800,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => env('HORIZON_TRIM_RECENT', 60),
        'pending' => env('HORIZON_TRIM_PENDING', 60),
        'completed' => env('HORIZON_TRIM_COMPLETED', 60),
        'failed' => env('HORIZON_TRIM_FAILED', 10080), // 1 week
        'monitored' => env('HORIZON_TRIM_MONITORED', 10080), // 1 week
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // App\Jobs\NoiseJob::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with every
    | timestamp of the jobs in order to create the said graph.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new deployment to start while workers scale down.
    |
    */

    'fast_termination' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor is allowed to consume before it is terminated and restarted.
    | For configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and their settings are used by
    | Horizon when scaling your application's queue workers.
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'high-priority-supervisor' => [
                'connection' => 'payments',
                'queue' => ['payments'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'maxTime' => 0,
                'maxJobs' => 1000,
                'memory' => 256,
                'tries' => 5,
                'timeout' => 180,
                'nice' => 0,
            ],

            'notifications-supervisor' => [
                'connection' => 'notifications',
                'queue' => ['notifications'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'size',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 5,
                'maxTime' => 0,
                'maxJobs' => 500,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 120,
                'nice' => 0,
            ],

            'exports-supervisor' => [
                'connection' => 'exports',
                'queue' => ['exports'],
                'balance' => 'simple',
                'processes' => 2,
                'maxTime' => 0,
                'maxJobs' => 10,
                'memory' => 1024,
                'tries' => 2,
                'timeout' => 900,
                'nice' => 10,
            ],

            'reports-supervisor' => [
                'connection' => 'reports',
                'queue' => ['reports'],
                'balance' => 'simple',
                'processes' => 2,
                'maxTime' => 0,
                'maxJobs' => 20,
                'memory' => 512,
                'tries' => 3,
                'timeout' => 600,
                'nice' => 5,
            ],

            'bulk-supervisor' => [
                'connection' => 'bulk',
                'queue' => ['bulk'],
                'balance' => 'simple',
                'processes' => 1,
                'maxTime' => 0,
                'maxJobs' => 5,
                'memory' => 1024,
                'tries' => 1,
                'timeout' => 3600,
                'nice' => 15,
            ],

            'maintenance-supervisor' => [
                'connection' => 'maintenance',
                'queue' => ['maintenance'],
                'balance' => 'simple',
                'processes' => 1,
                'maxTime' => 0,
                'maxJobs' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 1800,
                'nice' => 19,
            ],
        ],

        'staging' => [
            'high-priority-supervisor' => [
                'connection' => 'payments',
                'queue' => ['payments'],
                'balance' => 'simple',
                'processes' => 1,
                'maxTime' => 0,
                'maxJobs' => 100,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 180,
                'nice' => 0,
            ],

            'general-supervisor' => [
                'connection' => 'redis',
                'queue' => ['notifications', 'reports', 'exports', 'default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 5,
                'maxTime' => 0,
                'maxJobs' => 50,
                'memory' => 256,
                'tries' => 2,
                'timeout' => 300,
                'nice' => 0,
            ],

            'bulk-supervisor' => [
                'connection' => 'bulk',
                'queue' => ['bulk', 'maintenance'],
                'balance' => 'simple',
                'processes' => 1,
                'maxTime' => 0,
                'maxJobs' => 3,
                'memory' => 512,
                'tries' => 1,
                'timeout' => 1800,
                'nice' => 10,
            ],
        ],

        'local' => [
            'local-supervisor' => [
                'connection' => 'redis',
                'queue' => ['payments', 'notifications', 'reports', 'exports', 'default', 'bulk', 'maintenance'],
                'balance' => 'simple',
                'processes' => 3,
                'maxTime' => 0,
                'maxJobs' => 50,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 60,
                'nice' => 0,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    |
    | By enabling this feature, Horizon will be displayed with a dark color
    | scheme that should be easy on the eyes. Feel free to disable this if
    | you prefer the normal bright color scheme of Horizon.
    |
    */

    'darkmode' => true,

];

<?php

declare(strict_types=1);

use Pest\Plugins\Coverage;

return [
    /*
    |--------------------------------------------------------------------------
    | Test Directories
    |--------------------------------------------------------------------------
    */
    'test_directories' => [
        'tests/Unit',
        'tests/Feature',
        'tests/Integration',
        'tests/Browser',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parallel Testing
    |--------------------------------------------------------------------------
    */
    'parallel' => [
        'processes' => 4,
        'token' => 'pest_test_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Coverage Configuration
    |--------------------------------------------------------------------------
    */
    'coverage' => [
        'min' => 80,
        'include' => [
            'app',
            'modules',
        ],
        'exclude' => [
            'app/Console',
            'app/Exceptions',
            'app/Http/Middleware',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'routes',
            'storage',
            'vendor',
            'modules/*/Infrastructure/Laravel/Migration',
            'modules/*/Infrastructure/Laravel/Factory',
            'modules/*/Infrastructure/Laravel/Seeder',
            'modules/*/Infrastructure/Filament.bak',
            'tests',
        ],
        'reports' => [
            'html' => 'coverage/html',
            'clover' => 'coverage/clover.xml',
            'text' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups and Tags
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'unit' => 'tests/Unit',
        'feature' => 'tests/Feature',
        'integration' => 'tests/Integration',
        'browser' => 'tests/Browser',
        'campaign' => ['campaign'],
        'donation' => ['donation'],
        'organization' => ['organization'],
        'payment' => ['payment'],
        'multilingual' => ['multilingual'],
        'performance' => ['performance'],
        'slow' => ['slow'],
        'fast' => ['unit', 'feature'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporters
    |--------------------------------------------------------------------------
    */
    'reporters' => [
        'default',
        'junit' => 'test-results/junit.xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins
    |--------------------------------------------------------------------------
    */
    'plugins' => [
        Coverage::class,
    ],
];

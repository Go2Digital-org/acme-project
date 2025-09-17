<?php

declare(strict_types=1);

return [
    'browser' => [
        'name' => env('BROWSER_NAME', 'chromium'),
        'headless' => env('BROWSER_HEADLESS', false) === 'true' || env('BROWSER_HEADLESS', false) === true,
        'timeout' => 30000, // 30 seconds
    ],
    'viewport' => [
        'width' => 1920,
        'height' => 1080,
    ],
    'app' => [
        'base_url' => env('PLAYWRIGHT_BASE_URL', env('TEST_SERVER_URL', 'http://127.0.0.1:8000')),
    ],
    'screenshots' => [
        'path' => base_path('tests/Browser/screenshots'),
        'on_failure' => true,
        'on_success' => false,
    ],
    'visual_regression' => [
        'baseline_path' => base_path('tests/Browser/baseline'),
        'diff_path' => base_path('tests/Browser/diffs'),
        'threshold' => 0.2,
    ],
];

<?php

declare(strict_types=1);

return [
    'browser' => [
        'name' => env('BROWSER_NAME', 'chromium'),
        'headless' => env('BROWSER_HEADLESS', true) === 'true' || env('BROWSER_HEADLESS', true) === true,
        'timeout' => 30000, // 30 seconds to handle page loading delays
        'args' => [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--disable-features=TranslateUI',
            '--disable-ipc-flooding-protection',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-field-trial-config',
            '--no-first-run',
            '--no-default-browser-check',
            '--memory-pressure-off',
            '--max_old_space_size=4096',
        ],
    ],
    'viewport' => [
        'width' => 1280,
        'height' => 720, // Smaller viewport for better performance
    ],
    'app' => [
        'base_url' => env('PLAYWRIGHT_BASE_URL', env('TEST_SERVER_URL', 'http://127.0.0.1:8000')),
    ],
    'screenshots' => [
        'path' => base_path('tests/Browser/screenshots'),
        'on_failure' => true,
        'on_success' => false, // Disable success screenshots for speed
    ],
    'visual_regression' => [
        'baseline_path' => base_path('tests/Browser/baseline'),
        'diff_path' => base_path('tests/Browser/diffs'),
        'threshold' => 0.2,
    ],
];

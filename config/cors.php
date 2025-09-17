<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        // Development environments
        'http://localhost:3000',     // Vue.js development server
        'http://localhost:5173',     // Vite development server
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',

        // Add production domains as needed
        // 'https://acme-corp.com',
        // 'https://app.acme-corp.com',
        // 'https://admin.acme-corp.com',
    ],

    'allowed_origins_patterns' => [
        // Allow localhost with any port for development
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Origin',
        'Cache-Control',
        'X-CSRF-TOKEN',
        'X-Socket-ID',
    ],

    'exposed_headers' => [
        'Content-Language',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'Link',
        'Location',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,
];

<?php

declare(strict_types=1);

return [
    // General error messages
    'validation_error' => 'Whoops! Something went wrong.',
    'go_back_home' => 'Go Back Home',

    // HTTP error messages
    '403' => [
        'title' => '403 - Forbidden',
        'message' => 'You don\'t have permission to access this page.',
    ],
    '404' => [
        'title' => '404 - Page Not Found',
        'message' => 'The page you are looking for could not be found.',
    ],
    '419' => [
        'title' => '419 - Page Expired',
        'message' => 'Your session has expired. Please refresh the page and try again.',
    ],
    '429' => [
        'title' => '429 - Too Many Requests',
        'message' => 'Too many requests. Please try again later.',
    ],
    '500' => [
        'title' => '500 - Server Error',
        'message' => 'There was a problem with our server. Please try again later.',
    ],
    '503' => [
        'title' => '503 - Service Unavailable',
        'message' => 'The service is temporarily unavailable. Please try again later.',
    ],

    // Form error messages
    'form_errors' => 'Please correct the following errors:',
    'required_field' => 'This field is required',
    'invalid_format' => 'Invalid format',
    'file_upload_failed' => 'File upload failed',
    'permission_denied' => 'Permission denied',

    // System errors
    'database_error' => 'Database connection error',
    'network_error' => 'Network connection error',
    'timeout_error' => 'Request timed out',
    'unknown_error' => 'An unknown error occurred',
];

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when no specific gateway is requested.
    |
    */
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'mollie'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways that your application
    | supports. Each gateway has its own configuration requirements.
    |
    */
    'gateways' => [
        'mollie' => [
            'api_key' => env('MOLLIE_API_KEY'),
            'webhook_url' => env('MOLLIE_WEBHOOK_URL', env('APP_URL') . '/webhooks/mollie'),
            'test_mode' => env('MOLLIE_TEST_MODE', true),
            'description_prefix' => env('MOLLIE_DESCRIPTION_PREFIX', 'ACME Corp Donation'),
        ],

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'test_mode' => env('STRIPE_TEST_MODE', true),
            'automatic_payment_methods' => env('STRIPE_AUTOMATIC_PAYMENT_METHODS', true),
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'sandbox_mode' => env('PAYPAL_SANDBOX_MODE', true),
        ],

        'mock' => [
            'simulate_failures' => env('MOCK_SIMULATE_FAILURES', false),
            'failure_rate' => env('MOCK_FAILURE_RATE', 0.1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    |
    | Configure minimum and maximum donation amounts and daily limits
    | to prevent fraud and ensure reasonable donation sizes.
    |
    */
    'limits' => [
        'min_donation' => (float) env('PAYMENT_MIN_DONATION', 5.00),
        'max_donation' => (float) env('PAYMENT_MAX_DONATION', 50000.00),
        'max_daily_per_user' => (float) env('PAYMENT_MAX_DAILY_PER_USER', 10000.00),
        'max_monthly_per_user' => (float) env('PAYMENT_MAX_MONTHLY_PER_USER', 50000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies that your application supports for donations.
    | Make sure your payment gateways support these currencies.
    |
    */
    'currencies' => [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'CAD' => 'Canadian Dollar',
        'AUD' => 'Australian Dollar',
        'JPY' => 'Japanese Yen',
        'SGD' => 'Singapore Dollar',
        'CHF' => 'Swiss Franc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configure payment processing behavior, retry logic, and timeouts.
    |
    */
    'processing' => [
        'timeout' => (int) env('PAYMENT_TIMEOUT', 30), // seconds
        'max_retries' => (int) env('PAYMENT_MAX_RETRIES', 3),
        'retry_delay' => (int) env('PAYMENT_RETRY_DELAY', 5), // seconds
        'auto_capture' => env('PAYMENT_AUTO_CAPTURE', true),
        'refund_window_days' => (int) env('PAYMENT_REFUND_WINDOW', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security-related payment settings.
    |
    */
    'security' => [
        'require_3ds' => env('PAYMENT_REQUIRE_3DS', false),
        'enable_fraud_detection' => env('PAYMENT_FRAUD_DETECTION', true),
        'enable_rate_limiting' => env('PAYMENT_RATE_LIMITING', true),
        'max_attempts_per_hour' => (int) env('PAYMENT_MAX_ATTEMPTS_PER_HOUR', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configure webhook endpoints and verification settings.
    |
    */
    'webhooks' => [
        'enabled' => env('PAYMENT_WEBHOOKS_ENABLED', true),
        'verify_signatures' => env('PAYMENT_WEBHOOKS_VERIFY_SIGNATURES', true),
        'timeout' => (int) env('PAYMENT_WEBHOOKS_TIMEOUT', 10), // seconds
        'max_retries' => (int) env('PAYMENT_WEBHOOKS_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure donation confirmation and failure notifications.
    |
    */
    'notifications' => [
        'send_confirmations' => env('PAYMENT_SEND_CONFIRMATIONS', true),
        'send_failure_notifications' => env('PAYMENT_SEND_FAILURE_NOTIFICATIONS', true),
        'send_refund_confirmations' => env('PAYMENT_SEND_REFUND_CONFIRMATIONS', true),
        'admin_notification_threshold' => (float) env('PAYMENT_ADMIN_NOTIFICATION_THRESHOLD', 1000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recurring Payments
    |--------------------------------------------------------------------------
    |
    | Configure recurring donation processing.
    |
    */
    'recurring' => [
        'enabled' => env('PAYMENT_RECURRING_ENABLED', true),
        'frequencies' => [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ],
        'max_failures' => (int) env('PAYMENT_RECURRING_MAX_FAILURES', 3),
        'retry_delays' => [1, 3, 7], // days
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing & Development
    |--------------------------------------------------------------------------
    |
    | Settings for testing and development environments.
    |
    */
    'testing' => [
        'use_mock_gateway' => env('PAYMENT_USE_MOCK_GATEWAY', false),
        'mock_success_rate' => (float) env('PAYMENT_MOCK_SUCCESS_RATE', 0.9),
        'enable_test_webhooks' => env('PAYMENT_ENABLE_TEST_WEBHOOKS', false),
    ],
];

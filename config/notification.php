<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the ACME Corp notification
    | system, including channels, delivery settings, and integration options.
    |
    */

    'default_channel' => env('NOTIFICATION_DEFAULT_CHANNEL', 'email'),
    'default_priority' => env('NOTIFICATION_DEFAULT_PRIORITY', 'medium'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configuration for different notification delivery channels.
    |
    */
    'channels' => [
        'email' => [
            'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
            'driver' => env('MAIL_MAILER', 'smtp'),
            'from' => [
                'address' => env('NOTIFICATION_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@acme-corp.com')),
                'name' => env('NOTIFICATION_FROM_NAME', env('MAIL_FROM_NAME', 'ACME Corp CSR')),
            ],
            'reply_to' => env('NOTIFICATION_REPLY_TO', 'support@acme-corp.com'),
            'rate_limit' => [
                'per_minute' => 100,
                'per_hour' => 1000,
                'per_day' => 10000,
            ],
            'templates' => [
                'donation_confirmation' => 'emails.notifications.donation-confirmation',
                'campaign_created' => 'emails.notifications.campaign-created',
                'campaign_activated' => 'emails.notifications.campaign-activated',
                'campaign_completed' => 'emails.notifications.campaign-completed',
                'organization_verified' => 'emails.notifications.organization-verified',
                'default' => 'emails.notifications.default',
            ],
        ],

        'sms' => [
            'enabled' => env('NOTIFICATION_SMS_ENABLED', true),
            'driver' => env('SMS_DRIVER', 'twilio'),
            'twilio' => [
                'sid' => env('TWILIO_SID'),
                'token' => env('TWILIO_TOKEN'),
                'from' => env('TWILIO_FROM_NUMBER'),
            ],
            'vonage' => [
                'key' => env('VONAGE_KEY'),
                'secret' => env('VONAGE_SECRET'),
                'from' => env('VONAGE_FROM_NUMBER'),
            ],
            'rate_limit' => [
                'per_minute' => 20,
                'per_hour' => 200,
                'per_day' => 1000,
            ],
            'max_length' => 160,
            'url_shortener' => [
                'enabled' => true,
                'service' => 'tinyurl', // 'tinyurl', 'bitly', 'custom'
                'api_key' => env('URL_SHORTENER_API_KEY'),
            ],
        ],

        'push' => [
            'enabled' => env('NOTIFICATION_PUSH_ENABLED', true),
            'driver' => env('PUSH_DRIVER', 'fcm'),
            'fcm' => [
                'server_key' => env('FCM_SERVER_KEY'),
                'sender_id' => env('FCM_SENDER_ID'),
                'project_id' => env('FCM_PROJECT_ID'),
            ],
            'webpush' => [
                'vapid' => [
                    'public_key' => env('VAPID_PUBLIC_KEY'),
                    'private_key' => env('VAPID_PRIVATE_KEY'),
                    'subject' => env('VAPID_SUBJECT', 'mailto:admin@acme-corp.com'),
                ],
            ],
            'rate_limit' => [
                'per_minute' => 1000,
                'per_hour' => 10000,
                'per_day' => 50000,
            ],
        ],

        'webhook' => [
            'enabled' => env('NOTIFICATION_WEBHOOK_ENABLED', false),
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 60, // seconds
            'verify_ssl' => true,
            'rate_limit' => [
                'per_minute' => 50,
                'per_hour' => 500,
                'per_day' => 5000,
            ],
        ],

        'slack' => [
            'enabled' => env('NOTIFICATION_SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_DEFAULT_CHANNEL', '#notifications'),
            'username' => env('SLACK_USERNAME', 'ACME CSR Bot'),
            'icon' => env('SLACK_ICON', ':bell:'),
        ],

        'database' => [
            'enabled' => true,
            'table' => 'notifications',
            'cleanup' => [
                'enabled' => true,
                'retention_days' => 90,
                'batch_size' => 1000,
            ],
        ],

        'broadcast' => [
            'enabled' => env('BROADCAST_DRIVER', 'pusher') !== 'null',
            'driver' => env('BROADCAST_DRIVER', 'pusher'),
            'connection' => env('BROADCAST_CONNECTION', 'pusher'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define default settings for different notification types.
    |
    */
    'types' => [
        'donation_confirmation' => [
            'default_channels' => ['email', 'database'],
            'priority' => 'high',
            'expires_after' => null,
            'user_preferences_override' => false,
        ],
        'donation_received' => [
            'default_channels' => ['push', 'database'],
            'priority' => 'medium',
            'expires_after' => '7 days',
            'user_preferences_override' => true,
        ],
        'campaign_created' => [
            'default_channels' => ['email', 'database'],
            'priority' => 'medium',
            'expires_after' => '30 days',
            'user_preferences_override' => true,
        ],
        'campaign_activated' => [
            'default_channels' => ['email', 'push', 'database'],
            'priority' => 'high',
            'expires_after' => '7 days',
            'user_preferences_override' => false,
        ],
        'campaign_completed' => [
            'default_channels' => ['email', 'push', 'database'],
            'priority' => 'high',
            'expires_after' => '30 days',
            'user_preferences_override' => false,
        ],
        'admin_alert' => [
            'default_channels' => ['email', 'slack', 'database'],
            'priority' => 'critical',
            'expires_after' => null,
            'user_preferences_override' => false,
        ],
        'system_maintenance' => [
            'default_channels' => ['email', 'push', 'database', 'broadcast'],
            'priority' => 'high',
            'expires_after' => '1 day',
            'user_preferences_override' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notification job queues and processing.
    |
    */
    'queue' => [
        'default' => env('NOTIFICATION_QUEUE', 'notifications'),
        'high_priority' => env('NOTIFICATION_HIGH_PRIORITY_QUEUE', 'high-notifications'),
        'low_priority' => env('NOTIFICATION_LOW_PRIORITY_QUEUE', 'low-notifications'),
        'failed_queue' => env('NOTIFICATION_FAILED_QUEUE', 'failed-notifications'),

        'retry_after' => 90, // seconds
        'max_tries' => 3,
        'backoff' => [5, 15, 60], // seconds between retries

        'batch_processing' => [
            'enabled' => true,
            'batch_size' => 100,
            'batch_timeout' => 300, // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Preferences
    |--------------------------------------------------------------------------
    |
    | Default user notification preferences and override settings.
    |
    */
    'user_preferences' => [
        'allow_override' => true,
        'require_opt_in' => ['marketing', 'promotional'],
        'defaults' => [
            'email_enabled' => true,
            'email_frequency' => 'instant', // instant, daily, weekly
            'sms_enabled' => false,
            'push_enabled' => true,
            'quiet_hours' => [
                'enabled' => true,
                'start' => '22:00',
                'end' => '08:00',
                'timezone' => 'UTC',
            ],
        ],

        'frequencies' => [
            'instant' => 0,
            'hourly' => 3600,
            'daily' => 86400,
            'weekly' => 604800,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics and Analytics
    |--------------------------------------------------------------------------
    |
    | Configuration for notification tracking and analytics.
    |
    */
    'metrics' => [
        'enabled' => env('NOTIFICATION_METRICS_ENABLED', true),
        'track_opens' => true,
        'track_clicks' => true,
        'track_deliveries' => true,
        'track_failures' => true,

        'retention' => [
            'metrics_days' => 365,
            'events_days' => 90,
            'aggregated_forever' => true,
        ],

        'export' => [
            'enabled' => true,
            'formats' => ['csv', 'json', 'xlsx'],
            'schedule' => 'daily', // daily, weekly, monthly
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security and Spam Prevention
    |--------------------------------------------------------------------------
    |
    | Security settings for notification delivery and spam prevention.
    |
    */
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'per_user_per_hour' => 50,
            'per_user_per_day' => 200,
            'per_ip_per_hour' => 100,
        ],

        'content_filtering' => [
            'enabled' => true,
            'max_title_length' => 100,
            'max_message_length' => 1000,
            'allowed_html_tags' => ['p', 'br', 'strong', 'em', 'a'],
            'spam_keywords' => ['spam', 'phishing', 'malware'],
        ],

        'delivery_verification' => [
            'webhook_signature' => true,
            'ip_whitelist' => [],
            'user_agent_validation' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Internationalization
    |--------------------------------------------------------------------------
    |
    | Multi-language support for notifications.
    |
    */
    'i18n' => [
        'enabled' => true,
        'default_locale' => 'en',
        'supported_locales' => ['en', 'es', 'fr', 'de', 'zh'],
        'fallback_locale' => 'en',
        'auto_detect_user_locale' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance and Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimizations and caching strategies.
    |
    */
    'performance' => [
        'caching' => [
            'enabled' => env('NOTIFICATION_CACHING_ENABLED', true),
            'default_ttl' => 900, // 15 minutes
            'unread_count_ttl' => 300, // 5 minutes
            'preferences_ttl' => 3600, // 1 hour
            'digest_ttl' => 900, // 15 minutes
            'redis_enabled' => env('REDIS_ENABLED', true),
        ],

        'database' => [
            'read_replica_enabled' => env('DB_READ_REPLICA_ENABLED', false),
            'connection_pool_size' => env('DB_POOL_SIZE', 10),
            'query_timeout' => 30, // seconds
            'slow_query_threshold' => 1000, // milliseconds
        ],

        'bulk_operations' => [
            'batch_size' => 1000,
            'chunk_size' => 500,
            'parallel_processing' => true,
            'memory_limit' => '512M',
        ],

        'archiving' => [
            'enabled' => true,
            'archive_after_days' => 365,
            'batch_size' => 1000,
            'schedule' => 'daily',
            'compress_archives' => true,
        ],

        'monitoring' => [
            'enabled' => env('NOTIFICATION_MONITORING_ENABLED', true),
            'performance_alerts' => true,
            'connection_threshold' => 80, // percentage
            'slow_query_alert_threshold' => 2000, // milliseconds
            'memory_threshold' => 85, // percentage
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        'log_all_notifications' => env('NOTIFICATION_LOG_ALL', false),
        'disable_actual_sending' => env('NOTIFICATION_DISABLE_SENDING', false),
        'test_recipients' => [
            'email' => env('TEST_EMAIL_RECIPIENT'),
            'sms' => env('TEST_SMS_RECIPIENT'),
        ],
        'fake_delays' => [
            'email' => 0,
            'sms' => 2,
            'push' => 1,
        ],
        'profiling' => [
            'enabled' => env('NOTIFICATION_PROFILING_ENABLED', false),
            'sample_rate' => 0.1, // 10% of requests
        ],
    ],
];

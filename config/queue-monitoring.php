<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for queue monitoring,
    | including thresholds, alerts, and notification settings.
    |
    */

    'enabled' => env('QUEUE_MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Monitoring Thresholds
    |--------------------------------------------------------------------------
    |
    | Define threshold values for different queue monitoring metrics.
    | When these thresholds are exceeded, alerts will be triggered.
    |
    */

    'thresholds' => [
        'queue_sizes' => [
            'notifications' => env('QUEUE_THRESHOLD_NOTIFICATIONS', 1000),
            'payments' => env('QUEUE_THRESHOLD_PAYMENTS', 100),
            'exports' => env('QUEUE_THRESHOLD_EXPORTS', 50),
            'bulk' => env('QUEUE_THRESHOLD_BULK', 500),
            'maintenance' => env('QUEUE_THRESHOLD_MAINTENANCE', 200),
            'default' => env('QUEUE_THRESHOLD_DEFAULT', 200),
        ],

        'failed_jobs' => [
            'hourly' => env('FAILED_JOBS_THRESHOLD_HOURLY', 10),
            'daily' => env('FAILED_JOBS_THRESHOLD_DAILY', 50),
        ],

        'memory_usage' => [
            'warning' => env('MEMORY_THRESHOLD_WARNING', 80), // Percentage
            'critical' => env('MEMORY_THRESHOLD_CRITICAL', 95), // Percentage
        ],

        'job_duration' => [
            'slow_job_minutes' => env('SLOW_JOB_THRESHOLD_MINUTES', 30),
            'stuck_job_minutes' => env('STUCK_JOB_THRESHOLD_MINUTES', 60),
        ],

        'processing_rate' => [
            'minimum_jobs_per_minute' => env('MIN_PROCESSING_RATE', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how and when alerts should be sent when thresholds are exceeded.
    |
    */

    'alerts' => [
        'enabled' => env('QUEUE_ALERTS_ENABLED', true),

        'channels' => [
            'email' => env('QUEUE_ALERTS_EMAIL', true),
            'slack' => env('QUEUE_ALERTS_SLACK', false),
            'webhook' => env('QUEUE_ALERTS_WEBHOOK', false),
        ],

        'recipients' => [
            'critical' => [
                ['email' => env('ADMIN_EMAIL', 'admin@example.com'), 'name' => 'System Administrator'],
                ['email' => env('DEVOPS_EMAIL', 'devops@example.com'), 'name' => 'DevOps Team'],
            ],
            'high' => [
                ['email' => env('ADMIN_EMAIL', 'admin@example.com'), 'name' => 'System Administrator'],
            ],
            'medium' => [
                ['email' => env('DEV_EMAIL', 'dev@example.com'), 'name' => 'Development Team'],
            ],
            'low' => [
                // Low priority alerts go to logs only
            ],
        ],

        'rate_limiting' => [
            'enabled' => true,
            'max_alerts_per_hour' => 5,
            'cooldown_minutes' => 15, // Minimum time between similar alerts
        ],

        'escalation' => [
            'enabled' => true,
            'critical_escalation_minutes' => 30, // Escalate if not resolved
            'escalation_recipients' => [
                ['email' => env('CTO_EMAIL', 'cto@example.com'), 'name' => 'CTO'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Schedule
    |--------------------------------------------------------------------------
    |
    | Define how often different types of monitoring should run.
    |
    */

    'schedule' => [
        'queue_health' => '*/5 * * * *', // Every 5 minutes
        'failed_jobs' => '*/10 * * * *', // Every 10 minutes
        'job_metrics' => '*/15 * * * *', // Every 15 minutes
        'memory_usage' => '*/3 * * * *', // Every 3 minutes
        'slow_jobs' => '*/20 * * * *', // Every 20 minutes
        'system_health' => '0 * * * *', // Every hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configure what metrics to collect and how long to retain them.
    |
    */

    'metrics' => [
        'enabled' => env('QUEUE_METRICS_ENABLED', true),

        'retention' => [
            'hourly_metrics' => 7, // Days to keep hourly metrics
            'daily_metrics' => 30, // Days to keep daily metrics
            'failed_job_history' => 7, // Days to keep failed job details
        ],

        'collection' => [
            'queue_throughput' => true,
            'job_duration_stats' => true,
            'failure_patterns' => true,
            'memory_usage' => true,
            'system_resources' => env('COLLECT_SYSTEM_RESOURCES', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Recovery
    |--------------------------------------------------------------------------
    |
    | Configure automatic recovery mechanisms for failed jobs.
    |
    */

    'auto_recovery' => [
        'enabled' => env('QUEUE_AUTO_RECOVERY_ENABLED', true),

        'recoverable_errors' => [
            'connection timeout',
            'temporary network error',
            'rate limit exceeded',
            'service temporarily unavailable',
            'deadlock',
            'lock wait timeout',
        ],

        'recovery_delays' => [
            'connection timeout' => 300, // 5 minutes
            'temporary network error' => 180, // 3 minutes
            'rate limit exceeded' => 3600, // 1 hour
            'service temporarily unavailable' => 600, // 10 minutes
            'deadlock' => 60, // 1 minute
            'lock wait timeout' => 120, // 2 minutes
            'default' => 300, // 5 minutes
        ],

        'max_recovery_attempts' => 3,
        'recovery_cooldown_hours' => 24, // Don't recover same job more than once per day
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Email templates for different types of monitoring alerts.
    |
    */

    'templates' => [
        'queue_health_alert' => 'emails.monitoring.queue-health-alert',
        'failed_jobs_alert' => 'emails.monitoring.failed-jobs-alert',
        'memory_alert' => 'emails.monitoring.memory-alert',
        'slow_jobs_alert' => 'emails.monitoring.slow-jobs-alert',
        'system_health_alert' => 'emails.monitoring.system-health-alert',
        'recovery_notification' => 'emails.monitoring.recovery-notification',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the queue monitoring dashboard.
    |
    */

    'dashboard' => [
        'enabled' => env('QUEUE_DASHBOARD_ENABLED', true),
        'route_prefix' => 'admin/queue-monitoring',
        'middleware' => ['auth', 'admin'],
        'refresh_interval' => 30, // Seconds
        'charts' => [
            'queue_sizes' => true,
            'throughput' => true,
            'failure_rate' => true,
            'response_times' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | External service integrations for monitoring and alerting.
    |
    */

    'integrations' => [
        'slack' => [
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_MONITORING_CHANNEL', '#alerts'),
            'username' => 'Queue Monitor',
            'icon_emoji' => ':warning:',
        ],

        'webhook' => [
            'url' => env('MONITORING_WEBHOOK_URL'),
            'secret' => env('MONITORING_WEBHOOK_SECRET'),
            'timeout' => 10,
        ],

        'datadog' => [
            'enabled' => env('DATADOG_MONITORING_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
            'namespace' => 'acme.queue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Classification
    |--------------------------------------------------------------------------
    |
    | Classify jobs by importance for different monitoring and alerting.
    |
    */

    'job_classification' => [
        'critical' => [
            'ProcessPaymentJob',
            'ProcessRefundJob',
            'SendPaymentConfirmationJob',
            'ProcessDonationJob',
            'RefundProcessingJob',
        ],

        'high_priority' => [
            'SendEmailJob',
            'GenerateTaxReceiptJob',
            'SendAdminNotificationJob',
            'ProcessPaymentWebhookJob',
        ],

        'medium_priority' => [
            'SendCampaignUpdateNotificationJob',
            'SendMilestoneNotificationJob',
            'IndexEntityJob',
        ],

        'low_priority' => [
            'CleanupExpiredDataJob',
            'WarmCacheJob',
            'ProcessImportJob',
            'ExportDonationsJob',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Tuning
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for queue monitoring.
    |
    */

    'performance' => [
        'batch_size' => env('MONITORING_BATCH_SIZE', 100),
        'query_timeout' => env('MONITORING_QUERY_TIMEOUT', 30),
        'cache_ttl' => env('MONITORING_CACHE_TTL', 300), // 5 minutes
        'async_processing' => env('MONITORING_ASYNC', true),
    ],

];

<?php

declare(strict_types=1);

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;

return [
    /*
    |--------------------------------------------------------------------------
    | Export Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the storage disk and path for export files, as well as the
    | time-to-live (TTL) for exported files before automatic cleanup.
    |
    */
    'storage' => [
        'disk' => env('EXPORT_STORAGE_DISK', 'local'),
        'path' => 'exports',
        'ttl_days' => env('EXPORT_TTL_DAYS', 7),
        'temp_path' => storage_path('app/temp/exports'),
        'cleanup_on_download' => env('EXPORT_CLEANUP_ON_DOWNLOAD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how exports are processed, including chunk sizes for memory
    | efficiency, queue settings, and timeout configurations.
    |
    */
    'processing' => [
        'chunk_size' => env('EXPORT_CHUNK_SIZE', 1000),
        'queue' => env('EXPORT_QUEUE', 'exports'),
        'timeout' => env('EXPORT_TIMEOUT', 3600), // 1 hour
        'retry_after' => env('EXPORT_RETRY_AFTER', 300), // 5 minutes
        'max_attempts' => env('EXPORT_MAX_ATTEMPTS', 3),
        'memory_limit' => env('EXPORT_MEMORY_LIMIT', '512M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Limits and Constraints
    |--------------------------------------------------------------------------
    |
    | Define the maximum concurrent exports per user and organization, as well
    | as format-specific record limits to prevent resource exhaustion.
    |
    */
    'limits' => [
        'max_concurrent_per_user' => env('EXPORT_MAX_CONCURRENT_USER', 3),
        'max_concurrent_per_org' => env('EXPORT_MAX_CONCURRENT_ORG', 10),
        'max_file_size' => env('EXPORT_MAX_FILE_SIZE', 104857600), // 100MB
        'max_records' => [
            'csv' => env('EXPORT_MAX_RECORDS_CSV', 1000000),
            'excel' => env('EXPORT_MAX_RECORDS_EXCEL', 100000),
            'pdf' => env('EXPORT_MAX_RECORDS_PDF', 10000),
            'json' => env('EXPORT_MAX_RECORDS_JSON', 500000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Formats Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings for each export format, including delimiters,
    | encodings, and format-specific options.
    |
    */
    'formats' => [
        'csv' => [
            'delimiter' => env('EXPORT_CSV_DELIMITER', ','),
            'enclosure' => env('EXPORT_CSV_ENCLOSURE', '"'),
            'escape' => env('EXPORT_CSV_ESCAPE', '\\'),
            'encoding' => env('EXPORT_CSV_ENCODING', 'UTF-8'),
            'include_bom' => env('EXPORT_CSV_INCLUDE_BOM', true),
            'line_ending' => env('EXPORT_CSV_LINE_ENDING', "\n"),
        ],
        'excel' => [
            'writer_type' => env('EXPORT_EXCEL_WRITER', 'Xlsx'),
            'include_headers' => true,
            'auto_size_columns' => true,
            'freeze_header_row' => true,
            'creator' => env('APP_NAME', 'ACME Corp CSR Platform'),
        ],
        'pdf' => [
            'orientation' => 'landscape',
            'paper_size' => 'A4',
            'font_size' => 10,
            'margin' => [
                'top' => 20,
                'right' => 15,
                'bottom' => 20,
                'left' => 15,
            ],
        ],
        'json' => [
            'pretty_print' => env('EXPORT_JSON_PRETTY', true),
            'escape_unicode' => false,
            'escape_slashes' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how users are notified about export status changes.
    |
    */
    'notifications' => [
        'channels' => [
            'mail' => env('EXPORT_NOTIFY_MAIL', true),
            'database' => env('EXPORT_NOTIFY_DATABASE', true),
            'broadcast' => env('EXPORT_NOTIFY_BROADCAST', true),
        ],
        'events' => [
            'started' => false,
            'completed' => true,
            'failed' => true,
            'cancelled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for exports, including encryption and
    | access control settings.
    |
    */
    'security' => [
        'encrypt_exports' => env('EXPORT_ENCRYPT', false),
        'require_authentication' => true,
        'verify_ownership' => true,
        'signed_urls' => true,
        'url_expiry_minutes' => env('EXPORT_URL_EXPIRY', 60),
        'allowed_ips' => env('EXPORT_ALLOWED_IPS') ? explode(',', (string) env('EXPORT_ALLOWED_IPS')) : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for export processing.
    |
    */
    'performance' => [
        'use_cache' => env('EXPORT_USE_CACHE', true),
        'cache_ttl' => env('EXPORT_CACHE_TTL', 300), // 5 minutes
        'use_compression' => env('EXPORT_USE_COMPRESSION', true),
        'parallel_processing' => env('EXPORT_PARALLEL', false),
        'batch_insert' => true,
        'optimize_queries' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Resource Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define available resource types for export and their configurations.
    |
    */
    'resources' => [
        'campaigns' => [
            'enabled' => true,
            'model' => Campaign::class,
            'repository' => CampaignRepositoryInterface::class,
            'columns' => ['id', 'title', 'description', 'goal_amount', 'raised_amount', 'status', 'start_date', 'end_date'],
            'relations' => ['organization', 'donations'],
            'filters' => ['status', 'organization_id', 'date_range'],
        ],
        'donations' => [
            'enabled' => true,
            'model' => Donation::class,
            'repository' => DonationRepositoryInterface::class,
            'columns' => ['id', 'amount', 'currency', 'status', 'donor_name', 'donor_email', 'campaign_id', 'created_at'],
            'relations' => ['campaign', 'user'],
            'filters' => ['status', 'campaign_id', 'date_range', 'amount_range'],
        ],
        'users' => [
            'enabled' => true,
            'model' => User::class,
            'repository' => UserRepositoryInterface::class,
            'columns' => ['id', 'name', 'email', 'role', 'organization_id', 'created_at'],
            'relations' => ['organization', 'donations'],
            'filters' => ['role', 'organization_id', 'status'],
        ],
        'organizations' => [
            'enabled' => true,
            'model' => Organization::class,
            'repository' => OrganizationRepositoryInterface::class,
            'columns' => ['id', 'name', 'type', 'status', 'created_at'],
            'relations' => ['users', 'campaigns'],
            'filters' => ['type', 'status'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Templates Configuration
    |--------------------------------------------------------------------------
    |
    | Define predefined export templates for common use cases.
    |
    */
    'templates' => [
        'donation_report' => [
            'resource' => 'donations',
            'format' => 'excel',
            'columns' => ['id', 'amount', 'currency', 'donor_name', 'campaign_id', 'created_at'],
            'include_summary' => true,
        ],
        'campaign_summary' => [
            'resource' => 'campaigns',
            'format' => 'pdf',
            'columns' => ['title', 'goal_amount', 'raised_amount', 'status', 'start_date', 'end_date'],
            'include_charts' => true,
        ],
        'donor_list' => [
            'resource' => 'users',
            'format' => 'csv',
            'columns' => ['name', 'email', 'total_donated', 'last_donation_date'],
            'filters' => ['has_donations' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings for scheduled and recurring exports.
    |
    */
    'scheduling' => [
        'enabled' => env('EXPORT_SCHEDULING_ENABLED', false),
        'max_scheduled_per_user' => 5,
        'allowed_frequencies' => ['daily', 'weekly', 'monthly', 'quarterly'],
        'cleanup_old_schedules' => true,
        'retention_days' => 90,
    ],
];

# Database Schema Documentation

## Overview

The ACME Corp CSR Platform uses a MySQL 8.0 database with a modular hexagonal architecture. The schema is designed to support high-performance operations with 20K+ employee platforms, maintain data integrity, and provide comprehensive audit trails for compliance requirements.

## Database Structure Overview

### Core Domain Tables
- **Users**: Employee and admin user management
- **Organizations**: Multi-tenant organization entities with tenancy support
- **Campaigns**: CSR campaign management with rich metadata
- **Donations**: Financial transaction tracking with payment gateway integration
- **Categories**: Campaign categorization system
- **Compliance**: GDPR/PCI compliance and audit logging
- **Notifications**: System notification management

### Supporting Tables
- **Bookmarks**: User campaign bookmarks
- **Cache**: Application-level caching
- **Audits**: Comprehensive audit trail
- **Currency**: Multi-currency support

## Core Tables Structure

### Users Table

Primary user entity supporting role-based access control and multi-tenancy.

```sql
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `role` enum('admin','employee','organization_admin') NOT NULL DEFAULT 'employee',
  `organization_id` bigint unsigned DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_recovery_codes` json DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `timezone` varchar(255) DEFAULT 'UTC',
  `locale` varchar(10) DEFAULT 'en',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_organization_id_foreign` (`organization_id`),
  KEY `idx_users_status_role` (`status`,`role`),
  KEY `idx_users_email_verification` (`email_verified_at`),
  KEY `idx_users_org_status_id` (`organization_id`,`status`,`id`),
  CONSTRAINT `users_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Features**:
- Email-based authentication with verification
- Role-based access control (admin, employee, organization_admin)
- Two-factor authentication support
- JSON preferences for flexible user settings
- Organization association for multi-tenant support
- Performance-optimized indexes for leaderboard queries

### Organizations Table

Multi-tenant organization entities with tenancy database support.

```sql
CREATE TABLE `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL COMMENT 'Translatable organization name',
  `slug` varchar(255) NOT NULL,
  `description` json DEFAULT NULL COMMENT 'Translatable description',
  `mission` json DEFAULT NULL COMMENT 'Translatable mission statement',
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `registration_number` varchar(100) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_date` timestamp NULL DEFAULT NULL,
  `founded_date` date DEFAULT NULL,
  `subdomain` varchar(255) DEFAULT NULL,
  `database` varchar(255) DEFAULT NULL,
  `provisioning_status` varchar(50) DEFAULT NULL,
  `provisioning_error` text,
  `provisioned_at` timestamp NULL DEFAULT NULL,
  `tenant_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organizations_slug_unique` (`slug`),
  UNIQUE KEY `organizations_registration_number_unique` (`registration_number`),
  UNIQUE KEY `organizations_tax_id_unique` (`tax_id`),
  KEY `idx_organizations_status` (`status`),
  KEY `idx_organizations_active_verified` (`is_active`,`is_verified`),
  KEY `idx_organizations_category` (`category`),
  KEY `idx_organizations_verification` (`verification_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Features**:
- Multi-language support via JSON fields
- Tenant database provisioning support
- Verification workflow management
- Comprehensive business information storage
- Geographic location support

### Campaigns Table

Core campaign entity with performance optimizations for high-volume operations.

```sql
CREATE TABLE `campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) DEFAULT NULL,
  `title` json NOT NULL COMMENT 'Translatable campaign title',
  `slug` varchar(255) DEFAULT NULL,
  `description` json NOT NULL COMMENT 'Translatable campaign description',
  `goal_amount` decimal(15,2) NOT NULL,
  `target_amount` decimal(15,2) DEFAULT NULL,
  `current_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `goal_percentage` decimal(5,2) GENERATED ALWAYS AS (
    CASE
      WHEN goal_amount > 0 THEN (current_amount / goal_amount * 100)
      ELSE 0
    END
  ) STORED,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `donations_count` int unsigned NOT NULL DEFAULT '0',
  `views_count` bigint unsigned NOT NULL DEFAULT '0',
  `shares_count` bigint unsigned NOT NULL DEFAULT '0',
  `status` enum('draft','pending_approval','active','completed','cancelled','paused') NOT NULL DEFAULT 'draft',
  `category` varchar(100) DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `organization_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL COMMENT 'Campaign creator',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `visibility` enum('public','private','organization') NOT NULL DEFAULT 'public',
  `featured_image` varchar(500) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `priority` int NOT NULL DEFAULT '0',
  `has_corporate_matching` tinyint(1) NOT NULL DEFAULT '0',
  `corporate_matching_rate` decimal(5,2) DEFAULT NULL,
  `max_corporate_matching` decimal(15,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `deadline` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `submitted_for_approval_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `metadata` json DEFAULT NULL,
  `send_confirmation_copies_to_organizer` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaigns_uuid_unique` (`uuid`),
  UNIQUE KEY `campaigns_slug_unique` (`slug`),
  KEY `campaigns_category_id_foreign` (`category_id`),
  KEY `campaigns_organization_id_foreign` (`organization_id`),
  KEY `campaigns_user_id_foreign` (`user_id`),
  KEY `campaigns_created_by_foreign` (`created_by`),
  KEY `campaigns_approved_by_foreign` (`approved_by`),
  KEY `campaigns_rejected_by_foreign` (`rejected_by`),
  -- Performance indexes for high-volume queries
  KEY `idx_campaigns_status_dates` (`status`,`start_date`,`end_date`),
  KEY `idx_campaigns_featured_active` (`is_featured`,`status`,`deleted_at`,`current_amount`),
  KEY `idx_campaigns_near_goal` (`status`,`is_featured`,`goal_percentage`,`current_amount`,`deleted_at`),
  KEY `idx_campaigns_organization_status` (`organization_id`,`status`),
  KEY `idx_campaigns_featured_covering` (`deleted_at`,`status`,`is_featured`,`goal_percentage`,`current_amount`,`id`),
  KEY `idx_campaigns_goal_percentage` (`goal_percentage`),
  FULLTEXT KEY `idx_campaigns_search` (`title`,`description`),
  CONSTRAINT `campaigns_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaigns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Features**:
- Multi-language support for title and description
- Computed `goal_percentage` column for performance
- Comprehensive approval workflow
- Corporate matching support
- Performance-optimized indexes for featured campaigns
- Full-text search capability (delegated to Meilisearch)
- UUID support for public-facing URLs

### Donations Table

Financial transaction tracking with payment gateway integration and audit trail.

```sql
CREATE TABLE `donations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `payment_method` enum('credit_card','debit_card','bank_transfer','paypal','stripe','other') DEFAULT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway_response_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `recurring` tinyint(1) NOT NULL DEFAULT '0',
  `recurring_frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT NULL,
  `donated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text,
  `refund_reason` text,
  `notes` text,
  `notes_translations` json DEFAULT NULL,
  `corporate_match_amount` decimal(15,2) DEFAULT NULL,
  `donor_name` varchar(255) DEFAULT NULL COMMENT 'For guest donations',
  `donor_email` varchar(255) DEFAULT NULL COMMENT 'For guest donations',
  `confirmation_email_failed_at` timestamp NULL DEFAULT NULL,
  `confirmation_email_failure_reason` text,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `donations_transaction_id_unique` (`transaction_id`),
  KEY `donations_campaign_id_foreign` (`campaign_id`),
  KEY `donations_user_id_foreign` (`user_id`),
  -- Performance indexes for leaderboard and analytics
  KEY `idx_donations_status_amount` (`status`,`amount`),
  KEY `idx_donations_campaign_status` (`campaign_id`,`status`),
  KEY `idx_donations_leaderboard` (`user_id`,`status`,`deleted_at`,`amount`),
  KEY `idx_donations_user_ranking` (`status`,`deleted_at`,`user_id`,`amount`),
  KEY `idx_donations_payment_tracking` (`payment_gateway`,`gateway_response_id`),
  KEY `idx_donations_processed_date` (`processed_at`),
  KEY `idx_donations_performance` (`status`,`processed_at`,`amount`),
  CONSTRAINT `donations_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (YEAR(created_at)) (
  PARTITION p2024 VALUES LESS THAN (2025),
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION p2026 VALUES LESS THAN (2027),
  PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

**Key Features**:
- Multi-currency donation support
- Payment gateway integration tracking
- Anonymous and guest donation support
- Recurring donation support
- Corporate matching capabilities
- Comprehensive audit trail with timestamps
- Partitioned by year for performance at scale
- Optimized indexes for leaderboard calculations

### Categories Table

Hierarchical campaign categorization system.

```sql
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL COMMENT 'Translatable category name',
  `slug` varchar(255) NOT NULL,
  `description` json DEFAULT NULL COMMENT 'Translatable description',
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `idx_categories_active_sort` (`is_active`,`sort_order`),
  KEY `idx_categories_hierarchy` (`parent_id`,`sort_order`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Table Relationships

### Primary Relationships

```sql
-- User to Organization (Many-to-One)
users.organization_id → organizations.id

-- Campaign to Organization (Many-to-One)
campaigns.organization_id → organizations.id

-- Campaign to User (Many-to-One - Creator)
campaigns.user_id → users.id

-- Campaign to Category (Many-to-One)
campaigns.category_id → categories.id

-- Donation to Campaign (Many-to-One)
donations.campaign_id → campaigns.id

-- Donation to User (Many-to-One)
donations.user_id → users.id

-- Category Hierarchy (Self-referencing)
categories.parent_id → categories.id
```

### Supporting Relationships

```sql
-- Bookmarks (Many-to-Many through junction)
bookmarks.user_id → users.id
bookmarks.campaign_id → campaigns.id

-- Audit Trail
audits.user_id → users.id (polymorphic)

-- Approval Workflow
campaigns.approved_by → users.id
campaigns.rejected_by → users.id
```

## Key Indexes and Performance Considerations

### Campaign Performance Indexes

```sql
-- Featured campaigns optimization
KEY `idx_campaigns_featured_active` (`is_featured`,`status`,`deleted_at`,`current_amount`)

-- Near-goal campaigns (70-99% completion)
KEY `idx_campaigns_near_goal` (`status`,`is_featured`,`goal_percentage`,`current_amount`,`deleted_at`)

-- Covering index to avoid table lookups
KEY `idx_campaigns_featured_covering` (`deleted_at`,`status`,`is_featured`,`goal_percentage`,`current_amount`,`id`)

-- Organization campaign listing
KEY `idx_campaigns_organization_status` (`organization_id`,`status`)
```

### Donation Performance Indexes

```sql
-- Leaderboard calculation optimization
KEY `idx_donations_leaderboard` (`user_id`,`status`,`deleted_at`,`amount`)

-- User ranking subqueries
KEY `idx_donations_user_ranking` (`status`,`deleted_at`,`user_id`,`amount`)

-- Campaign donation tracking
KEY `idx_donations_campaign_status` (`campaign_id`,`status`)
```

### User Organization Indexes

```sql
-- Organization-based user queries
KEY `idx_users_org_status_id` (`organization_id`,`status`,`id`)
```

## Performance Views

### Featured Campaigns View

```sql
CREATE OR REPLACE VIEW v_featured_campaigns AS
SELECT
    c.*,
    CASE
        WHEN c.is_featured = 1 THEN 1
        WHEN c.goal_percentage BETWEEN 70 AND 99.99 THEN 2
        ELSE 3
    END as priority
FROM campaigns c
WHERE c.deleted_at IS NULL
    AND c.status = 'active'
ORDER BY priority, c.current_amount DESC;
```

## Compliance Tables

### Data Subject Management

```sql
CREATE TABLE `compliance_data_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(255) NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `consent_status` enum('given','withdrawn','pending') NOT NULL,
  `consented_purposes` json DEFAULT NULL,
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `consent_withdrawn_at` timestamp NULL DEFAULT NULL,
  `data_export_requested_at` timestamp NULL DEFAULT NULL,
  `data_export_completed_at` timestamp NULL DEFAULT NULL,
  `deletion_requested_at` timestamp NULL DEFAULT NULL,
  `deletion_completed_at` timestamp NULL DEFAULT NULL,
  `legal_basis` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consent_records_subject` (`subject_type`,`subject_id`),
  KEY `idx_consent_status_email` (`consent_status`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Audit Logging

```sql
CREATE TABLE `compliance_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_type` varchar(255) DEFAULT NULL,
  `compliance_status` enum('compliant','violation','warning') NOT NULL,
  `event_data` json NOT NULL,
  `risk_assessment` json DEFAULT NULL,
  `compliance_officer` varchar(255) DEFAULT NULL,
  `remediation_action` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `remediated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_auditable` (`auditable_type`,`auditable_id`),
  KEY `idx_audit_logs_event_date` (`event_type`,`created_at`),
  KEY `idx_audit_logs_compliance` (`compliance_status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Migration Strategy

### Zero-Downtime Migrations

```php
// Online index creation for large tables
Schema::table('campaigns', function (Blueprint $table) {
    $table->index(['status', 'created_at'], 'idx_campaigns_status_date');
}); // Uses ALGORITHM=INPLACE, LOCK=NONE in MySQL 8.0

// Partitioning existing tables
DB::statement('
    ALTER TABLE donations
    PARTITION BY RANGE (YEAR(created_at)) (
        PARTITION p2024 VALUES LESS THAN (2025),
        PARTITION p2025 VALUES LESS THAN (2026),
        PARTITION pmax VALUES LESS THAN MAXVALUE
    )
');
```

### Data Migration Patterns

```php
// Batch processing for large data migrations
DB::transaction(function () {
    $chunk = 1000;
    Campaign::whereNull('uuid')
        ->chunkById($chunk, function ($campaigns) {
            foreach ($campaigns as $campaign) {
                $campaign->update(['uuid' => Str::uuid()]);
            }
        });
});
```

## Monitoring and Maintenance

### Performance Monitoring Queries

```sql
-- Identify slow queries
SELECT query_time, lock_time, rows_sent, rows_examined, sql_text
FROM mysql.slow_log
WHERE query_time > 1.0
ORDER BY query_time DESC LIMIT 10;

-- Index usage analysis
SELECT object_schema, object_name, index_name, count_read, count_write
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = 'acme_corp_csr'
ORDER BY count_read DESC;

-- Table growth monitoring
SELECT
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    ROUND((data_length / 1024 / 1024), 2) AS 'Data (MB)',
    ROUND((index_length / 1024 / 1024), 2) AS 'Index (MB)'
FROM information_schema.tables
WHERE table_schema = 'acme_corp_csr'
ORDER BY (data_length + index_length) DESC;
```

### Backup and Recovery

```bash
# Full backup with consistency
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --quick \
    --lock-tables=false \
    --databases acme_corp_csr | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Point-in-time recovery setup
mysql> SET GLOBAL log_bin_trust_function_creators = 1;
mysql> SET GLOBAL binlog_format = 'ROW';
```

## Data Types and Constraints

### Monetary Precision
- **DECIMAL(15,2)**: Precise financial calculations up to 13 digits + 2 decimal places
- **Multi-currency**: ISO 4217 3-character currency codes
- **Default Currency**: EUR (Euro)

### Text Internationalization
- **JSON fields**: Translatable content (title, description, mission)
- **UTF8MB4**: Full Unicode support including emojis
- **Collation**: utf8mb4_unicode_ci for proper sorting

### Date and Time Management
- **TIMESTAMP**: UTC storage with automatic timezone conversion
- **DATE**: Campaign scheduling (start_date, end_date)
- **Generated Columns**: Computed goal_percentage for performance

### Status Enumerations
```sql
-- User status lifecycle
ENUM('active','inactive','suspended')

-- Campaign status workflow
ENUM('draft','pending_approval','active','completed','cancelled','paused')

-- Donation processing states
ENUM('pending','processing','completed','failed','refunded','cancelled')

-- Payment methods
ENUM('credit_card','debit_card','bank_transfer','paypal','stripe','other')
```

## Security and Compliance

### Data Protection
- **Password Hashing**: bcrypt with configurable rounds
- **Two-Factor Authentication**: TOTP secret and recovery codes
- **PII Encryption**: Sensitive data encrypted at application level
- **Audit Trail**: Complete change tracking for compliance

### Access Control
- **Role-Based Security**: Admin, employee, organization_admin
- **Multi-Tenant Isolation**: Organization-based data separation
- **API Security**: JWT token-based authentication
- **Database Security**: Prepared statements, parameterized queries

---

**Developed and Maintained by Go2Digital**

**Copyright 2025 Go2Digital - All Rights Reserved**
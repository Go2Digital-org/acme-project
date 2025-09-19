<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     * Keep false for performance - use minimal factories instead.
     */
    protected bool $seed = false;

    /**
     * Setup the test environment for integration tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Basic configuration for integration testing
        config([
            'app.debug' => false,
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'acme_corp_csr_test',
            'database.connections.mysql.strict' => false,
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'array',
            'mail.default' => 'array',
            'scout.driver' => null,
            'logging.default' => 'null',
        ]);

        // Disable query logging for performance
        DB::disableQueryLog();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Clear any caches
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Cache may be mocked, ignore
        }

        parent::tearDown();
    }

    /**
     * Create minimal test data for performance.
     * Override this in specific test classes as needed.
     *
     * @return array<string, mixed>
     */
    protected function createTestData(): array
    {
        return [];
    }

    /**
     * Create analytics tables if they don't exist.
     * Use this sparingly and only when absolutely necessary.
     */
    protected function ensureAnalyticsTablesExist(): void
    {
        static $tablesCreated = false;

        if ($tablesCreated) {
            return;
        }

        // Create analytics_events table for testing (if not exists)
        if (! DB::getSchemaBuilder()->hasTable('analytics_events')) {
            DB::statement('CREATE TABLE analytics_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                organization_id INT,
                event_type VARCHAR(255),
                event_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_org (user_id, organization_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB');
        }

        // Create analytics_reports table for testing (if not exists)
        if (! DB::getSchemaBuilder()->hasTable('analytics_reports')) {
            DB::statement('CREATE TABLE analytics_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                type VARCHAR(255),
                format VARCHAR(255),
                user_id INT,
                organization_id INT,
                parameters JSON,
                filters JSON,
                data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_organization_id (organization_id)
            ) ENGINE=InnoDB');
        }

        $tablesCreated = true;
    }

    /**
     * Create application_cache table if it doesn't exist.
     * Use this for Analytics and Widget tests.
     */
    protected function ensureApplicationCacheTableExists(): void
    {
        static $tableCreated = false;

        if ($tableCreated) {
            return;
        }

        if (! DB::getSchemaBuilder()->hasTable('application_cache')) {
            DB::statement('CREATE TABLE application_cache (
                cache_key VARCHAR(255) PRIMARY KEY,
                stats_data JSON NULL,
                calculated_at TIMESTAMP NULL,
                calculation_time_ms INT DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_cache_key (cache_key),
                INDEX idx_calculated_at (calculated_at)
            ) ENGINE=InnoDB');
        } else {
            // Ensure stats_data column exists if table already exists
            if (! DB::getSchemaBuilder()->hasColumn('application_cache', 'stats_data')) {
                DB::statement('ALTER TABLE application_cache ADD COLUMN stats_data JSON NULL AFTER cache_key');
            }
        }

        $tableCreated = true;
    }
}

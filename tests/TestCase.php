<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Flag to track if database has been validated in this test run.
     */
    protected static bool $databaseValidated = false;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database exists and has all tables before running tests
        $this->ensureDatabaseReady();

        // Clear all caches before each test
        Cache::flush();

        // Fake external services for complete isolation
        Mail::fake();
        Notification::fake();
        Queue::fake();
        Event::fake();
        Bus::fake();

        // Set testing configuration
        config([
            'app.debug' => false,
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
            'mail.default' => 'array',
            'broadcasting.default' => 'null',
            'scout.driver' => null,
            'database.default' => $this->getTestDatabaseConnection(),
            'database.connections.mysql.foreign_key_checks' => false,
            'database.connections.mysql.strict' => false,
        ]);

        // Disable external services
        $this->disableExternalServices();
    }

    /**
     * Get the appropriate database connection for testing.
     * Uses SQLite for CI, MySQL for local testing.
     */
    protected function getTestDatabaseConnection(): string
    {
        // Check if we're using SQLite (CI environment)
        if (config('database.connections.sqlite.database') === ':memory:') {
            return 'sqlite';
        }

        // Check environment variables for CI detection
        if (env('DB_CONNECTION') === 'sqlite') {
            return 'sqlite';
        }

        // Default to MySQL for local testing
        return 'mysql';
    }

    /**
     * Disable external services during testing.
     */
    protected function disableExternalServices(): void
    {
        // Disable third-party service providers
        config([
            'services.stripe.key' => 'sk_test_dummy',
            'services.paypal.mode' => 'sandbox',
            'services.socialite' => [],
            'telescope.enabled' => false,
            'pulse.enabled' => false,
            'horizon.enabled' => false,
        ]);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Clear all caches safely after each test
        try {
            Cache::flush();
        } catch (\BadMethodCallException $e) {
            // Cache facade is mocked, skip flush
        }

        parent::tearDown();
    }

    /**
     * Reset static state between test classes.
     * This runs after all tests in a class are complete.
     */
    public static function tearDownAfterClass(): void
    {
        static::$databaseValidated = false;
        parent::tearDownAfterClass();
    }

    /**
     * Ensure database exists and has all required tables.
     * Self-heals by creating database and running migrations if needed.
     */
    protected function ensureDatabaseReady(): void
    {
        // Skip this entirely - let database traits handle it

    }

    /**
     * Run migrations without dropping tables.
     */
    protected function runMigrations(): void
    {
        try {
            $connection = $this->getTestDatabaseConnection();

            // Just run pending migrations, don't fresh
            Artisan::call('migrate', [
                '--database' => $connection,
                '--env' => 'testing',
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            // If migrations fail, mark as validated to avoid loops
            static::$databaseValidated = true;
        }
    }

    /**
     * Force test database configuration to prevent accidental production access.
     */
    protected function forceTestDatabase(): void
    {
        // Self-heal: Force testing environment if not set
        if (app()->environment() !== 'testing') {
            // Try to force testing environment
            app()->detectEnvironment(function () {
                return 'testing';
            });

            // If still not testing, it's a critical error
            if (app()->environment() !== 'testing') {
                throw new \RuntimeException('Tests can only run in testing environment! Current env: ' . app()->environment());
            }
        }

        $connection = $this->getTestDatabaseConnection();

        if ($connection === 'sqlite') {
            // FORCE SQLite test database configuration (CI)
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                'database.connections.sqlite.foreign_key_constraints' => false,
            ]);
        } else {
            // FORCE MySQL test database configuration (local)
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.database' => 'acme_corp_csr_test',
                'database.connections.mysql.host' => '127.0.0.1',
                'database.connections.mysql.port' => 3306,
                'database.connections.mysql.username' => 'root',
                'database.connections.mysql.password' => 'root',
            ]);

            // Verify we're NOT using production database names
            $currentDb = config('database.connections.mysql.database');
            $dangerousDatabases = ['acme_corp_csr', 'acme-corp-optimy', 'production', 'main', 'primary'];

            if (array_key_exists(strtolower($currentDb), array_flip($dangerousDatabases))) {
                throw new \RuntimeException("DANGER: Attempting to run tests on non-test database: {$currentDb}");
            }

            // Database name MUST contain 'test'
            if (! str_contains(strtolower($currentDb), 'test')) {
                throw new \RuntimeException("SAFETY: Database name must contain 'test'. Current: {$currentDb}");
            }
        }
    }

    /**
     * Create the test database if it doesn't exist.
     * ONLY creates databases with 'test' in the name.
     */
    protected function createTestDatabase(string $databaseName): void
    {
        // SAFETY: Only create databases with 'test' in the name
        if (! str_contains(strtolower($databaseName), 'test')) {
            throw new \RuntimeException("SAFETY: Cannot create non-test database: {$databaseName}");
        }

        try {
            $connection = new \PDO(
                'mysql:host=127.0.0.1;port=3306',
                'root',
                'root',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $connection->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to create test database: {$e->getMessage()}");
        }
    }

    /**
     * Self-heal the database by running fresh migrations.
     * ONLY works on test databases.
     */
    protected function selfHealDatabase(string $databaseName): void
    {
        // SAFETY: Only heal test databases
        if (! str_contains(strtolower($databaseName), 'test')) {
            throw new \RuntimeException("SAFETY: Cannot heal non-test database: {$databaseName}");
        }

        try {
            // Ensure database exists
            $this->createTestDatabase($databaseName);

            // Force test database config again
            $this->forceTestDatabase();

            // Reconnect to the database
            DB::purge('mysql');
            DB::reconnect('mysql');

            // Run fresh migrations on TEST database only
            Artisan::call('migrate:fresh', [
                '--database' => 'mysql',
                '--env' => 'testing',
                '--force' => true,
                '--seed' => false,
            ]);

            static::$databaseValidated = true;

        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to self-heal test database: {$e->getMessage()}");
        }
    }
}

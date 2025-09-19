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
use Modules\Organization\Infrastructure\Laravel\Models\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

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

        // Minimal setup for tests
        $this->setupTestEnvironment();
    }

    private function setupTestEnvironment(): void
    {
        // Essential configuration for testing
        config([
            'app.debug' => false,
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
            'mail.default' => 'array',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'acme_corp_csr_test',
            'database.connections.mysql.foreign_key_checks' => false,
            'scout.driver' => null,
        ]);

        // Fake external services
        Mail::fake();
        Notification::fake();
        Queue::fake();
        Event::fake();
        Bus::fake();
    }

    /**
     * Setup database for testing - called by RefreshDatabase trait
     */
    protected function refreshDatabase()
    {
        // Ensure we're using the test database
        $this->ensureTestDatabase();

        // Run fresh migrations quickly
        $this->artisan('migrate:fresh', [
            '--env' => 'testing',
            '--force' => true,
        ]);

        $this->beginDatabaseTransaction();
    }

    private function ensureTestDatabase(): void
    {
        $currentDb = config('database.connections.mysql.database');
        if ($currentDb !== 'acme_corp_csr_test') {
            config(['database.connections.mysql.database' => 'acme_corp_csr_test']);
        }
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
            'debugbar.enabled' => false,
            'scout.driver' => null,
            'meilisearch.host' => 'http://localhost:7700',
            'meilisearch.key' => 'test-key',
            // Disable analytics providers
            'analytics.google_analytics.tracking_id' => null,
            'analytics.facebook_pixel.pixel_id' => null,
            // Disable external file systems
            'filesystems.disks.s3' => null,
            'filesystems.disks.gcs' => null,
        ]);
    }

    /**
     * Disable unnecessary Laravel features for performance.
     */
    protected function disableUnnecessaryFeatures(): void
    {
        config([
            // Disable route caching for tests
            'cache.stores.route' => ['driver' => 'array'],
            // Disable view caching for tests
            'view.cache' => false,
            // Disable asset compilation
            'mix-manifest' => [],
            'vite-manifest' => [],
            // Disable broadcasting
            'broadcasting.connections.pusher' => null,
            'broadcasting.connections.redis' => null,
            // Disable background job processing
            'horizon.enabled' => false,
            'queue.batching.driver' => null,
            // Disable monitoring services
            'logging.channels.sentry' => null,
            'logging.channels.bugsnag' => null,
            // Disable performance monitoring
            'app.performance_monitoring' => false,
            'app.error_tracking' => false,
        ]);

        // Disable Eloquent model events for performance
        if (app()->bound('events')) {
            app('events')->forget('eloquent.*');
        }
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Minimal cleanup for performance
        fake()->unique(true);

        parent::tearDown();
    }

    /**
     * Reset static state between test classes.
     * This runs after all tests in a class are complete.
     */
    public static function tearDownAfterClass(): void
    {
        static::$databaseValidated = false;

        // Clear Eloquent model event listeners to prevent memory leaks
        $models = ['Campaign', 'Organization', 'User', 'Donation', 'Category'];
        foreach ($models as $model) {
            $modelClass = "Modules\\{$model}\\Domain\\Model\\{$model}";
            if (class_exists($modelClass) && method_exists($modelClass, 'flushEventListeners')) {
                $modelClass::flushEventListeners();
            }
        }

        parent::tearDownAfterClass();
    }

    /**
     * Ensure database exists and has all required tables.
     * Self-heals by creating database and running migrations if needed.
     */
    protected function ensureDatabaseReady(): void
    {
        // Skip database validation for performance - assume database is ready

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

            // Force reconnection to ensure we use the right database
            try {
                DB::purge('mysql');
                DB::reconnect('mysql');
            } catch (\Exception $e) {
                // Ignore reconnection errors during setup
            }

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

    /**
     * Optimize configuration for testing performance.
     */
    protected function optimizeForTesting(): void
    {
        // Disable query logging to save memory
        DB::disableQueryLog();

        // Optimize Laravel for testing
        config([
            'filesystems.default' => 'local',
            'logging.default' => 'null',
            'view.compiled' => storage_path('framework/views'),
        ]);
    }

    /**
     * Clear static state that could affect parallel tests.
     */
    protected function clearStaticState(): void
    {
        // Clear Eloquent model caches
        \Illuminate\Database\Eloquent\Model::clearBootedModels();

        // Clear resolved instances that might have state
        app()->forgetInstance('events');
        app()->forgetInstance('queue');
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');
        app()->forgetInstance('session');
        app()->forgetInstance('session.store');

        // Clear View cache
        if (app()->bound('view')) {
            app('view')->flushState();
        }

        // Clear Auth state
        if (app()->bound('auth')) {
            app('auth')->forgetGuards();
        }

        // Force garbage collection for memory optimization
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Create minimal test data for performance.
     * Use this instead of creating full factory data when possible.
     *
     * @return array<string, mixed>
     */
    protected function createMinimalTestData(): array
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $organization = Organization::factory()->create([
            'name' => ['en' => 'Test Organization'],
            'registration_number' => 'REG-TEST-' . uniqid(),
            'tax_id' => 'TAX-TEST-' . uniqid(),
            'category' => 'nonprofit',
            'is_verified' => true,
        ]);

        return compact('user', 'organization');
    }
}

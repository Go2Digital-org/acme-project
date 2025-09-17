<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;

trait OptimizedDatabaseTesting
{
    // Don't use LazilyRefreshDatabase here - let each test case decide

    protected static bool $schemaLoaded = false;

    protected static array $migrationCache = [];

    protected static array $createdDatabases = [];

    protected int $maxDeadlockRetries = 3;

    protected int $deadlockRetryDelay = 100000; // microseconds

    /**
     * Setup optimized database for testing.
     */
    protected function setUpOptimizedDatabase(): void
    {
        $this->configureOptimizedConnection();
        $this->setupParallelDatabase();
        // Don't load schema here - let RefreshDatabase trait handle it
    }

    /**
     * Configure database connection for optimal testing performance.
     */
    protected function configureOptimizedConnection(): void
    {
        $connection = config('database.default');

        if ($connection === 'mysql') {
            DB::connection()->getPdo()->setAttribute(PDO::ATTR_TIMEOUT, 5);
            DB::connection()->getPdo()->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND,
                'SET SESSION innodb_lock_wait_timeout = 5; ' .
                'SET SESSION lock_wait_timeout = 5; ' .
                "SET SESSION transaction_isolation = 'READ-COMMITTED';"
            );

            // Optimize MySQL for testing
            // Only apply safe optimizations
            DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
            DB::statement("SET SESSION transaction_isolation = 'READ-COMMITTED'");
            // Don't disable foreign keys globally - causes issues
        }
    }

    /**
     * Setup database for parallel test execution.
     */
    protected function setupParallelDatabase(): void
    {
        if (! $this->isParallelTesting()) {
            return;
        }

        $workerId = $this->getWorkerId();
        $baseDatabase = config('database.connections.mysql.database', 'acme_corp_csr_test');
        $parallelDatabase = "{$baseDatabase}_{$workerId}";

        // Create worker-specific database
        $this->createParallelDatabase($parallelDatabase);

        // Switch to worker database
        config(['database.connections.mysql.database' => $parallelDatabase]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * Create database for parallel worker.
     */
    protected function createParallelDatabase(string $database): void
    {
        // Skip if already created in this process
        if (isset(static::$createdDatabases[$database])) {
            return;
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . config('database.connections.mysql.host', '127.0.0.1'),
                config('database.connections.mysql.username', 'root'),
                config('database.connections.mysql.password', 'root'),
                [PDO::ATTR_TIMEOUT => 5]
            );

            // Drop and recreate for clean state
            $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            static::$createdDatabases[$database] = true;
        } catch (PDOException $e) {
            // Database creation failed, tests will use fallback
        }
    }

    /**
     * Load cached database schema for faster setup.
     */
    protected function loadCachedSchema(): void
    {
        if (static::$schemaLoaded) {
            return;
        }

        $schemaPath = base_path('database/schema/mysql-schema.sql');

        if (file_exists($schemaPath)) {
            // Use cached schema dump for faster migration
            DB::unprepared(file_get_contents($schemaPath));
            static::$schemaLoaded = true;
        } else {
            // Fall back to regular migrations
            $this->artisan('migrate', ['--force' => true]);
            static::$schemaLoaded = true;
        }
    }

    /**
     * Execute database operation with deadlock retry logic.
     */
    protected function withDeadlockRetry(callable $callback, ?int $maxRetries = null)
    {
        $maxRetries = $maxRetries ?? $this->maxDeadlockRetries;
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                return DB::transaction($callback, 5);
            } catch (\Exception $e) {
                if ($this->isDeadlockException($e) && $attempts < $maxRetries - 1) {
                    $attempts++;
                    usleep($this->deadlockRetryDelay * $attempts);

                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Check if exception is a deadlock error.
     */
    protected function isDeadlockException(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'deadlock') ||
               str_contains($message, 'lock wait timeout') ||
               str_contains($message, '1213') || // MySQL deadlock error code
               str_contains($message, '1205'); // MySQL lock wait timeout
    }

    /**
     * Optimize database queries for testing.
     */
    protected function optimizeQueries(): void
    {
        // Enable query log for debugging but with limit
        if (env('LOG_QUERIES', false)) {
            DB::enableQueryLog();
            DB::listen(function ($query) {
                if ($query->time > 100) { // Log slow queries only
                    logger()->warning('Slow test query', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }

    /**
     * Truncate tables efficiently without foreign key checks.
     */
    protected function truncateTables(array $tables): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Check if running in parallel mode.
     */
    protected function isParallelTesting(): bool
    {
        return getenv('PEST_PARALLEL_WORKER') !== false ||
               getenv('PARATEST_WORKER') !== false ||
               getenv('TEST_TOKEN') !== false;
    }

    /**
     * Get parallel worker ID.
     */
    protected function getWorkerId(): int
    {
        // Pest 4 uses PEST_PARALLEL_WORKER
        if ($worker = getenv('PEST_PARALLEL_WORKER')) {
            return (int) $worker;
        }

        // Fallback to ParaTest worker ID
        if ($token = getenv('TEST_TOKEN')) {
            return (int) substr($token, -1);
        }

        if ($worker = getenv('PARATEST_WORKER')) {
            return (int) $worker;
        }

        return 0;
    }

    /**
     * Clean up after test.
     */
    protected function tearDownOptimizedDatabase(): void
    {
        // Clear query log to free memory
        DB::disableQueryLog();

        // Reset connection settings
        if (config('database.default') === 'mysql') {
            try {
                DB::statement('SET SESSION foreign_key_checks = 1');
                DB::statement('SET SESSION unique_checks = 1');
            } catch (\Exception $e) {
                // Connection might be closed
            }
        }

        // Purge connections
        DB::purge('mysql');
    }

    /**
     * Run seeders efficiently.
     */
    protected function seedOptimized(array $seeders): void
    {
        $this->withDeadlockRetry(function () use ($seeders) {
            foreach ($seeders as $seeder) {
                $this->artisan('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            }
        });
    }

    /**
     * Assert database has record with retry on lock.
     */
    protected function assertDatabaseHasWithRetry(string $table, array $data): void
    {
        $this->withDeadlockRetry(function () use ($table, $data) {
            $this->assertDatabaseHas($table, $data);
        });
    }

    /**
     * Create test data with optimized batch insert.
     */
    protected function createBatchTestData(string $model, array $records): void
    {
        $chunks = array_chunk($records, 500); // Insert in chunks

        foreach ($chunks as $chunk) {
            $this->withDeadlockRetry(function () use ($model, $chunk) {
                $model::insert($chunk);
            });
        }
    }

    /**
     * Get optimized database configuration for testing.
     */
    protected function getOptimizedDatabaseConfig(): array
    {
        return [
            'mysql' => [
                'options' => [
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ],
                'pool' => [
                    'min' => 1,
                    'max' => 10,
                ],
            ],
            'sqlite' => [
                'foreign_key_constraints' => false,
                'journal_mode' => 'MEMORY',
                'synchronous' => 'OFF',
                'temp_store' => 'MEMORY',
                'mmap_size' => '268435456',
            ],
        ];
    }
}

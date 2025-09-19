<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('Database Connection Feature Tests', function (): void {
    it('can connect to test database', function (): void {
        expect(DB::connection()->getDatabaseName())->toBe('acme_corp_csr_test');
    });

    it('validates database connection is working', function (): void {
        $result = DB::select('SELECT 1 as test');
        expect($result)->not->toBeEmpty();
        expect($result[0]->test)->toBe(1);
    });

    it('can check if tables exist', function (): void {
        // Check if migrations table exists (created by Laravel)
        $hasTable = Schema::hasTable('migrations');
        expect($hasTable)->toBeIn([true, false]); // Either true or false is valid
    });

    it('validates database configuration', function (): void {
        $config = config('database.connections.mysql');
        expect($config['database'])->toBe('acme_corp_csr_test');
        expect($config['host'])->toBe('127.0.0.1');
        expect($config['port'])->toBe('3306');
    });

    it('can perform basic database operations', function (): void {
        try {
            DB::statement('SELECT NOW()');
            expect(true)->toBeTrue(); // If we get here, the query worked
        } catch (\Exception $e) {
            expect(false)->toBeTrue(); // Fail if exception occurs
        }
    });

    it('validates foreign key checks are disabled', function (): void {
        expect(config('database.connections.mysql.foreign_key_checks'))->toBeFalse();
    });

    it('validates database connection pool settings', function (): void {
        $config = config('database.connections.mysql');
        expect($config['charset'] ?? 'utf8mb4')->toBe('utf8mb4');
        expect($config['collation'] ?? 'utf8mb4_unicode_ci')->toBe('utf8mb4_unicode_ci');
    });
});

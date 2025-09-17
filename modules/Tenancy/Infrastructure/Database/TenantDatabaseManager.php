<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Database;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Domain\Model\Organization;
use Modules\Tenancy\Domain\Exception\TenantException;
use PDOException;

/**
 * Tenant Database Manager.
 *
 * Handles database creation and management for tenants.
 */
class TenantDatabaseManager
{
    /**
     * Create a database for the tenant.
     *
     * @throws TenantException
     */
    public function createDatabase(Organization $organization): void
    {
        $databaseName = $organization->getAttribute('database') ?: $organization->getDatabaseName();

        if (! $databaseName) {
            throw TenantException::databaseCreationFailed(
                'unknown',
                'Database name not set for organization'
            );
        }

        try {
            // Create database with proper character set and collation
            DB::statement(
                "CREATE DATABASE IF NOT EXISTS `{$databaseName}` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci"
            );
        } catch (PDOException $e) {
            throw TenantException::databaseCreationFailed($databaseName, $e->getMessage());
        }
    }

    /**
     * Drop a tenant database.
     *
     * WARNING: This permanently deletes all tenant data!
     *
     * @throws TenantException
     */
    public function dropDatabase(Organization $organization): void
    {
        $databaseName = $organization->database;

        if (! $databaseName) {
            return; // Nothing to drop
        }

        try {
            DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
        } catch (PDOException $e) {
            throw TenantException::databaseCreationFailed(
                $databaseName,
                'Failed to drop database: ' . $e->getMessage()
            );
        }
    }

    /**
     * Check if a database exists.
     */
    public function databaseExists(string $databaseName): bool
    {
        $result = DB::select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$databaseName]
        );

        return count($result) > 0;
    }

    /**
     * Get database size in MB.
     */
    public function getDatabaseSize(string $databaseName): float
    {
        $result = DB::select(
            'SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = ?',
            [$databaseName]
        );

        return (float) ($result[0]->size_mb ?? 0);
    }

    /**
     * Get table count for a database.
     */
    public function getTableCount(string $databaseName): int
    {
        $result = DB::select(
            'SELECT COUNT(*) as table_count 
            FROM information_schema.TABLES 
            WHERE table_schema = ?',
            [$databaseName]
        );

        return (int) ($result[0]->table_count ?? 0);
    }

    /**
     * Backup tenant database.
     *
     * @return string Path to backup file
     */
    public function backupDatabase(Organization $organization): string
    {
        $databaseName = $organization->database;
        $backupPath = storage_path('app/tenant-backups');

        // Ensure backup directory exists
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filename = sprintf(
            '%s/%s_%s_backup_%s.sql',
            $backupPath,
            $organization->subdomain,
            $databaseName,
            now()->format('Y-m-d_H-i-s')
        );

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg((string) config('database.connections.mysql.host')),
            escapeshellarg((string) config('database.connections.mysql.port')),
            escapeshellarg((string) config('database.connections.mysql.username')),
            escapeshellarg((string) config('database.connections.mysql.password')),
            escapeshellarg($databaseName ?? ''),
            escapeshellarg($filename)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw TenantException::databaseCreationFailed(
                $databaseName ?? 'unknown',
                'Backup failed: ' . implode("\n", $output)
            );
        }

        return $filename;
    }

    /**
     * Restore tenant database from backup.
     */
    public function restoreDatabase(Organization $organization, string $backupFile): void
    {
        if (! file_exists($backupFile)) {
            throw TenantException::databaseCreationFailed(
                $organization->database ?? 'unknown',
                "Backup file not found: {$backupFile}"
            );
        }

        $databaseName = $organization->database;

        // First ensure database exists
        $this->createDatabase($organization);

        $command = sprintf(
            'mysql -h %s -P %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg((string) config('database.connections.mysql.host')),
            escapeshellarg((string) config('database.connections.mysql.port')),
            escapeshellarg((string) config('database.connections.mysql.username')),
            escapeshellarg((string) config('database.connections.mysql.password')),
            escapeshellarg($databaseName ?? ''),
            escapeshellarg($backupFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw TenantException::databaseCreationFailed(
                $databaseName ?? 'unknown',
                'Restore failed: ' . implode("\n", $output)
            );
        }
    }
}

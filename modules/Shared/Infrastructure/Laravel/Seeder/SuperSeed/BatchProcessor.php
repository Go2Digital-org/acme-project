<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use RuntimeException;

/**
 * High-Performance Batch Processor for Trillion-Scale Data Inserts
 *
 * Features:
 * - Raw SQL batch inserts for maximum speed
 * - Memory-efficient data chunking
 * - Transaction management
 * - Duplicate key handling
 * - Progress tracking
 */
class BatchProcessor
{
    /** @var array<string, mixed> */
    private array $columnCache = [];

    private int $maxChunkSize = 50000; // Max records per single INSERT

    public function __construct(private readonly string $tableName) {}

    /**
     * Insert batch of records using raw SQL for maximum performance
     *
     * @param  list<array<string, mixed>>  $records
     */
    public function insertBatch(array $records): int
    {
        if ($records === []) {
            return 0;
        }

        $totalInserted = 0;

        // Process in chunks to avoid query size limits
        $chunks = array_chunk($records, max(1, $this->maxChunkSize));

        DB::beginTransaction();

        try {
            foreach ($chunks as $chunk) {
                $totalInserted += $this->insertChunk($chunk);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw new RuntimeException('Batch insert failed: ' . $e->getMessage(), 0, $e);
        }

        return $totalInserted;
    }

    /**
     * Insert a single chunk using prepared statement for speed
     *
     * @param  list<array<string, mixed>>  $records
     */
    private function insertChunk(array $records): int
    {
        if ($records === []) {
            return 0;
        }

        if (! isset($records[0])) {
            return 0;
        }

        $columns = array_keys($records[0]);
        $this->validateColumns($columns);

        // Build optimized INSERT statement
        $sql = $this->buildInsertSQL($columns, count($records));

        // Flatten values for prepared statement
        $values = $this->flattenValues($records, $columns);

        // Execute with raw PDO for maximum speed
        $rowsInserted = DB::insert($sql, $values);

        return $rowsInserted ? count($records) : 0;
    }

    /**
     * Build highly optimized INSERT SQL statement
     */
    /**
     * @param  list<string>  $columns
     */
    private function buildInsertSQL(array $columns, int $recordCount): string
    {
        $columnList = '`' . implode('`, `', $columns) . '`';
        $placeholderSet = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $allPlaceholders = str_repeat($placeholderSet . ',', $recordCount - 1) . $placeholderSet;

        // Use INSERT IGNORE for performance - skip duplicates without error
        return "INSERT IGNORE INTO `{$this->tableName}` ({$columnList}) VALUES {$allPlaceholders}";
    }

    /**
     * Flatten 2D array of records into 1D array for prepared statement
     *
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $columns
     * @return list<mixed>
     */
    private function flattenValues(array $records, array $columns): array
    {
        $flatValues = [];

        foreach ($records as $record) {
            foreach ($columns as $column) {
                $value = $record[$column] ?? null;

                // Convert objects to strings
                if (is_object($value)) {
                    $value = method_exists($value, '__toString') ? (string) $value : null;
                }

                // Convert boolean to int for MySQL
                if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }

                $flatValues[] = $value;
            }
        }

        return $flatValues;
    }

    /**
     * Validate columns exist in target table (cached for performance)
     */
    /**
     * @param  list<string>  $columns
     */
    private function validateColumns(array $columns): void
    {
        if ($this->columnCache === []) {
            $this->columnCache = $this->getTableColumns();
        }

        $invalidColumns = array_diff($columns, array_keys($this->columnCache));

        if ($invalidColumns !== []) {
            throw new RuntimeException(
                "Invalid columns for table {$this->tableName}: " . implode(', ', $invalidColumns)
            );
        }
    }

    /**
     * Get table columns from database schema (cached)
     */
    /**
     * @return array<string, mixed>
     */
    private function getTableColumns(): array
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($this->tableName);

        if (empty($columns)) {
            throw new RuntimeException("Table {$this->tableName} does not exist or has no columns");
        }

        return array_fill_keys($columns, true);
    }

    /**
     * Insert with upsert capability (INSERT ... ON DUPLICATE KEY UPDATE)
     *
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $updateColumns
     */
    public function upsertBatch(array $records, array $updateColumns = []): int
    {
        if ($records === []) {
            return 0;
        }

        $totalProcessed = 0;
        $chunks = array_chunk($records, max(1, $this->maxChunkSize));

        DB::beginTransaction();

        try {
            foreach ($chunks as $chunk) {
                $totalProcessed += $this->upsertChunk($chunk, $updateColumns);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw new RuntimeException('Batch upsert failed: ' . $e->getMessage(), 0, $e);
        }

        return $totalProcessed;
    }

    /**
     * Upsert a single chunk
     *
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $updateColumns
     */
    private function upsertChunk(array $records, array $updateColumns): int
    {
        if ($records === []) {
            return 0;
        }

        if (! isset($records[0])) {
            return 0;
        }

        $columns = array_keys($records[0]);
        $this->validateColumns($columns);

        $sql = $this->buildUpsertSQL($columns, count($records), $updateColumns);
        $values = $this->flattenValues($records, $columns);

        DB::statement($sql, $values);

        return count($records);
    }

    /**
     * Build UPSERT SQL with ON DUPLICATE KEY UPDATE
     */
    /**
     * @param  list<string>  $columns
     * @param  list<string>  $updateColumns
     */
    private function buildUpsertSQL(array $columns, int $recordCount, array $updateColumns): string
    {
        $columnList = '`' . implode('`, `', $columns) . '`';
        $placeholderSet = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $allPlaceholders = str_repeat($placeholderSet . ',', $recordCount - 1) . $placeholderSet;

        $sql = "INSERT INTO `{$this->tableName}` ({$columnList}) VALUES {$allPlaceholders}";

        if ($updateColumns !== []) {
            $updateClauses = [];
            foreach ($updateColumns as $column) {
                $updateClauses[] = "`{$column}` = VALUES(`{$column}`)";
            }
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateClauses);
        }

        return $sql;
    }

    /**
     * Bulk delete records by IDs for cleanup operations
     */
    /**
     * @param  list<mixed>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $chunks = array_chunk($ids, max(1, $this->maxChunkSize));
        $totalDeleted = 0;

        foreach ($chunks as $chunk) {
            $placeholders = str_repeat('?,', count($chunk) - 1) . '?';
            $sql = "DELETE FROM `{$this->tableName}` WHERE id IN ({$placeholders})";

            $affected = DB::delete($sql, $chunk);
            $totalDeleted += $affected;
        }

        return $totalDeleted;
    }

    /**
     * Get table statistics for optimization
     */
    /**
     * @return array<string, mixed>
     */
    public function getTableStats(): array
    {
        $stats = DB::select("SHOW TABLE STATUS LIKE '{$this->tableName}'")[0] ?? null;

        if (! $stats) {
            return [];
        }

        return [
            'rows' => $stats->Rows ?? 0,
            'data_length' => $stats->Data_length ?? 0,
            'index_length' => $stats->Index_length ?? 0,
            'auto_increment' => $stats->Auto_increment ?? 1,
            'avg_row_length' => $stats->Avg_row_length ?? 0,
        ];
    }

    /**
     * Optimize table after bulk operations
     */
    public function optimizeTable(): void
    {
        try {
            DB::statement("OPTIMIZE TABLE `{$this->tableName}`");
        } catch (Exception $e) {
            // Optimization is not critical - just log and continue
            Log::warning(
                "Table optimization failed for {$this->tableName}: " . $e->getMessage()
            );
        }
    }

    /**
     * Reset table auto-increment value
     */
    public function resetAutoIncrement(int $value = 1): void
    {
        DB::statement("ALTER TABLE `{$this->tableName}` AUTO_INCREMENT = {$value}");
    }

    /**
     * Set maximum chunk size for batch operations
     */
    public function setMaxChunkSize(int $size): void
    {
        $this->maxChunkSize = max(1000, min(100000, $size));
    }

    /**
     * Get current chunk size
     */
    public function getMaxChunkSize(): int
    {
        return $this->maxChunkSize;
    }

    /**
     * Check if table exists
     */
    public function tableExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable($this->tableName);
    }
}

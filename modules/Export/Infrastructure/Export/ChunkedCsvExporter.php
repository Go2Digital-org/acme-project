<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Export;

use Exception;
use SplFileObject;

/**
 * Memory-efficient CSV exporter that streams data directly to file.
 * Features:
 * - Streaming output to handle large datasets without memory issues
 * - UTF-8 BOM support for proper Excel compatibility
 * - Configurable delimiter and enclosure
 * - Automatic file handle management
 * - Memory-efficient chunked writing
 */
class ChunkedCsvExporter
{
    private ?SplFileObject $file = null;

    private bool $headerWritten = false;

    public function __construct(
        private readonly string $filePath,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
        private readonly bool $includeBom = true
    ) {
        $this->initializeFile();
    }

    /**
     * Initialize the file for writing
     */
    private function initializeFile(): void
    {
        $directory = dirname($this->filePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new Exception("Failed to create directory: {$directory}");
        }

        $this->file = new SplFileObject($this->filePath, 'w');

        if (! $this->file->isWritable()) {
            throw new Exception("Failed to create writable file: {$this->filePath}");
        }

        // Add UTF-8 BOM for Excel compatibility
        if ($this->includeBom) {
            $this->file->fwrite("\xEF\xBB\xBF");
        }

        // Set CSV control characters
        $this->file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * Write CSV headers to the file
     *
     * @param  array<int, string>|null  $headers  Optional headers to write. If not provided, uses default donation headers
     */
    public function writeHeaders(?array $headers = null): void
    {
        if ($this->headerWritten) {
            return;
        }

        if ($headers === null) {
            $headers = [
                'ID',
                'Campaign ID',
                'Campaign Title',
                'User ID',
                'User Name',
                'User Email',
                'Amount',
                'Currency',
                'Payment Method',
                'Payment Gateway',
                'Transaction ID',
                'Status',
                'Anonymous',
                'Recurring',
                'Recurring Frequency',
                'Donated At',
                'Processed At',
                'Completed At',
                'Corporate Match Amount',
                'Notes',
                'Created At',
                'Updated At',
            ];
        }

        $this->writeRow($headers);
        $this->headerWritten = true;
    }

    /**
     * Write multiple rows to the CSV file
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function writeRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->writeRow($row);
        }
    }

    /**
     * Write a single row to the CSV file
     *
     * @param  array<int, string>|array<string, mixed>  $row
     */
    public function writeRow(array $row): void
    {
        if (! $this->file instanceof SplFileObject) {
            throw new Exception('CSV file not initialized');
        }

        // Ensure all values are strings and handle nulls
        $processedRow = array_map(function ($value): string {
            if ($value === null) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                return json_encode($value, JSON_THROW_ON_ERROR);
            }

            return (string) $value;
        }, $row);

        // Write the row using SplFileObject's CSV functionality
        $this->file->fputcsv($processedRow, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * Write raw CSV data (already formatted)
     */
    public function writeRaw(string $data): void
    {
        if (! $this->file instanceof SplFileObject) {
            throw new Exception('CSV file not initialized');
        }

        $this->file->fwrite($data);
    }

    /**
     * Get the current file size in bytes
     */
    public function getFileSize(): int
    {
        if (! $this->file instanceof SplFileObject) {
            return 0;
        }

        return $this->file->getSize() ?? 0;
    }

    /**
     * Flush any buffered data to the file
     */
    public function flush(): void
    {
        if ($this->file instanceof SplFileObject) {
            $this->file->fflush();
        }
    }

    /**
     * Get the file path
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Check if the file is ready for writing
     */
    public function isReady(): bool
    {
        return $this->file instanceof SplFileObject && $this->file->isWritable();
    }

    /**
     * Get current position in the file
     */
    public function getCurrentPosition(): int
    {
        if (! $this->file instanceof SplFileObject) {
            return 0;
        }

        $position = $this->file->ftell();

        return $position !== false ? $position : 0;
    }

    /**
     * Add a comment line to the CSV (prefixed with #)
     */
    public function writeComment(string $comment): void
    {
        $this->writeRaw("# {$comment}\n");
    }

    /**
     * Write a blank line
     */
    public function writeBlankLine(): void
    {
        $this->writeRaw("\n");
    }

    /**
     * Validate that all rows have the same number of columns
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function validateRowStructure(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        if (! isset($rows[0])) {
            return true;
        }

        $expectedColumnCount = count($rows[0]);

        foreach ($rows as $index => $row) {
            if (count($row) !== $expectedColumnCount) {
                throw new Exception(
                    "Row {$index} has " . count($row) . " columns, expected {$expectedColumnCount}"
                );
            }
        }

        return true;
    }

    /**
     * Close the file and release resources
     */
    public function close(): void
    {
        if ($this->file instanceof SplFileObject) {
            $this->file->fflush();
            $this->file = null;
        }
    }

    /**
     * Destructor to ensure file is closed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get CSV configuration
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'escape' => $this->escape,
            'include_bom' => $this->includeBom,
            'file_path' => $this->filePath,
        ];
    }

    /**
     * Estimate memory usage (rough calculation)
     */
    public function getEstimatedMemoryUsage(): string
    {
        $fileSize = $this->getFileSize();

        // SplFileObject typically uses minimal memory
        $estimatedMemory = 1024; // Base memory for file handle

        return number_format($estimatedMemory / 1024, 2) . ' KB (file size: ' .
               number_format($fileSize / 1024, 2) . ' KB)';
    }
}

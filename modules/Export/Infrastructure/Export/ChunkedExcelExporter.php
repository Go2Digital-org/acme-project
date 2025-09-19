<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Export;

use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Memory-efficient Excel exporter that processes data in chunks to handle large datasets.
 * Features:
 * - Memory-efficient chunked processing
 * - Professional styling with headers and formatting
 * - Automatic column width adjustment
 * - Data validation and type handling
 * - Multiple worksheet support
 * - Memory cleanup and optimization
 *
 * Note: Requires phpoffice/phpspreadsheet package
 */
class ChunkedExcelExporter
{
    private ?Spreadsheet $spreadsheet = null;

    private ?Worksheet $worksheet = null;

    private ?Xlsx $writer = null;

    private int $currentRow = 1;

    private bool $headerWritten = false;

    private int $currentWorksheetNumber = 1;

    // Column configuration for auto-width calculation
    /** @var array<string, mixed> */
    private array $columnWidths = [];

    /** @var array<string, mixed> */
    private array $headerMapping = [];

    public function __construct(
        private readonly string $filePath,
        private readonly int $maxRowsPerWorksheet = 1000000 // Excel limit is ~1M rows
    ) {
        $this->initializeSpreadsheet();
    }

    /**
     * Initialize the spreadsheet and worksheet
     */
    private function initializeSpreadsheet(): void
    {
        // Check if PhpSpreadsheet is available
        if (! class_exists(Spreadsheet::class)) {
            throw new Exception(
                'PhpSpreadsheet library is required for Excel export. ' .
                'Install it with: composer require phpoffice/phpspreadsheet'
            );
        }

        $directory = dirname($this->filePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new Exception("Failed to create directory: {$directory}");
        }

        $this->spreadsheet = new Spreadsheet;
        $this->worksheet = $this->spreadsheet->getActiveSheet();
        $this->worksheet->setTitle('Donations Export');

        // Set default properties
        $this->spreadsheet->getProperties()
            ->setCreator('ACME Corp CSR Platform')
            ->setTitle('Donation Export')
            ->setSubject('Donation Data Export')
            ->setDescription('Export of donation data from ACME Corp CSR Platform');

        $this->initializeColumnMappings();
    }

    /**
     * Initialize column mappings and widths
     */
    /**
     * @param  array<int, string>|null  $headers
     */
    private function initializeColumnMappings(?array $headers = null): void
    {
        if ($headers === null) {
            // Default donation headers
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
                'Organization Name',
                'Created At',
                'Updated At',
            ];
        }

        // Build header mapping from array
        $this->headerMapping = [];
        $column = 'A';
        foreach ($headers as $header) {
            $this->headerMapping[$column] = $header;
            $this->columnWidths[$column] = strlen((string) $header) + 2;
            $column++;
        }
    }

    /**
     * Write headers to the Excel file
     *
     * @param  array<int, string>|null  $headers  Optional headers to write. If not provided, uses default donation headers
     */
    public function writeHeaders(?array $headers = null): void
    {
        if ($this->headerWritten) {
            return;
        }

        // Setup headers if provided
        if ($headers !== null) {
            $this->initializeColumnMappings($headers);
        }

        $headerRow = array_values($this->headerMapping);
        /** @var array<int, string> $headerRow */
        $this->writeHeaderRow($headerRow);
        $this->headerWritten = true;
    }

    /**
     * Write styled header row
     *
     * @param  array<int, string>  $headers
     */
    private function writeHeaderRow(array $headers): void
    {
        $column = 'A';

        foreach ($headers as $header) {
            if ($this->worksheet instanceof Worksheet) {
                $this->worksheet->setCellValue($column . $this->currentRow, $header);
            }

            // Update column width if header is longer
            $this->updateColumnWidth($column, $header);

            $column++;
        }

        $this->applyHeaderStyling();
        $this->currentRow++;
    }

    /**
     * Apply professional styling to headers
     */
    private function applyHeaderStyling(): void
    {
        $headerRange = 'A' . $this->currentRow . ':' .
                      array_keys($this->headerMapping)[count($this->headerMapping) - 1] .
                      $this->currentRow;

        if ($this->worksheet instanceof Worksheet) {
            $this->worksheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Set header row height
        if ($this->worksheet instanceof Worksheet) {
            $this->worksheet->getRowDimension($this->currentRow)->setRowHeight(25);
        }
    }

    /**
     * Write multiple rows to the Excel file
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function writeRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->writeRow($row);

            // Check if we need a new worksheet
            if ($this->currentRow > $this->maxRowsPerWorksheet) {
                $this->createNewWorksheet();
            }
        }
    }

    /**
     * Write a single row to the Excel file
     *
     * @param  array<string, mixed>  $row
     */
    public function writeRow(array $row): void
    {
        if (! $this->worksheet instanceof Worksheet) {
            throw new Exception('Excel worksheet not initialized');
        }

        $column = 'A';

        foreach ($row as $value) {
            $processedValue = $this->processValue($value);
            $this->worksheet?->setCellValue($column . $this->currentRow, $processedValue);

            // Update column width based on content
            $this->updateColumnWidth($column, (string) $processedValue);

            $column++;
        }

        // Apply row styling every few rows for performance
        if ($this->currentRow % 100 === 0) {
            $this->applyDataRowStyling($this->currentRow - 99, $this->currentRow);
        }

        $this->currentRow++;
    }

    /**
     * Process value for Excel cell
     */
    private function processValue(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        // Handle large numbers to prevent scientific notation
        if (is_numeric($value) && strlen((string) $value) > 10) {
            return (string) $value;
        }

        return $value;
    }

    /**
     * Update column width based on content
     */
    private function updateColumnWidth(string $column, string $content): void
    {
        $contentLength = strlen($content);

        if ($contentLength > ($this->columnWidths[$column] ?? 0)) {
            $this->columnWidths[$column] = min($contentLength + 2, 50); // Max width 50
        }
    }

    /**
     * Apply styling to data rows
     */
    private function applyDataRowStyling(int $startRow, int $endRow): void
    {
        $dataRange = 'A' . $startRow . ':' .
                    array_keys($this->headerMapping)[count($this->headerMapping) - 1] .
                    $endRow;

        if ($this->worksheet instanceof Worksheet) {
            $this->worksheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);
        }

        // Alternate row colors
        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($row % 2 === 0) {
                $rowRange = 'A' . $row . ':' .
                           array_keys($this->headerMapping)[count($this->headerMapping) - 1] .
                           $row;

                if ($this->worksheet instanceof Worksheet) {
                    $this->worksheet->getStyle($rowRange)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F2F2F2'],
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Create a new worksheet when row limit is reached
     */
    private function createNewWorksheet(): void
    {
        $this->currentWorksheetNumber++;
        $worksheetName = 'Donations Export ' . $this->currentWorksheetNumber;

        if ($this->spreadsheet instanceof Spreadsheet) {
            $this->worksheet = $this->spreadsheet->createSheet();
            $this->worksheet->setTitle($worksheetName);
        }

        $this->currentRow = 1;
        $this->headerWritten = false;

        // Write headers for new worksheet
        $this->writeHeaders();
    }

    /**
     * Apply final formatting and optimizations
     */
    private function finalizeWorksheet(): void
    {
        // Apply final row styling for remaining rows
        $lastStyledRow = ((int) (($this->currentRow - 1) / 100)) * 100;
        if ($lastStyledRow < $this->currentRow - 1) {
            $this->applyDataRowStyling($lastStyledRow + 1, $this->currentRow - 1);
        }

        // Set column widths
        if ($this->worksheet instanceof Worksheet) {
            foreach ($this->columnWidths as $column => $width) {
                $this->worksheet->getColumnDimension($column)->setWidth($width);
            }

            // Freeze header row
            $this->worksheet->freezePane('A2');

            // Set default row height
            $this->worksheet->getDefaultRowDimension()->setRowHeight(20);

            // Auto-filter on header row
            $filterRange = 'A1:' .
                          array_keys($this->headerMapping)[count($this->headerMapping) - 1] .
                          '1';
            $this->worksheet->setAutoFilter($filterRange);
        }
    }

    /**
     * Get file size estimation
     */
    public function getEstimatedFileSize(): int
    {
        // Rough estimation: ~1KB per row with formatting
        return ($this->currentRow - 1) * 1024;
    }

    /**
     * Get current row count
     */
    public function getCurrentRowCount(): int
    {
        return $this->currentRow - 1; // Subtract 1 for header
    }

    /**
     * Get worksheet count
     */
    public function getWorksheetCount(): int
    {
        return $this->currentWorksheetNumber;
    }

    /**
     * Get memory usage information
     *
     * @return array<string, mixed>
     */
    public function getMemoryUsage(): array
    {
        return [
            'current_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'memory_formatted' => number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_formatted' => number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }

    /**
     * Force garbage collection and cleanup
     */
    public function cleanup(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Save and close the Excel file
     */
    public function close(): void
    {
        if (! $this->spreadsheet instanceof Spreadsheet) {
            return;
        }

        try {
            // Finalize all worksheets
            $this->finalizeWorksheet();

            // Create writer
            if (! $this->spreadsheet instanceof Spreadsheet) {
                throw new Exception('Spreadsheet is not initialized');
            }
            $this->writer = new Xlsx($this->spreadsheet);

            // Optimize for memory usage
            $this->writer->setPreCalculateFormulas(false);

            // Save the file
            $this->writer->save($this->filePath);

            // Clean up
            $this->spreadsheet->disconnectWorksheets();

        } catch (Exception $e) {
            throw new Exception('Failed to save Excel file: ' . $e->getMessage(), $e->getCode(), $e);
        } finally {
            // Release resources
            $this->spreadsheet = null;
            $this->worksheet = null;
            $this->writer = null;

            // Force garbage collection
            $this->cleanup();
        }
    }

    /**
     * Get file path
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Check if exporter is ready
     */
    public function isReady(): bool
    {
        return $this->spreadsheet instanceof Spreadsheet && $this->worksheet instanceof Worksheet;
    }

    /**
     * Get export statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'file_path' => $this->filePath,
            'rows_written' => $this->currentRow - 1,
            'worksheets_created' => $this->currentWorksheetNumber,
            'estimated_file_size' => $this->getEstimatedFileSize(),
            'memory_usage' => $this->getMemoryUsage(),
        ];
    }

    /**
     * Destructor to ensure cleanup
     */
    public function __destruct()
    {
        $this->close();
    }
}

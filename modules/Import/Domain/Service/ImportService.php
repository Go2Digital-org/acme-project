<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Service;

use Modules\Import\Domain\Model\ImportJob;

interface ImportService
{
    /**
     * Start the import process for the given import job
     */
    public function startImport(ImportJob $importJob): void;

    /**
     * Process import job data
     */
    public function processImport(ImportJob $importJob): void;

    /**
     * Validate import file format and structure
     *
     * @param  array<string, string>  $mapping
     * @return array<string>
     */
    public function validateImportFile(string $filePath, array $mapping): array;

    /**
     * Get supported file formats for import
     *
     * @return array<string>
     */
    public function getSupportedFormats(): array;

    /**
     * Get required columns for import type
     *
     * @return array<string>
     */
    public function getRequiredColumns(string $importType): array;

    /**
     * Preview import data
     *
     * @return array<array<string, mixed>>
     */
    public function previewImportData(string $filePath, int $rowLimit = 10): array;
}

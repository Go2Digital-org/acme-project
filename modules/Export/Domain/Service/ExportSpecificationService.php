<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Service;

use Carbon\Carbon;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\ValueObject\ExportFormat;

final readonly class ExportSpecificationService
{
    private const MAX_CONCURRENT_EXPORTS_PER_USER = 3;

    private const MAX_CONCURRENT_EXPORTS_PER_ORGANIZATION = 10;

    private const DEFAULT_EXPORT_EXPIRY_HOURS = 72;

    private const MAX_EXPORT_RECORDS_LIMIT = 100000;

    private const MIN_TIME_BETWEEN_IDENTICAL_EXPORTS_MINUTES = 15;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function validateExportRequest(
        int $userId,
        int $organizationId,
        string $resourceType,
        ExportFormat $format,
        array $filters,
        int $userActiveExports,
        int $organizationActiveExports,
        int $estimatedRecordCount = 0
    ): void {
        $this->validateConcurrencyLimits($userActiveExports, $organizationActiveExports);
        $this->validateResourceType($resourceType);
        $this->validateFilters($filters);
        $this->validateRecordCount($estimatedRecordCount);
        $this->validateFormatSpecificRules($format, $estimatedRecordCount);
    }

    public function canUserRequestExport(int $userActiveExports): bool
    {
        return $userActiveExports < self::MAX_CONCURRENT_EXPORTS_PER_USER;
    }

    public function canOrganizationRequestExport(int $organizationActiveExports): bool
    {
        return $organizationActiveExports < self::MAX_CONCURRENT_EXPORTS_PER_ORGANIZATION;
    }

    public function calculateExpiryDate(?Carbon $customExpiry = null): Carbon
    {
        if ($customExpiry instanceof Carbon) {
            return $customExpiry;
        }

        return now()->addHours(self::DEFAULT_EXPORT_EXPIRY_HOURS);
    }

    public function shouldPreventDuplicateExport(
        ?Carbon $lastSimilarExportAt,
        ?Carbon $now = null
    ): bool {
        if (! $lastSimilarExportAt instanceof Carbon) {
            return false;
        }

        $now ??= now();
        $minutesSinceLastExport = $lastSimilarExportAt->diffInMinutes($now);

        return $minutesSinceLastExport < self::MIN_TIME_BETWEEN_IDENTICAL_EXPORTS_MINUTES;
    }

    public function getRecommendedBatchSize(ExportFormat $format, int $totalRecords): int
    {
        return (int) match ($format) {
            ExportFormat::CSV => min(5000, max(1000, $totalRecords / 20)),
            ExportFormat::EXCEL => min(2000, max(500, $totalRecords / 50)),
            ExportFormat::PDF => min(500, max(100, $totalRecords / 100)),
        };
    }

    public function getEstimatedProcessingTimeMinutes(ExportFormat $format, int $recordCount): int
    {
        $baseTimePerRecord = match ($format) {
            ExportFormat::CSV => 0.001,      // 1ms per record
            ExportFormat::EXCEL => 0.005,    // 5ms per record
            ExportFormat::PDF => 0.02,       // 20ms per record
        };

        $estimatedSeconds = $recordCount * $baseTimePerRecord;
        $estimatedMinutes = max(1, ceil($estimatedSeconds / 60));

        // Add overhead for large datasets
        if ($recordCount > 10000) {
            $estimatedMinutes += ceil($recordCount / 10000) * 2;
        }

        return (int) $estimatedMinutes;
    }

    public function getExportPriority(
        int $recordCount,
        ExportFormat $format,
        bool $isOrganizationAdmin = false
    ): int {
        $basePriority = 100;

        // Adjust for record count (smaller exports get higher priority)
        if ($recordCount < 1000) {
            $basePriority += 20;
        } elseif ($recordCount > 50000) {
            $basePriority -= 30;
        }

        // Adjust for format complexity
        $basePriority += match ($format) {
            ExportFormat::CSV => 10,
            ExportFormat::EXCEL => 0,
            ExportFormat::PDF => -10,
        };

        // Admin exports get higher priority
        if ($isOrganizationAdmin) {
            $basePriority += 15;
        }

        return max(1, $basePriority);
    }

    public function isExportSizeOptimal(int $recordCount, ExportFormat $format): bool
    {
        return match ($format) {
            ExportFormat::CSV => $recordCount <= 50000,
            ExportFormat::EXCEL => $recordCount <= 25000,
            ExportFormat::PDF => $recordCount <= 5000,
        };
    }

    public function getSuggestedAlternativeFormat(int $recordCount, ExportFormat $currentFormat): ?ExportFormat
    {
        if ($this->isExportSizeOptimal($recordCount, $currentFormat)) {
            return null;
        }

        return match ($currentFormat) {
            ExportFormat::PDF => $recordCount > 10000 ? ExportFormat::CSV : ExportFormat::EXCEL,
            ExportFormat::EXCEL => $recordCount > 50000 ? ExportFormat::CSV : null,
            ExportFormat::CSV => null,
        };
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function validateExportConfiguration(array $configuration): void
    {
        $requiredFields = ['columns', 'include_headers', 'date_format'];

        foreach ($requiredFields as $field) {
            if (! isset($configuration[$field])) {
                throw ExportException::invalidExportConfiguration("Missing required field: {$field}");
            }
        }

        if (empty($configuration['columns']) || ! is_array($configuration['columns'])) {
            throw ExportException::invalidExportConfiguration('At least one column must be selected');
        }

        if (count($configuration['columns']) > 50) {
            throw ExportException::invalidExportConfiguration('Too many columns selected (maximum: 50)');
        }

        if (! is_bool($configuration['include_headers'])) {
            throw ExportException::invalidExportConfiguration('include_headers must be boolean');
        }

        $validDateFormats = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y', 'm/d/Y'];
        if (! in_array($configuration['date_format'], $validDateFormats)) {
            throw ExportException::invalidExportConfiguration('Invalid date format');
        }
    }

    private function validateConcurrencyLimits(int $userActiveExports, int $organizationActiveExports): void
    {
        if (! $this->canUserRequestExport($userActiveExports)) {
            throw ExportException::invalidExportConfiguration(
                "Maximum concurrent exports per user exceeded ({$userActiveExports}/" . self::MAX_CONCURRENT_EXPORTS_PER_USER . ')'
            );
        }

        if (! $this->canOrganizationRequestExport($organizationActiveExports)) {
            throw ExportException::invalidExportConfiguration(
                "Maximum concurrent exports per organization exceeded ({$organizationActiveExports}/" . self::MAX_CONCURRENT_EXPORTS_PER_ORGANIZATION . ')'
            );
        }
    }

    private function validateResourceType(string $resourceType): void
    {
        $validResourceTypes = [
            'campaigns',
            'donations',
            'users',
            'organizations',
            'analytics',
            'reports',
        ];

        if (! in_array($resourceType, $validResourceTypes)) {
            throw ExportException::invalidExportConfiguration("Invalid resource type: {$resourceType}");
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function validateFilters(array $filters): void
    {
        if (count($filters) > 20) {
            throw ExportException::invalidExportConfiguration('Too many filters applied (maximum: 20)');
        }

        foreach ($filters as $key => $value) {
            if (is_string($key) && strlen($key) > 50) {
                throw ExportException::invalidExportConfiguration("Filter key too long: {$key}");
            }

            if (is_array($value) && count($value) > 100) {
                throw ExportException::invalidExportConfiguration("Filter array too large for key: {$key}");
            }
        }
    }

    private function validateRecordCount(int $estimatedRecordCount): void
    {
        if ($estimatedRecordCount > self::MAX_EXPORT_RECORDS_LIMIT) {
            throw ExportException::invalidExportConfiguration(
                "Export record count ({$estimatedRecordCount}) exceeds maximum limit (" . self::MAX_EXPORT_RECORDS_LIMIT . ')'
            );
        }
    }

    private function validateFormatSpecificRules(ExportFormat $format, int $estimatedRecordCount): void
    {
        switch ($format) {
            case ExportFormat::PDF:
                if ($estimatedRecordCount > 10000) {
                    throw ExportException::invalidExportConfiguration(
                        'PDF exports are limited to 10,000 records. Consider using CSV or Excel format.'
                    );
                }
                break;

            case ExportFormat::EXCEL:
                if ($estimatedRecordCount > 1048576) { // Excel row limit
                    throw ExportException::invalidExportConfiguration(
                        'Excel exports cannot exceed 1,048,576 records. Use CSV format for larger datasets.'
                    );
                }
                break;
        }
    }
}

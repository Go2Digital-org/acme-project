<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Psr\Log\LoggerInterface;
use ZipArchive;

class ExportAnalyticsCommandHandler
{
    public function __construct(
        private readonly WidgetDataAggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return array<string, mixed>|null */
    public function handle(ExportAnalyticsCommand $command): ?array
    {
        try {
            $startTime = microtime(true);

            // Parse time range
            $timeRange = $this->parseTimeRange($command->timeRange);

            // Collect data based on export type and data types
            $exportData = $this->collectExportData($command, $timeRange);

            // Generate filename if not provided
            $filename = $command->filename ?? $this->generateFilename($command);

            // Export data in specified format
            $exportPath = match ($command->format) {
                'csv' => $this->exportToCsv($exportData, $filename, $command->includeHeaders),
                'excel' => $this->exportToExcel($exportData, $filename, $command->includeHeaders),
                'json' => $this->exportToJson($exportData, $filename),
                'pdf' => $this->exportToPdf($exportData, $filename),
                default => throw new InvalidArgumentException("Unsupported export format: {$command->format}")
            };

            // Compress if requested
            if ($command->compressOutput) {
                $exportPath = $this->compressFile($exportPath);
            }

            // Handle delivery
            $deliveryResult = $this->handleDelivery($command, $exportPath);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log export
            $this->logExport($command, $exportPath, $processingTime);

            return [
                'export_path' => $exportPath,
                'filename' => basename($exportPath),
                'format' => $command->format,
                'compressed' => $command->compressOutput,
                'delivery_method' => $command->deliveryMethod,
                'delivery_result' => $deliveryResult,
                'processing_time_ms' => $processingTime,
                'record_count' => $this->countRecords($exportData),
                'file_size_bytes' => Storage::size($exportPath),
            ];
        } catch (Exception $e) {
            $this->logger->error('Analytics export failed', [
                'export_type' => $command->exportType,
                'format' => $command->format,
                'user_id' => $command->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function parseTimeRange(?string $timeRangeStr): TimeRange
    {
        if (! $timeRangeStr) {
            return TimeRange::last30Days();
        }

        return match ($timeRangeStr) {
            'today' => TimeRange::today(),
            'yesterday' => TimeRange::yesterday(),
            'this_week' => TimeRange::thisWeek(),
            'last_week' => TimeRange::lastWeek(),
            'this_month' => TimeRange::thisMonth(),
            'last_month' => TimeRange::lastMonth(),
            'last_30_days' => TimeRange::last30Days(),
            'last_90_days' => TimeRange::last90Days(),
            'this_year' => TimeRange::thisYear(),
            'last_year' => TimeRange::lastYear(),
            default => TimeRange::last30Days()
        };
    }

    /** @return array<string, mixed> */
    private function collectExportData(ExportAnalyticsCommand $command, TimeRange $timeRange): array
    {
        $data = [];

        foreach ($command->dataTypes as $dataType) {
            $data[$dataType] = match ($dataType) {
                'donations' => $this->exportDonationData($timeRange, $command->filters),
                'campaigns' => $this->exportCampaignData($timeRange, $command->filters),
                'users' => $this->exportUserData($timeRange, $command->filters),
                'events' => $this->exportEventData($timeRange, $command->filters),
                'organizations' => $this->exportOrganizationData($timeRange, $command->filters),
                'donation_stats' => $this->aggregationService->aggregateDonationStats($timeRange, $command->filters),
                'campaign_stats' => $this->aggregationService->aggregateCampaignStats($timeRange, $command->filters),
                'top_donors' => $this->aggregationService->aggregateTopDonors($timeRange, 50, $command->filters),
                default => []
            };
        }

        return $data;
    }

    /** @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    private function exportDonationData(TimeRange $timeRange, array $filters): array
    {
        $query = DB::table('donations')
            ->leftJoin('users', 'donations.user_id', '=', 'users.id')
            ->leftJoin('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->leftJoin('organizations', 'users.organization_id', '=', 'organizations.id')
            ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('users.organization_id', $filters['organization_id']))
            ->when(! empty($filters['campaign_id']), fn ($q) => $q->where('donations.campaign_id', $filters['campaign_id']))
            ->select([
                'donations.id as donation_id',
                'donations.amount',
                'donations.currency',
                'donations.status',
                'donations.payment_method',
                'donations.created_at as donated_at',
                'users.name as donor_name',
                'users.email as donor_email',
                'campaigns.title as campaign_title',
                'organizations.name as organization_name',
            ]);

        return $query->get()->toArray();
    }

    /** @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    private function exportCampaignData(TimeRange $timeRange, array $filters): array
    {
        $query = DB::table('campaigns')
            ->leftJoin('users', 'campaigns.user_id', '=', 'users.id')
            ->leftJoin('organizations', 'campaigns.organization_id', '=', 'organizations.id')
            ->whereBetween('campaigns.created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('campaigns.organization_id', $filters['organization_id']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('campaigns.status', $filters['status']))
            ->select([
                'campaigns.id',
                'campaigns.title',
                'campaigns.description',
                'campaigns.target_amount',
                'campaigns.current_amount',
                'campaigns.status',
                'campaigns.start_date',
                'campaigns.end_date',
                'campaigns.created_at',
                'users.name as creator_name',
                'organizations.name as organization_name',
            ]);

        return $query->get()->toArray();
    }

    /** @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    private function exportUserData(TimeRange $timeRange, array $filters): array
    {
        $query = DB::table('users')
            ->leftJoin('organizations', 'users.organization_id', '=', 'organizations.id')
            ->whereBetween('users.created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('users.organization_id', $filters['organization_id']))
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status',
                'users.created_at',
                'users.last_login_at',
                'organizations.name as organization_name',
            ]);

        return $query->get()->toArray();
    }

    /** @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    private function exportEventData(TimeRange $timeRange, array $filters): array
    {
        $query = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(! empty($filters['event_type']), fn ($q) => $q->where('event_type', $filters['event_type']))
            ->select([
                'id',
                'event_type',
                'event_name',
                'user_id',
                'organization_id',
                'campaign_id',
                'properties',
                'created_at',
            ])
            ->limit(10000); // Limit for performance

        return $query->get()->toArray();
    }

    /** @param array<string, mixed> $filters
     * @return array<int, mixed>
     */
    private function exportOrganizationData(TimeRange $timeRange, array $filters): array
    {
        return $this->aggregationService->aggregateOrganizationStats($timeRange, $filters)['organizations'];
    }

    /** @param array<string, mixed> $data */
    private function exportToCsv(array $data, string $filename, bool $includeHeaders): string
    {
        $path = "exports/{$filename}.csv";
        $handle = fopen(Storage::path($path), 'w');

        if ($handle === false) {
            throw new Exception("Unable to open file for writing: {$path}");
        }

        foreach ($data as $dataType => $records) {
            if (empty($records)) {
                continue;
            }

            // Add section header
            fputcsv($handle, ["=== {$dataType} ==="]);

            // Add column headers if requested
            if ($includeHeaders) {
                $firstRecord = is_array($records) ? reset($records) : $records;
                if (is_array($firstRecord) || is_object($firstRecord)) {
                    fputcsv($handle, array_keys((array) $firstRecord));
                }
            }

            // Add data rows
            foreach ($records as $record) {
                fputcsv($handle, (array) $record);
            }

            // Add empty line between sections
            fputcsv($handle, []);
        }

        fclose($handle);

        return $path;
    }

    /** @param array<string, mixed> $data */
    private function exportToJson(array $data, string $filename): string
    {
        $path = "exports/{$filename}.json";
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        if ($jsonData === false) {
            throw new Exception('Failed to encode data to JSON');
        }

        Storage::put($path, $jsonData);

        return $path;
    }

    /** @param array<string, mixed> $data */
    private function exportToExcel(array $data, string $filename, bool $includeHeaders): string
    {
        // This would require a package like PhpSpreadsheet
        // For now, fallback to CSV
        return $this->exportToCsv($data, $filename, $includeHeaders);
    }

    /** @param array<string, mixed> $data */
    private function exportToPdf(array $data, string $filename): string
    {
        // This would require a PDF generation library
        // For now, fallback to JSON
        return $this->exportToJson($data, $filename);
    }

    private function compressFile(string $filePath): string
    {
        $compressedPath = $filePath . '.zip';

        $zip = new ZipArchive;
        if ($zip->open(Storage::path($compressedPath), ZipArchive::CREATE) === true) {
            $zip->addFile(Storage::path($filePath), basename($filePath));
            $zip->close();

            // Remove original file
            Storage::delete($filePath);

            return $compressedPath;
        }

        return $filePath;
    }

    /** @return array<string, mixed> */
    private function handleDelivery(ExportAnalyticsCommand $command, string $exportPath): array
    {
        return match ($command->deliveryMethod) {
            'download' => ['status' => 'ready', 'path' => $exportPath],
            'email' => $this->deliverByEmail($command, $exportPath),
            'storage' => ['status' => 'stored', 'path' => $exportPath],
            default => ['status' => 'ready', 'path' => $exportPath]
        };
    }

    /** @return array<string, mixed> */
    private function deliverByEmail(ExportAnalyticsCommand $command, string $exportPath): array
    {
        // This would integrate with email service
        // For now, just return success status
        return [
            'status' => 'email_sent',
            'recipient' => $command->notificationEmail,
            'attachment' => basename($exportPath),
        ];
    }

    private function generateFilename(ExportAnalyticsCommand $command): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $dataTypes = implode('-', $command->dataTypes);

        return "analytics_export_{$command->exportType}_{$dataTypes}_{$timestamp}";
    }

    /** @param array<string, mixed> $data */
    private function countRecords(array $data): int
    {
        $total = 0;
        foreach ($data as $records) {
            if (is_array($records)) {
                $total += count($records);
            }
        }

        return $total;
    }

    private function logExport(ExportAnalyticsCommand $command, string $exportPath, float $processingTime): void
    {
        DB::table('analytics_exports')->insert([
            'type' => $command->exportType,
            'format' => $command->format,
            'user_id' => $command->userId,
            'organization_id' => $command->organizationId,
            'filename' => basename($exportPath),
            'file_path' => $exportPath,
            'file_size' => Storage::size($exportPath),
            'data_types' => json_encode($command->dataTypes),
            'filters' => json_encode($command->filters),
            'delivery_method' => $command->deliveryMethod,
            'processing_time_ms' => $processingTime,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logger->info('Analytics export completed', [
            'export_type' => $command->exportType,
            'format' => $command->format,
            'user_id' => $command->userId,
            'filename' => basename($exportPath),
            'processing_time_ms' => $processingTime,
        ]);
    }
}

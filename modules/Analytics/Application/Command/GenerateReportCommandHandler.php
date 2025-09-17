<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Psr\Log\LoggerInterface;

class GenerateReportCommandHandler
{
    public function __construct(
        private readonly WidgetDataAggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return array<string, mixed>|null */
    public function handle(GenerateReportCommand $command): ?array
    {
        try {
            $startTime = microtime(true);

            // Create TimeRange from command parameters
            $timeRange = $this->parseTimeRange($command->timeRange);

            // Generate report data based on type
            $reportData = match ($command->reportType) {
                'donation_analytics' => $this->generateDonationReport($timeRange, $command->filters ?? []),
                'campaign_performance' => $this->generateCampaignReport($timeRange, $command->filters ?? []),
                'user_activity' => $this->generateUserActivityReport($timeRange, $command->filters ?? []),
                'organization_stats' => $this->generateOrganizationReport($timeRange, $command->filters ?? []),
                'donor_trends' => $this->generateDonorTrendsReport($timeRange, $command->filters ?? []),
                default => throw new InvalidArgumentException("Unsupported report type: {$command->reportType}")
            };

            // Add metadata
            $report = [
                'report_info' => [
                    'name' => $command->reportName,
                    'type' => $command->reportType,
                    'format' => $command->format,
                    'generated_at' => now()->toISOString(),
                    'time_range' => [
                        'start' => $timeRange->start->toISOString(),
                        'end' => $timeRange->end->toISOString(),
                        'label' => $timeRange->label,
                    ],
                    'filters_applied' => $command->filters,
                    'include_comparisons' => $command->includeComparisons,
                    'include_visualization_data' => $command->includeVisualizationData,
                    'generation_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ],
                'data' => $reportData,
            ];

            // Store report if requested
            if ($command->userId) {
                $this->storeReport($command, $report);
            }

            $this->logger->info('Analytics report generated', [
                'report_type' => $command->reportType,
                'report_name' => $command->reportName,
                'user_id' => $command->userId,
                'organization_id' => $command->organizationId,
                'generation_time_ms' => $report['report_info']['generation_time_ms'],
            ]);

            return $report;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate analytics report', [
                'report_type' => $command->reportType,
                'report_name' => $command->reportName,
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

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function generateDonationReport(TimeRange $timeRange, array $filters): array
    {
        $stats = $this->aggregationService->aggregateDonationStats($timeRange, $filters);
        $trends = $this->aggregationService->aggregateDonationTrends($timeRange, 'day', $filters);
        $topDonors = $this->aggregationService->aggregateTopDonors($timeRange, 10, $filters);

        return [
            'summary' => $stats,
            'trends' => $trends,
            'top_donors' => $topDonors,
        ];
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function generateCampaignReport(TimeRange $timeRange, array $filters): array
    {
        return [
            'performance' => $this->aggregationService->aggregateCampaignStats($timeRange, $filters),
        ];
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function generateUserActivityReport(TimeRange $timeRange, array $filters): array
    {
        // Get user activity metrics
        $activeUsers = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($query) => $query->where('organization_id', $filters['organization_id']))
            ->distinct('user_id')
            ->count();

        $totalEvents = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($query) => $query->where('organization_id', $filters['organization_id']))
            ->count();

        $eventsByType = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn ($query) => $query->where('organization_id', $filters['organization_id']))
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get();

        return [
            'active_users' => $activeUsers,
            'total_events' => $totalEvents,
            'events_by_type' => $eventsByType->toArray(),
        ];
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function generateOrganizationReport(TimeRange $timeRange, array $filters): array
    {
        return [
            'statistics' => $this->aggregationService->aggregateOrganizationStats($timeRange, $filters),
        ];
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function generateDonorTrendsReport(TimeRange $timeRange, array $filters): array
    {
        $trends = $this->aggregationService->aggregateDonationTrends($timeRange, 'week', $filters);
        $topDonors = $this->aggregationService->aggregateTopDonors($timeRange, 20, $filters);

        return [
            'weekly_trends' => $trends,
            'top_donors' => $topDonors,
        ];
    }

    /** @param array<string, mixed> $report */
    private function storeReport(GenerateReportCommand $command, array $report): void
    {
        DB::table('analytics_reports')->insert([
            'name' => $command->reportName,
            'type' => $command->reportType,
            'format' => $command->format,
            'user_id' => $command->userId,
            'organization_id' => $command->organizationId,
            'parameters' => json_encode($command->parameters),
            'filters' => json_encode($command->filters),
            'data' => json_encode($report),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

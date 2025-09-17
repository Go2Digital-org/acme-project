<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use InvalidArgumentException;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for getting notification statistics query.
 *
 * Returns comprehensive notification statistics with breakdowns by type,
 * channel, status, and time periods.
 */
final readonly class GetNotificationStatsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetNotificationStatsQuery) {
            throw new InvalidArgumentException('Expected GetNotificationStatsQuery');
        }

        $this->validateQuery($query);

        try {
            $startTime = microtime(true);

            // Build base filters
            $filters = $this->buildFilters($query);

            // Get basic counts
            $stats = [
                'total_notifications' => $this->repository->countByFilters($filters),
                'period' => [
                    'start_date' => $query->startDate?->toIso8601String(),
                    'end_date' => $query->endDate?->toIso8601String(),
                ],
                'filters_applied' => [
                    'user_id' => $query->userId,
                    'organization_id' => $query->organizationId,
                    'types' => $query->types,
                    'channels' => $query->channels,
                    'statuses' => $query->statuses,
                ],
            ];

            // Add breakdown statistics if requested
            if ($query->includeBreakdown) {
                $stats['breakdown'] = $this->getBreakdownStats($filters);
            }

            // Add trend statistics if requested
            if ($query->includeTrends) {
                $stats['trends'] = $this->getTrendStats($filters, $query->groupBy, $query->startDate, $query->endDate);
            }

            // Add computed metrics
            $stats['metrics'] = $this->getComputedMetrics($filters);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->debug('Notification statistics retrieved successfully', [
                'user_id' => $query->userId,
                'organization_id' => $query->organizationId,
                'total_count' => $stats['total_notifications'],
                'execution_time_ms' => $executionTime,
                'include_breakdown' => $query->includeBreakdown,
                'include_trends' => $query->includeTrends,
            ]);

            $stats['execution_time_ms'] = $executionTime;

            return $stats;
        } catch (Exception $e) {
            $this->logger->error('Failed to get notification statistics', [
                'user_id' => $query->userId,
                'organization_id' => $query->organizationId,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::queryFailed(
                "Failed to get notification statistics: {$e->getMessage()}",
            );
        }
    }

    /**
     * Build filters array from query parameters.
     */
    /**
     * @return array<string, mixed>
     */
    private function buildFilters(GetNotificationStatsQuery $query): array
    {
        $filters = [];

        if ($query->userId !== null) {
            $filters['notifiable_id'] = (string) $query->userId;
        }

        if ($query->organizationId !== null) {
            $filters['organization_id'] = (string) $query->organizationId;
        }

        if ($query->types !== []) {
            $filters['type'] = $query->types[0] ?? null; // Repository expects single type
        }

        if ($query->channels !== []) {
            $filters['channel'] = $query->channels[0] ?? null; // Repository expects single channel
        }

        if ($query->statuses !== []) {
            $filters['status'] = $query->statuses[0] ?? null; // Repository expects single status
        }

        if ($query->startDate instanceof CarbonInterface) {
            $filters['date_from'] = $query->startDate->format('Y-m-d H:i:s');
        }

        if ($query->endDate instanceof CarbonInterface) {
            $filters['date_to'] = $query->endDate->format('Y-m-d H:i:s');
        }

        return $filters;
    }

    /**
     * Get breakdown statistics by type, channel, status, and priority.
     *
     * @param  array<string, mixed>  $baseFilters
     * @return array<string, mixed>
     */
    private function getBreakdownStats(array $baseFilters): array
    {
        return [
            'by_type' => $this->repository->getCountsByField('type', $baseFilters),
            'by_channel' => $this->repository->getCountsByField('channel', $baseFilters),
            'by_status' => $this->repository->getCountsByField('status', $baseFilters),
            'by_priority' => $this->repository->getCountsByField('priority', $baseFilters),
        ];
    }

    /**
     * Get trend statistics over time periods.
     *
     * @param  array<string, mixed>  $baseFilters
     * @return array<string, mixed>
     */
    private function getTrendStats(array $baseFilters, string $groupBy, ?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        // Default to last 30 days if no dates provided
        $start = $startDate ?? Carbon::now()->subDays(30);
        $end = $endDate ?? Carbon::now();

        // Get time series data
        $timeSeries = $this->repository->getTimeSeriesData($baseFilters, $groupBy, $start, $end);

        // Calculate trend metrics
        $values = array_values($timeSeries);
        $totalPeriods = count($values);

        if ($totalPeriods < 2) {
            return [
                'time_series' => $timeSeries,
                'trend_direction' => 'insufficient_data',
                'average_per_period' => $totalPeriods > 0 ? $values[0] : 0,
                'peak_period' => null,
                'growth_rate' => 0,
            ];
        }

        $average = array_sum($values) / $totalPeriods;
        $peak = $values === [] ? 0 : max($values);
        $peakPeriod = array_search($peak, $values, true);

        // Simple growth rate calculation (latest vs earliest)
        $firstValue = reset($values);
        $lastValue = end($values);
        $growthRate = $firstValue > 0 ? (($lastValue - $firstValue) / $firstValue) * 100 : 0;

        return [
            'time_series' => $timeSeries,
            'trend_direction' => $growthRate > 5 ? 'increasing' : ($growthRate < -5 ? 'decreasing' : 'stable'),
            'average_per_period' => round($average, 2),
            'peak_period' => array_keys($timeSeries)[$peakPeriod] ?? null,
            'peak_value' => $peak,
            'growth_rate' => round($growthRate, 2),
            'total_periods' => $totalPeriods,
        ];
    }

    /**
     * Get computed metrics and ratios.
     *
     * @param  array<string, mixed>  $baseFilters
     * @return array<string, mixed>
     */
    private function getComputedMetrics(array $baseFilters): array
    {
        $totalCount = $this->repository->countByFilters($baseFilters);

        if ($totalCount === 0) {
            return [
                'read_rate' => 0,
                'delivery_rate' => 0,
                'failure_rate' => 0,
                'pending_rate' => 0,
            ];
        }

        $readCount = $this->repository->countByFilters(array_merge($baseFilters, ['status' => 'read']));
        $sentCount = $this->repository->countByFilters(array_merge($baseFilters, ['status' => ['sent', 'delivered']]));
        $failedCount = $this->repository->countByFilters(array_merge($baseFilters, ['status' => 'failed']));
        $pendingCount = $this->repository->countByFilters(array_merge($baseFilters, ['status' => ['pending', 'scheduled']]));

        return [
            'read_rate' => round(($readCount / $totalCount) * 100, 2),
            'delivery_rate' => round(($sentCount / $totalCount) * 100, 2),
            'failure_rate' => round(($failedCount / $totalCount) * 100, 2),
            'pending_rate' => round(($pendingCount / $totalCount) * 100, 2),
        ];
    }

    /**
     * Validate the get notification stats query.
     */
    private function validateQuery(GetNotificationStatsQuery $query): void
    {
        if ($query->userId !== null && $query->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if ($query->organizationId !== null && $query->organizationId <= 0) {
            throw NotificationException::invalidData(
                'organization_id',
                'Organization ID must be a positive integer',
            );
        }

        if ($query->startDate instanceof CarbonInterface && $query->endDate instanceof CarbonInterface && $query->startDate->isAfter($query->endDate)) {
            throw NotificationException::invalidData(
                'date_range',
                'Start date cannot be after end date',
            );
        }

        // Validate group by options
        $validGroupBy = ['hour', 'day', 'week', 'month'];

        if (! in_array($query->groupBy, $validGroupBy, true)) {
            throw NotificationException::invalidData(
                'group_by',
                'Group by must be one of: ' . implode(', ', $validGroupBy),
            );
        }

        // Prevent queries that are too broad (more than 1 year)
        if ($query->startDate instanceof CarbonInterface && $query->endDate instanceof CarbonInterface) {
            $daysDiff = $query->startDate->diffInDays($query->endDate);

            if ($daysDiff > 365) {
                throw NotificationException::invalidData(
                    'date_range',
                    'Date range cannot exceed 365 days',
                );
            }
        }
    }
}

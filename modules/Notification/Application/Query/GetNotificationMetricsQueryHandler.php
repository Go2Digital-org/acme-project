<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Exception;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for getting notification metrics query.
 *
 * Returns comprehensive notification metrics with statistics, trends, and analytics.
 */
final readonly class GetNotificationMetricsQueryHandler implements GetNotificationMetricsQueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(GetNotificationMetricsQuery $query): array
    {
        $this->validateQuery($query);

        try {
            $startTime = microtime(true);

            // Build base filters
            $filters = $this->buildFilters($query);

            // Get comprehensive metrics
            $metrics = [
                'totalNotifications' => $this->repository->countByFilters($filters),
                'deliveredNotifications' => $this->repository->countByFilters(
                    array_merge($filters, ['status' => ['sent', 'delivered']])
                ),
                'failedNotifications' => $this->repository->countByFilters(
                    array_merge($filters, ['status' => 'failed'])
                ),
                'readNotifications' => $this->repository->countByFilters(
                    array_merge($filters, ['status' => 'read'])
                ),
                'pendingNotifications' => $this->repository->countByFilters(
                    array_merge($filters, ['status' => ['pending', 'scheduled']])
                ),
            ];

            // Calculate rates
            $totalCount = $metrics['totalNotifications'];
            $deliveryRate = $totalCount > 0 ? ($metrics['deliveredNotifications'] / $totalCount) * 100 : 0;
            $openRate = $metrics['deliveredNotifications'] > 0 ? ($metrics['readNotifications'] / $metrics['deliveredNotifications']) * 100 : 0;
            $failureRate = $totalCount > 0 ? ($metrics['failedNotifications'] / $totalCount) * 100 : 0;

            // Get trend data
            $chartData = $this->getTrendData($filters, $query->groupBy, $query->startDate, $query->endDate);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $result = [
                'metrics' => array_merge($metrics, [
                    'deliveryRate' => round($deliveryRate, 2),
                    'openRate' => round($openRate, 2),
                    'failureRate' => round($failureRate, 2),
                ]),
                'dateRange' => [
                    'startDate' => $query->startDate->toDateString(),
                    'endDate' => $query->endDate->toDateString(),
                ],
                'groupBy' => $query->groupBy,
                'chartData' => $chartData,
                'executionTimeMs' => $executionTime,
            ];

            $this->logger->debug('Notification metrics retrieved successfully', [
                'user_id' => $query->userId,
                'organization_id' => $query->organizationId,
                'total_count' => $totalCount,
                'execution_time_ms' => $executionTime,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Failed to get notification metrics', [
                'user_id' => $query->userId,
                'organization_id' => $query->organizationId,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::queryFailed(
                "Failed to get notification metrics: {$e->getMessage()}",
            );
        }
    }

    /**
     * Build filters array from query parameters.
     *
     * @return array<string, mixed>
     */
    private function buildFilters(GetNotificationMetricsQuery $query): array
    {
        $filters = [];

        if ($query->userId !== null) {
            $filters['notifiable_id'] = (string) $query->userId;
        }

        if ($query->organizationId !== null) {
            $filters['organization_id'] = (string) $query->organizationId;
        }

        $filters['date_from'] = $query->startDate->format('Y-m-d H:i:s');
        $filters['date_to'] = $query->endDate->format('Y-m-d H:i:s');

        return $filters;
    }

    /**
     * Get trend data over time periods.
     *
     * @param  array<string, mixed>  $baseFilters
     * @return array<string, mixed>
     */
    private function getTrendData(array $baseFilters, string $groupBy, CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        try {
            // Get time series data - correct parameter order according to interface
            $timeSeries = $this->repository->getTimeSeriesData(
                $groupBy,
                $groupBy,
                $startDate,
                $endDate,
                $baseFilters
            );

            return $timeSeries;
        } catch (Exception $e) {
            $this->logger->warning('Failed to get trend data', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Validate the get notification metrics query.
     */
    private function validateQuery(GetNotificationMetricsQuery $query): void
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

        if ($query->startDate->isAfter($query->endDate)) {
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
        $daysDiff = $query->startDate->diffInDays($query->endDate);

        if ($daysDiff > 365) {
            throw NotificationException::invalidData(
                'date_range',
                'Date range cannot exceed 365 days',
            );
        }
    }
}

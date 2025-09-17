<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use InvalidArgumentException;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for getting notification digest query.
 *
 * Creates digestible summaries of notifications for email digests,
 * dashboard widgets, or periodic reports.
 */
final readonly class GetNotificationDigestQueryHandler implements QueryHandlerInterface
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
        if (! $query instanceof GetNotificationDigestQuery) {
            throw new InvalidArgumentException('Expected GetNotificationDigestQuery');
        }

        $this->validateQuery($query);

        try {
            // Determine date range based on digest type
            [$startDate, $endDate] = $this->getDigestDateRange($query);

            // Ensure we have proper Carbon instances
            if (! $startDate instanceof Carbon || ! $endDate instanceof Carbon) {
                throw new InvalidArgumentException('Invalid date range returned');
            }

            // Build filters
            $filters = $this->buildDigestFilters($query, $startDate, $endDate);

            // Get notifications for digest (using existing repository method)
            $notifications = $this->repository->findByFilters($filters, $query->maxNotifications)->toArray();

            // Build digest response
            $digest = [
                'user_id' => $query->userId,
                'digest_type' => $query->digestType,
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
                'generated_at' => Carbon::now()->toIso8601String(),
            ];

            // Add summary statistics if requested
            if ($query->includeSummaryStats) {
                $digest['summary'] = $this->buildDigestSummary($filters);
            }

            // Group notifications by type if requested
            if ($query->groupByType) {
                $digest['notifications_by_type'] = $this->groupNotificationsByType($notifications);
                $digest['total_notifications'] = count($notifications);
            } else {
                $digest['notifications'] = $notifications;
                $digest['total_notifications'] = count($notifications);
            }

            // Add digest metadata
            $digest['metadata'] = [
                'include_read' => $query->includeRead,
                'max_notifications' => $query->maxNotifications,
                'filters_applied' => [
                    'include_types' => $query->includeTypes,
                    'exclude_types' => $query->excludeTypes,
                ],
            ];

            $this->logger->info('Notification digest generated successfully', [
                'user_id' => $query->userId,
                'digest_type' => $query->digestType,
                'total_notifications' => $digest['total_notifications'],
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
            ]);

            return $digest;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate notification digest', [
                'user_id' => $query->userId,
                'digest_type' => $query->digestType,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::queryFailed(
                "Failed to generate notification digest: {$e->getMessage()}",
            );
        }
    }

    /**
     * Get date range for the digest based on digest type.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function getDigestDateRange(GetNotificationDigestQuery $query): array
    {
        if ($query->startDate instanceof CarbonInterface && $query->endDate instanceof CarbonInterface) {
            return [$query->startDate, $query->endDate];
        }

        $now = Carbon::now();

        return match ($query->digestType) {
            'hourly' => [$now->copy()->subHour(), $now],
            'daily' => [$now->copy()->subDay(), $now],
            'weekly' => [$now->copy()->subWeek(), $now],
            'monthly' => [$now->copy()->subMonth(), $now],
            default => [$now->copy()->subDay(), $now],
        };
    }

    /**
     * Build filters for digest query.
     *
     * @return array<string, mixed>
     */
    private function buildDigestFilters(GetNotificationDigestQuery $query, Carbon $startDate, Carbon $endDate): array
    {
        $filters = [
            'notifiable_id' => (string) $query->userId,
            'date_from' => $startDate->toDateTimeString(),
            'date_to' => $endDate->toDateTimeString(),
        ];

        if (! $query->includeRead) {
            $filters['unread'] = true;
        }

        if ($query->includeTypes !== []) {
            $filters['type'] = $query->includeTypes[0] ?? null; // Repository expects single type
        }

        // Note: excludeTypes not supported by current repository interface

        return $filters;
    }

    /**
     * Build summary statistics for the digest.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildDigestSummary(array $filters): array
    {
        $totalCount = $this->repository->countByFilters($filters);

        if ($totalCount === 0) {
            return [
                'total_notifications' => 0,
                'unread_count' => 0,
                'by_priority' => [],
                'by_type' => [],
                'by_channel' => [],
            ];
        }

        return [
            'total_notifications' => $totalCount,
            'unread_count' => $this->repository->countByFilters(
                array_merge($filters, ['status' => 'unread']),
            ),
            'by_priority' => $this->repository->getCountsByField('priority', $filters),
            'by_type' => $this->repository->getCountsByField('type', $filters),
            'by_channel' => $this->repository->getCountsByField('channel', $filters),
        ];
    }

    /**
     * Group notifications by type for better digest organization.
     *
     * @param  array<int, Notification>  $notifications
     * @return array<int, array<string, mixed>>
     */
    private function groupNotificationsByType(array $notifications): array
    {
        $grouped = [];

        foreach ($notifications as $notification) {
            if (! $notification instanceof Notification) {
                continue; // Skip invalid notifications
            }

            $type = $notification->type;

            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'count' => 0,
                    'notifications' => [],
                    'latest_notification' => null,
                ];
            }

            $grouped[$type]['count']++;
            $grouped[$type]['notifications'][] = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'status' => $notification->status,
                'created_at' => $notification->created_at->toISOString(),
            ];

            // Keep track of the latest notification for each type
            if (! $grouped[$type]['latest_notification'] instanceof \Illuminate\Support\Carbon ||
                ($notification->created_at && $notification->created_at > $grouped[$type]['latest_notification'])) {
                $grouped[$type]['latest_notification'] = $notification->created_at;
            }
        }

        // Sort groups by latest notification date
        uasort($grouped, function (array $a, array $b): int {
            $aLatest = $a['latest_notification'];
            $bLatest = $b['latest_notification'];

            if (! $aLatest instanceof \Illuminate\Support\Carbon && ! $bLatest instanceof \Illuminate\Support\Carbon) {
                return 0;
            }
            if (! $aLatest instanceof \Illuminate\Support\Carbon) {
                return 1;
            }
            if (! $bLatest instanceof \Illuminate\Support\Carbon) {
                return -1;
            }

            return $bLatest <=> $aLatest;
        });

        return array_values($grouped);
    }

    /**
     * Validate the get notification digest query.
     */
    private function validateQuery(GetNotificationDigestQuery $query): void
    {
        if ($query->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        $validDigestTypes = ['hourly', 'daily', 'weekly', 'monthly'];

        if (! in_array($query->digestType, $validDigestTypes, true)) {
            throw NotificationException::invalidData(
                'digest_type',
                'Digest type must be one of: ' . implode(', ', $validDigestTypes),
            );
        }

        if ($query->startDate instanceof CarbonInterface && $query->endDate instanceof CarbonInterface && $query->startDate->isAfter($query->endDate)) {
            throw NotificationException::invalidData(
                'date_range',
                'Start date cannot be after end date',
            );
        }

        if ($query->maxNotifications < 1 || $query->maxNotifications > 1000) {
            throw NotificationException::invalidData(
                'max_notifications',
                'Max notifications must be between 1 and 1000',
            );
        }

        // Validate that include and exclude types don't overlap
        $overlap = array_intersect($query->includeTypes, $query->excludeTypes);

        if ($overlap !== []) {
            throw NotificationException::invalidData(
                'type_filters',
                'Include types and exclude types cannot overlap: ' . implode(', ', $overlap),
            );
        }
    }
}

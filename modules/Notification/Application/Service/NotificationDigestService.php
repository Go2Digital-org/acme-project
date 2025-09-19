<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Service;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Application\Query\GetNotificationDigestQuery;
use Modules\Notification\Application\Query\GetNotificationDigestQueryHandler;
use Modules\Notification\Application\Query\GetNotificationPreferencesQuery;
use Modules\Notification\Application\Query\GetNotificationPreferencesQueryHandler;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing notification digests.
 *
 * Handles:
 * - Generating periodic notification digests
 * - Managing digest preferences and scheduling
 * - Creating digest notification content
 * - Batch digest delivery optimization
 */
final readonly class NotificationDigestService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationPreferencesRepositoryInterface $preferencesRepository,
        private UserRepositoryInterface $userRepository,
        private GetNotificationDigestQueryHandler $getDigestHandler,
        private GetNotificationPreferencesQueryHandler $getPreferencesHandler,
        private CreateNotificationCommandHandler $createNotificationHandler,
        private LoggerInterface $logger,
    ) {}

    /**
     * Generate and send digests for specified users and digest type.
     *
     * @param  array<int>  $userIds
     * @return array{digest_type: string, generated: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>, failed: array<int, array<string, mixed>>, execution_time_ms: float}
     */
    public function generateAndSendDigests(string $digestType = 'daily', array $userIds = []): array
    {
        $startTime = microtime(true);

        try {
            // Get users who should receive digests
            $targetUsers = $this->getDigestTargetUsers($digestType, $userIds);

            $generated = [];
            $skipped = [];
            $failed = [];

            foreach ($targetUsers as $user) {
                try {
                    $digest = $this->generateUserDigest($user['id'], $digestType);

                    if ($digest['total_notifications'] > 0) {
                        $notification = $this->createDigestNotification($user, $digest, $digestType);
                        $generated[] = [
                            'user_id' => $user['id'],
                            'notification_id' => $notification->id,
                            'notification_count' => $digest['total_notifications'],
                        ];
                    } else {
                        $skipped[] = [
                            'user_id' => $user['id'],
                            'reason' => 'no_notifications',
                        ];
                    }
                } catch (Exception $e) {
                    $failed[] = [
                        'user_id' => $user['id'],
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->error('Failed to generate digest for user', [
                        'user_id' => $user['id'],
                        'digest_type' => $digestType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Digest generation completed', [
                'digest_type' => $digestType,
                'target_users' => count($targetUsers),
                'generated' => count($generated),
                'skipped' => count($skipped),
                'failed' => count($failed),
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'digest_type' => $digestType,
                'generated' => $generated,
                'skipped' => $skipped,
                'failed' => $failed,
                'execution_time_ms' => $executionTime,
            ];
        } catch (Exception $e) {
            $this->logger->error('Digest generation failed', [
                'digest_type' => $digestType,
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::digestGenerationFailed(
                "Failed to generate {$digestType} digests: {$e->getMessage()}",
            );
        }
    }

    /**
     * Generate digest for a specific user.
     *
     * @return array<string, mixed>
     */
    public function generateUserDigest(int $userId, string $digestType): array
    {
        $query = new GetNotificationDigestQuery(
            userId: $userId,
            digestType: $digestType,
            includeRead: false,
            groupByType: true,
            includeSummaryStats: true,
            maxNotifications: 50,
        );

        return $this->getDigestHandler->handle($query);
    }

    /**
     * Get digest configuration for a user.
     *
     * @return array{user_id: int, digest_frequency: int, digest_enabled: bool, digest_type: string, timezone: string, quiet_hours: mixed, last_digest_sent: string|null}
     */
    public function getDigestConfiguration(int $userId): array
    {
        $preferencesQuery = new GetNotificationPreferencesQuery($userId);
        $preferences = $this->getPreferencesHandler->handle($preferencesQuery);

        $digestFrequency = $preferences['digest_frequency'] ?? 1; // Daily by default

        return [
            'user_id' => $userId,
            'digest_frequency' => $digestFrequency,
            'digest_enabled' => $digestFrequency > 0,
            'digest_type' => $this->mapFrequencyToType($digestFrequency),
            'timezone' => $preferences['timezone'] ?? 'UTC',
            'quiet_hours' => $preferences['quiet_hours'] ?? null,
            'last_digest_sent' => $this->getLastDigestSentTime($userId),
        ];
    }

    /**
     * Update digest configuration for a user.
     *
     * @return array{user_id: int, digest_frequency: int, digest_enabled: bool, digest_type: string, timezone: string, quiet_hours: mixed, last_digest_sent: string|null}
     */
    public function updateDigestConfiguration(int $userId): array
    {
        // This would update the user's notification preferences
        // For now, return the current configuration
        return $this->getDigestConfiguration($userId);
    }

    /**
     * Preview digest content for a user without sending.
     *
     * @return array{user_id: int, digest_type: string, preview_content: array<string, mixed>, notification_count: int, generated_at: string}
     */
    public function previewDigest(int $userId, string $digestType): array
    {
        $digest = $this->generateUserDigest($userId, $digestType);

        return [ // @phpstan-ignore-line
            'user_id' => $userId,
            'digest_type' => $digestType,
            'preview_content' => $this->formatDigestContent($digest),
            'notification_count' => (int) $digest['total_notifications'],
            'generated_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Get digest delivery statistics.
     *
     * @return array{period: array{start: string, end: string}, total_digests_sent: int, by_type: array<string, int>, by_status: array<string, int>}
     */
    public function getDigestStats(?DateTimeInterface $startDate = null, ?DateTimeInterface $endDate = null): array
    {
        $startDate ??= Carbon::now()->subWeek();
        $endDate ??= Carbon::now();

        // Get digest notifications sent in the period
        $digestNotifications = $this->notificationRepository->findByFilters([
            'type' => 'digest',
            'created_at_gte' => $startDate,
            'created_at_lte' => $endDate,
        ]);

        $stats = [
            'period' => [
                'start' => $startDate instanceof Carbon ? $startDate->toISOString() : $startDate->format('c'),
                'end' => $endDate instanceof Carbon ? $endDate->toISOString() : $endDate->format('c'),
            ],
            'total_digests_sent' => count($digestNotifications),
            'by_type' => [],
            'by_status' => [],
        ];

        foreach ($digestNotifications as $notification) {
            /** @var Notification $notification */
            $digestType = $notification->metadata['digest_type'] ?? 'unknown';
            $stats['by_type'][$digestType] = ($stats['by_type'][$digestType] ?? 0) + 1;
            $stats['by_status'][$notification->status] = ($stats['by_status'][$notification->status] ?? 0) + 1;
        }

        return $stats; // @phpstan-ignore-line
    }

    /**
     * Clean up old digest notifications.
     */
    public function cleanupOldDigests(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->notificationRepository->deleteByFilters([
            'type' => 'digest',
            'status' => ['read', 'delivered'],
            'created_at_lte' => $cutoffDate,
        ]);
    }

    /**
     * Get users who should receive digests of the specified type.
     *
     * @param  array<int, int>  $specificUserIds
     * @return array<int, array<string, mixed>>
     */
    private function getDigestTargetUsers(string $digestType, array $specificUserIds = []): array
    {
        $digestFrequency = $this->mapDigestTypeToFrequency($digestType);

        if ($specificUserIds !== []) {
            // Get specific users
            $users = $this->userRepository->findByIds($specificUserIds);

            return array_map(fn (User $user): array => $user->toArray(), $users);
        }

        // Get users with matching digest frequency preferences
        $preferences = $this->preferencesRepository->findByDigestFrequency((string) $digestFrequency);
        $userIds = array_map(fn ($pref) => $pref->user_id, $preferences->toArray());

        if ($userIds === []) {
            return [];
        }

        $users = $this->userRepository->findByIds($userIds);

        return array_map(fn (User $user): array => $user->toArray(), $users);
    }

    /**
     * Create a digest notification for a user.
     *
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>  $digest
     */
    private function createDigestNotification(array $user, array $digest, string $digestType): Notification
    {
        $content = $this->formatDigestContent($digest);

        $command = new CreateNotificationCommand(
            recipientId: $user['id'],
            title: $content['title'],
            message: $content['message'],
            type: 'digest',
            channel: 'email',
            priority: 'low',
            senderId: null,
            data: [
                'digest_data' => $digest,
                'html_content' => $content['html'] ?? null,
            ],
            metadata: [
                'digest_type' => $digestType,
                'notification_count' => $digest['total_notifications'],
                'period_start' => $digest['period']['start'],
                'period_end' => $digest['period']['end'],
                'auto_generated' => true,
            ],
        );

        return $this->createNotificationHandler->handle($command);
    }

    /**
     * Format digest content for presentation.
     *
     * @param  array<string, mixed>  $digest
     * @return array{title: string, message: string, html: string}
     */
    private function formatDigestContent(array $digest): array
    {
        $notificationCount = $digest['total_notifications'];
        $period = $this->formatPeriodText($digest['period']['start'], $digest['period']['end']);

        $title = "Your Notification Digest - {$notificationCount} new notification" .
                 ($notificationCount === 1 ? '' : 's') . " {$period}";

        $message = $this->buildDigestMessage($digest);

        return [
            'title' => $title,
            'message' => $message,
            'html' => $this->buildDigestHtml($digest),
        ];
    }

    /**
     * Build the digest message content.
     *
     * @param  array<string, mixed>  $digest
     */
    private function buildDigestMessage(array $digest): string
    {
        $notificationCount = $digest['total_notifications'];
        $period = $this->formatPeriodText($digest['period']['start'], $digest['period']['end']);

        $message = "You have {$notificationCount} new notification" .
                   ($notificationCount === 1 ? '' : 's') . " {$period}.\n\n";

        if (isset($digest['notifications_by_type'])) {
            foreach ($digest['notifications_by_type'] as $typeGroup) {
                $message .= "• {$typeGroup['count']} {$typeGroup['type']} notification";
                $message .= ($typeGroup['count'] === 1 ? '' : 's') . "\n";

                // Add latest notification title as example
                if (! empty($typeGroup['notifications'])) {
                    $latest = $typeGroup['notifications'][0];
                    $message .= "  Latest: {$latest['title']}\n";
                }
            }
        }

        return $message . "\nLog in to view all your notifications.";
    }

    /**
     * Build HTML content for digest email.
     *
     * @param  array<string, mixed>  $digest
     */
    private function buildDigestHtml(array $digest): string
    {
        // This would typically use a template engine
        // For now, return a simple HTML structure

        $html = "<h2>Your Notification Digest</h2>\n";
        $html .= "<p>You have {$digest['total_notifications']} new notifications.</p>\n";

        if (isset($digest['notifications_by_type'])) {
            $html .= "<div class='digest-groups'>\n";

            foreach ($digest['notifications_by_type'] as $typeGroup) {
                $html .= "<div class='notification-group'>\n";
                $html .= "<h3>{$typeGroup['type']} ({$typeGroup['count']})</h3>\n";

                foreach (array_slice($typeGroup['notifications'], 0, 3) as $notification) {
                    $html .= "<div class='notification-item'>\n";
                    $html .= "<strong>{$notification['title']}</strong><br>\n";
                    $html .= "<span class='message'>{$notification['message']}</span><br>\n";
                    $html .= "<small class='created-at'>{$notification['created_at']}</small>\n";
                    $html .= "</div>\n";
                }

                if ($typeGroup['count'] > 3) {
                    $remaining = $typeGroup['count'] - 3;
                    $html .= "<p><small>... and {$remaining} more</small></p>\n";
                }

                $html .= "</div>\n";
            }
            $html .= "</div>\n";
        }

        return $html;
    }

    /**
     * Format period text for human readability.
     */
    private function formatPeriodText(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->isToday()) {
            return 'today';
        }

        if ($start->isYesterday()) {
            return 'yesterday';
        }

        $days = $start->diffInDays($end);

        if ($days <= 1) {
            return 'in the last 24 hours';
        }

        if ($days <= 7) {
            return "in the last {$days} days";
        }

        return "from {$start->format('M j')} to {$end->format('M j')}";
    }

    /**
     * Map digest frequency number to digest type string.
     */
    private function mapFrequencyToType(int $frequency): string
    {
        return match ($frequency) {
            0 => 'disabled',
            1 => 'daily',
            7 => 'weekly',
            30 => 'monthly',
            default => 'daily',
        };
    }

    /**
     * Map digest type string to frequency number.
     */
    private function mapDigestTypeToFrequency(string $digestType): int
    {
        return match ($digestType) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            default => 1,
        };
    }

    /**
     * Get the last time a digest was sent to a user.
     */
    private function getLastDigestSentTime(int $userId): ?string
    {
        $lastDigest = $this->notificationRepository->findByFilters([
            'notifiable_id' => $userId,
            'type' => 'digest',
        ], 1);

        if (isset($lastDigest[0])) {
            /** @var Notification $lastDigestNotification */
            $lastDigestNotification = $lastDigest[0];

            return $lastDigestNotification->created_at->toISOString();
        }

        return null;
    }
}

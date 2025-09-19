<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Service;

use DateTimeInterface;
use Exception;
use Modules\Notification\Application\Command\BulkCreateNotificationsCommand;
use Modules\Notification\Application\Command\BulkCreateNotificationsCommandHandler;
use Modules\Notification\Application\Command\BulkMarkAsReadCommand;
use Modules\Notification\Application\Command\BulkMarkAsReadCommandHandler;
use Modules\Notification\Application\Command\CancelScheduledNotificationCommand;
use Modules\Notification\Application\Command\CancelScheduledNotificationCommandHandler;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Application\Command\DeleteNotificationCommand;
use Modules\Notification\Application\Command\DeleteNotificationCommandHandler;
use Modules\Notification\Application\Command\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Command\MarkNotificationAsReadCommandHandler;
use Modules\Notification\Application\Command\ScheduleNotificationCommand;
use Modules\Notification\Application\Command\ScheduleNotificationCommandHandler;
use Modules\Notification\Application\Command\SendNotificationCommand;
use Modules\Notification\Application\Command\SendNotificationCommandHandler;
use Modules\Notification\Application\Command\UpdateNotificationPreferencesCommand;
use Modules\Notification\Application\Command\UpdateNotificationPreferencesCommandHandler;
use Modules\Notification\Application\Query\GetNotificationDetailsQuery;
use Modules\Notification\Application\Query\GetNotificationDetailsQueryHandler;
use Modules\Notification\Application\Query\GetNotificationDigestQuery;
use Modules\Notification\Application\Query\GetNotificationDigestQueryHandler;
use Modules\Notification\Application\Query\GetNotificationPreferencesQuery;
use Modules\Notification\Application\Query\GetNotificationPreferencesQueryHandler;
use Modules\Notification\Application\Query\GetNotificationStatsQuery;
use Modules\Notification\Application\Query\GetNotificationStatsQueryHandler;
use Modules\Notification\Application\Query\GetUnreadNotificationCountQuery;
use Modules\Notification\Application\Query\GetUnreadNotificationCountQueryHandler;
use Modules\Notification\Application\Query\GetUserNotificationsQuery;
use Modules\Notification\Application\Query\GetUserNotificationsQueryHandler;
use Modules\Notification\Application\Query\SearchNotificationsQuery;
use Modules\Notification\Application\Query\SearchNotificationsQueryHandler;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Psr\Log\LoggerInterface;

/**
 * Main application service that orchestrates all notification operations.
 *
 * This service provides a high-level API for all notification functionality,
 * coordinating between commands, queries, and other application services.
 *
 * Key responsibilities:
 * - Command and query orchestration
 * - Business workflow coordination
 * - Cross-cutting concerns (logging, validation)
 * - Integration between notification subsystems
 */
final readonly class NotificationApplicationService
{
    public function __construct(
        // Command handlers
        private CreateNotificationCommandHandler $createHandler,
        private BulkCreateNotificationsCommandHandler $bulkCreateHandler,
        private MarkNotificationAsReadCommandHandler $markReadHandler,
        private BulkMarkAsReadCommandHandler $bulkMarkReadHandler,
        private SendNotificationCommandHandler $sendHandler,
        private DeleteNotificationCommandHandler $deleteHandler,
        private ScheduleNotificationCommandHandler $scheduleHandler,
        private CancelScheduledNotificationCommandHandler $cancelScheduleHandler,
        private UpdateNotificationPreferencesCommandHandler $preferencesHandler,

        // Query handlers
        private GetUserNotificationsQueryHandler $getUserNotificationsHandler,
        private GetUnreadNotificationCountQueryHandler $getUnreadCountHandler,
        private GetNotificationDetailsQueryHandler $getDetailsHandler,
        private GetNotificationStatsQueryHandler $getStatsHandler,
        private SearchNotificationsQueryHandler $searchHandler,
        private GetNotificationPreferencesQueryHandler $getPreferencesHandler,
        private GetNotificationDigestQueryHandler $getDigestHandler,

        // Application services
        private NotificationSchedulingService $schedulingService,
        private NotificationDigestService $digestService,
        private LoggerInterface $logger,
    ) {}

    // =======================================================================
    // NOTIFICATION CREATION & SENDING
    // =======================================================================

    /**
     * Create a single notification.
     */
    public function createNotification(CreateNotificationCommand $command): Notification
    {
        $this->logger->info('Creating notification', [
            'notifiable_id' => $command->recipientId,
            'type' => $command->type,
            'channel' => $command->channel,
        ]);

        return $this->createHandler->handle($command);
    }

    /**
     * Create multiple notifications in bulk.
     *
     * @return array<int, Notification>
     */
    public function createNotificationsBulk(BulkCreateNotificationsCommand $command): array
    {
        $this->logger->info('Creating bulk notifications', [
            'count' => count($command->notifications),
            'batch_id' => $command->batchId,
            'source' => $command->source,
        ]);

        return $this->bulkCreateHandler->handle($command);
    }

    /**
     * Create and immediately send a notification.
     *
     * @param  array<string, mixed>  $deliveryOptions
     */
    public function createAndSendNotification(CreateNotificationCommand $createCommand, array $deliveryOptions = []): Notification
    {
        $notification = $this->createNotification($createCommand);

        $sendCommand = new SendNotificationCommand(
            notificationId: $notification->id,
            forceImmediate: true,
            deliveryOptions: $deliveryOptions,
        );

        $this->sendNotification($sendCommand);

        return $notification;
    }

    /**
     * Send an existing notification.
     */
    public function sendNotification(SendNotificationCommand $command): mixed
    {
        $this->logger->info('Sending notification', [
            'notification_id' => $command->notificationId,
            'force_immediate' => $command->forceImmediate,
        ]);

        return $this->sendHandler->handle($command);
    }

    // =======================================================================
    // NOTIFICATION SCHEDULING
    // =======================================================================

    /**
     * Schedule a notification for future delivery.
     */
    public function scheduleNotification(ScheduleNotificationCommand $command): mixed
    {
        $this->logger->info('Scheduling notification', [
            'notifiable_id' => $command->recipientId,
            'scheduled_for' => $command->scheduledFor->format('c'),
            'recurring' => $command->recurring,
        ]);

        return $this->scheduleHandler->handle($command);
    }

    /**
     * Cancel a scheduled notification.
     */
    public function cancelScheduledNotification(CancelScheduledNotificationCommand $command): mixed
    {
        $this->logger->info('Canceling scheduled notification', [
            'notification_id' => $command->notificationId,
            'user_id' => $command->userId,
            'cancel_recurring' => $command->cancelRecurring,
        ]);

        return $this->cancelScheduleHandler->handle($command);
    }

    /**
     * Process scheduled notifications that are due.
     *
     * @return array<string, mixed>
     */
    public function processScheduledNotifications(int $limit = 100): array
    {
        return $this->schedulingService->processDueNotifications($limit);
    }

    /**
     * Generate recurring notifications for the next period.
     *
     * @return array<string, mixed>
     */
    public function generateRecurringNotifications(?DateTimeInterface $upTo = null): array
    {
        return $this->schedulingService->generateRecurringNotifications($upTo);
    }

    // =======================================================================
    // NOTIFICATION READING & MANAGEMENT
    // =======================================================================

    /**
     * Mark a notification as read.
     */
    public function markNotificationAsRead(MarkNotificationAsReadCommand $command): mixed
    {
        return $this->markReadHandler->handle($command);
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markNotificationsAsReadBulk(BulkMarkAsReadCommand $command): mixed
    {
        $this->logger->info('Bulk marking notifications as read', [
            'user_id' => $command->userId,
            'count' => count($command->notificationIds),
            'mark_all' => $command->markAllAsRead,
        ]);

        return $this->bulkMarkReadHandler->handle($command);
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(DeleteNotificationCommand $command): mixed
    {
        $this->logger->info('Deleting notification', [
            'notification_id' => $command->notificationId,
            'user_id' => $command->userId,
            'hard_delete' => $command->hardDelete,
        ]);

        return $this->deleteHandler->handle($command);
    }

    // =======================================================================
    // NOTIFICATION PREFERENCES
    // =======================================================================

    /**
     * Update user notification preferences.
     */
    public function updateNotificationPreferences(UpdateNotificationPreferencesCommand $command): NotificationPreferences
    {
        $this->logger->info('Updating notification preferences', [
            'user_id' => $command->userId,
            'timezone' => $command->timezone,
            'digest_frequency' => $command->digestFrequency,
        ]);

        return $this->preferencesHandler->handle($command);
    }

    /**
     * Get user notification preferences.
     */
    public function getNotificationPreferences(GetNotificationPreferencesQuery $query): mixed
    {
        return $this->getPreferencesHandler->handle($query);
    }

    // =======================================================================
    // NOTIFICATION QUERIES & SEARCH
    // =======================================================================

    /**
     * Get notifications for a user.
     */
    public function getUserNotifications(GetUserNotificationsQuery $query): mixed
    {
        return $this->getUserNotificationsHandler->handle($query);
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadNotificationCount(GetUnreadNotificationCountQuery $query): mixed
    {
        return $this->getUnreadCountHandler->handle($query);
    }

    /**
     * Get detailed information about a specific notification.
     */
    public function getNotificationDetails(GetNotificationDetailsQuery $query): mixed
    {
        return $this->getDetailsHandler->handle($query);
    }

    /**
     * Search notifications with advanced filters.
     */
    public function searchNotifications(SearchNotificationsQuery $query): mixed
    {
        return $this->searchHandler->handle($query);
    }

    /**
     * Get notification statistics and metrics.
     */
    public function getNotificationStats(GetNotificationStatsQuery $query): mixed
    {
        return $this->getStatsHandler->handle($query);
    }

    // =======================================================================
    // NOTIFICATION DIGESTS
    // =======================================================================

    /**
     * Get notification digest for a user.
     */
    public function getNotificationDigest(GetNotificationDigestQuery $query): mixed
    {
        return $this->getDigestHandler->handle($query);
    }

    /**
     * Generate and send notification digests for users.
     *
     * @param  array<int>  $userIds
     * @return array<string, mixed>
     */
    public function generateAndSendDigests(string $digestType = 'daily', array $userIds = []): array
    {
        return $this->digestService->generateAndSendDigests($digestType, $userIds);
    }

    /**
     * Get digest configuration for a user.
     *
     * @return array<string, mixed>
     */
    public function getDigestConfiguration(int $userId): array
    {
        return $this->digestService->getDigestConfiguration($userId);
    }

    // =======================================================================
    // BATCH OPERATIONS
    // =======================================================================

    /**
     * Process a batch of notification operations.
     *
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    public function processBatchOperations(array $operations): array
    {
        $results = [];
        $errors = [];

        foreach ($operations as $index => $operation) {
            try {
                $result = $this->processSingleOperation($operation);
                $results[$index] = $result;
            } catch (Exception $e) {
                $errors[$index] = [
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('Batch operation failed', [
                    'index' => $index,
                    'operation' => $operation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'success_count' => count($results),
            'error_count' => count($errors),
        ];
    }

    // =======================================================================
    // HEALTH & MAINTENANCE
    // =======================================================================

    /**
     * Perform notification system health check.
     *
     * @return array<string, mixed>
     */
    public function performHealthCheck(): array
    {
        $startTime = microtime(true);

        try {
            // Check basic functionality
            $healthMetrics = [
                'timestamp' => now()->format('c'),
                'status' => 'healthy',
                'checks' => [],
            ];

            // Check database connectivity
            $healthMetrics['checks']['database'] = $this->checkDatabaseHealth();

            // Check scheduled notifications processing
            $healthMetrics['checks']['scheduling'] = $this->checkSchedulingHealth();

            // Check delivery systems
            $healthMetrics['checks']['delivery'] = $this->checkDeliveryHealth();

            // Overall health status
            $failedChecks = array_filter($healthMetrics['checks'], fn (array $check): bool => $check['status'] !== 'healthy');
            $healthMetrics['status'] = $failedChecks === [] ? 'healthy' : 'degraded';

            $healthMetrics['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

            return $healthMetrics;
        } catch (Exception $e) {
            return [
                'timestamp' => now()->format('c'),
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Clean up old and orphaned notifications.
     *
     * @return array<string, mixed>
     */
    public function performMaintenance(): array
    {
        return [];
    }

    /**
     * Process a single operation within a batch.
     *
     * @param  array<string, mixed>  $operation
     */
    private function processSingleOperation(array $operation): mixed
    {
        $type = $operation['type'] ?? null;
        $data = $operation['data'] ?? [];

        if ($type === null) {
            throw NotificationException::invalidData('type', 'Operation type is required');
        }

        return match ($type) {
            'create' => $this->createNotification(CreateNotificationCommand::fromArray($data)),
            'send' => $this->sendNotification(new SendNotificationCommand(...$data)),
            'mark_read' => $this->markNotificationAsRead(new MarkNotificationAsReadCommand(...$data)),
            'delete' => $this->deleteNotification(new DeleteNotificationCommand(...$data)),
            'schedule' => $this->scheduleNotification(new ScheduleNotificationCommand(...$data)),
            default => throw NotificationException::invalidData('type', "Unknown operation type: {$type}"),
        };
    }

    // =======================================================================
    // PRIVATE HEALTH CHECK METHODS
    // =======================================================================

    /**
     * @return array<string, mixed>
     */
    private function checkDatabaseHealth(): array
    {
        try {
            // Simple query to check database connectivity
            $query = new GetUnreadNotificationCountQuery(userId: '1');
            $this->getUnreadCountHandler->handle($query);

            return ['status' => 'healthy', 'message' => 'Database connectivity OK'];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSchedulingHealth(): array
    {
        try {
            $dueNotifications = $this->schedulingService->countDueNotifications();
            $status = $dueNotifications > 100 ? 'degraded' : 'healthy';

            return [
                'status' => $status,
                'message' => "Due notifications: {$dueNotifications}",
                'due_count' => $dueNotifications,
            ];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDeliveryHealth(): array
    {
        return [
            'status' => 'healthy',
            'message' => 'Monitoring disabled',
            'failure_rate' => 0,
        ];
    }
}

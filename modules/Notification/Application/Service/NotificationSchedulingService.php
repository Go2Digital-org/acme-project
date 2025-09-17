<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Service;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Application\Command\SendNotificationCommand;
use Modules\Notification\Application\Command\SendNotificationCommandHandler;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing notification scheduling and recurring notifications.
 *
 * Handles:
 * - Processing due scheduled notifications
 * - Generating recurring notification instances
 * - Managing notification delivery timing
 * - Scheduling optimization and batching
 */
final readonly class NotificationSchedulingService
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private SendNotificationCommandHandler $sendHandler,
        private CreateNotificationCommandHandler $createHandler,
        private LoggerInterface $logger,
    ) {}

    /**
     * Process all notifications that are due for delivery.
     *
     * @return array{processed: array<int, string>, failed: array<int, array{notification_id: string, error: string}>, total_found: int, execution_time_ms: float}
     */
    public function processDueNotifications(int $limit = 100): array
    {
        $startTime = microtime(true);

        try {
            // Get due scheduled notifications
            $dueNotifications = $this->repository->findByFilters([
                'status' => 'scheduled',
                'scheduled_for_lte' => Carbon::now(),
            ], $limit);

            /** @var array<int, string> $processed */
            $processed = [];
            /** @var array<int, array{notification_id: string, error: string}> $failed */
            $failed = [];

            foreach ($dueNotifications as $notification) {
                try {
                    $this->processDueNotification($notification);
                    $processed[] = $notification->id;
                } catch (Exception $e) {
                    $failed[] = [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->error('Failed to process due notification', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                        'scheduled_for' => $notification->scheduled_for?->toISOString(),
                    ]);
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Due notifications processing completed', [
                'total_found' => count($dueNotifications),
                'processed' => count($processed),
                'failed' => count($failed),
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'processed' => $processed,
                'failed' => $failed,
                'total_found' => count($dueNotifications),
                'execution_time_ms' => $executionTime,
            ];
        } catch (Exception $e) {
            $this->logger->error('Due notifications processing failed', [
                'error' => $e->getMessage(),
                'limit' => $limit,
            ]);

            throw NotificationException::schedulingFailed(
                "Failed to process due notifications: {$e->getMessage()}",
            );
        }
    }

    /**
     * Generate recurring notification instances for the specified period.
     *
     * @return array{generated: array<int, string>, skipped: array<int, array{notification_id: string, error: string}>, total_recurring: int, generation_period_end: string, execution_time_ms: float}
     */
    public function generateRecurringNotifications(?DateTimeInterface $upTo = null): array
    {
        $upTo ??= Carbon::now()->addWeeks(2); // Generate 2 weeks ahead by default
        $startTime = microtime(true);

        try {
            // Find all active recurring notifications
            $recurringNotifications = $this->repository->findByFilters([
                'status' => ['scheduled', 'sent'],
                'metadata->is_recurring' => true,
                'metadata->recurring_active' => true,
            ]);

            /** @var array<int, string> $generated */
            $generated = [];
            /** @var array<int, array{notification_id: string, error: string}> $skipped */
            $skipped = [];

            foreach ($recurringNotifications as $notification) {
                try {
                    $instances = $this->generateRecurringInstances($notification, $upTo);
                    $generated = array_merge($generated, $instances);
                } catch (Exception $e) {
                    $skipped[] = [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ];

                    $this->logger->warning('Failed to generate recurring instances', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            /** @var string $periodEnd */
            $periodEnd = $upTo instanceof Carbon ? $upTo->toISOString() : $upTo->format('c');

            $this->logger->info('Recurring notifications generation completed', [
                'total_recurring' => count($recurringNotifications),
                'instances_generated' => count($generated),
                'skipped' => count($skipped),
                'generation_period_end' => $periodEnd,
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'generated' => $generated,
                'skipped' => $skipped,
                'total_recurring' => count($recurringNotifications),
                'generation_period_end' => $periodEnd,
                'execution_time_ms' => $executionTime,
            ];
        } catch (Exception $e) {
            $this->logger->error('Recurring notifications generation failed', [
                'error' => $e->getMessage(),
                'up_to' => $upTo instanceof Carbon ? $upTo->toISOString() : ($upTo->format('c') ?: ''),
            ]);

            throw NotificationException::schedulingFailed(
                "Failed to generate recurring notifications: {$e->getMessage()}",
            );
        }
    }

    /**
     * Count notifications that are currently due for processing.
     */
    public function countDueNotifications(): int
    {
        return $this->repository->countByFilters([
            'status' => 'scheduled',
            'scheduled_for_lte' => Carbon::now(),
        ]);
    }

    /**
     * Get scheduling statistics and health metrics.
     *
     * @return array{due_now: int, due_next_hour: int, due_next_24_hours: int, active_recurring: int, overdue: int}
     */
    public function getSchedulingStats(): array
    {
        $now = Carbon::now();

        return [
            'due_now' => $this->repository->countByFilters([
                'status' => 'scheduled',
                'scheduled_for_lte' => $now,
            ]),
            'due_next_hour' => $this->repository->countByFilters([
                'status' => 'scheduled',
                'scheduled_for_lte' => $now->copy()->addHour(),
                'scheduled_for_gte' => $now,
            ]),
            'due_next_24_hours' => $this->repository->countByFilters([
                'status' => 'scheduled',
                'scheduled_for_lte' => $now->copy()->addDay(),
                'scheduled_for_gte' => $now,
            ]),
            'active_recurring' => $this->repository->countByFilters([
                'metadata->is_recurring' => true,
                'metadata->recurring_active' => true,
            ]),
            'overdue' => $this->repository->countByFilters([
                'status' => 'scheduled',
                'scheduled_for_lte' => $now->copy()->subHour(),
            ]),
        ];
    }

    /**
     * Reschedule a notification to a new time.
     */
    public function rescheduleNotification(int $notificationId, DateTimeInterface $newTime, ?string $reason = null): bool
    {
        $notification = $this->repository->findById((string) $notificationId);

        if (! $notification instanceof Notification) {
            throw NotificationException::notificationNotFound((string) $notificationId);
        }

        if (! in_array($notification->status, ['scheduled', 'pending'], true)) {
            throw NotificationException::invalidStatus(
                "Cannot reschedule notification with status '{$notification->status}'",
            );
        }

        $carbon = $newTime instanceof Carbon ? $newTime : Carbon::instance($newTime);

        if ($carbon->isPast()) {
            throw NotificationException::invalidData(
                'new_time',
                'Cannot reschedule to a time in the past',
            );
        }

        try {
            /** @var array<string, mixed> $metadata */
            $metadata = array_merge($notification->metadata ?? [], [
                'rescheduled_at' => Carbon::now()->toISOString(),
                'previous_scheduled_for' => $notification->scheduled_for?->toISOString(),
                'reschedule_reason' => $reason,
            ]);

            $this->repository->updateById((string) $notificationId, [
                'scheduled_for' => $carbon,
                'metadata' => $metadata,
            ]);
            $updated = true;

            $this->logger->info('Notification rescheduled successfully', [
                'notification_id' => $notificationId,
                'previous_time' => $notification->scheduled_for?->toISOString(),
                'new_time' => $carbon->toISOString(),
                'reason' => $reason,
            ]);

            return $updated;
        } catch (Exception $e) {
            $this->logger->error('Failed to reschedule notification', [
                'notification_id' => $notificationId,
                'new_time' => $carbon->toISOString(),
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::schedulingFailed(
                "Failed to reschedule notification {$notificationId}: {$e->getMessage()}",
            );
        }
    }

    /**
     * Process a single due notification.
     */
    private function processDueNotification(Notification $notification): void
    {
        // Check if notification is part of a recurring series
        if ($this->isRecurringNotification($notification)) {
            $this->processRecurringNotification($notification);
        }

        // Send the notification
        /** @var array<string, mixed> $deliveryOptions */
        $deliveryOptions = $notification->metadata['delivery_options'] ?? [];
        $sendCommand = new SendNotificationCommand(
            notificationId: $notification->id,
            forceImmediate: true,
            deliveryOptions: $deliveryOptions,
        );

        $this->sendHandler->handle($sendCommand);
    }

    /**
     * Generate recurring instances for a recurring notification.
     *
     * @return array<int, string>
     */
    private function generateRecurringInstances(Notification $notification, DateTimeInterface $upTo): array
    {
        /** @var array<string, mixed>|null $recurringConfig */
        $recurringConfig = $notification->metadata['recurring_config'] ?? null;

        if ($recurringConfig === null) {
            throw NotificationException::invalidData(
                'recurring_config',
                'Recurring notification missing configuration',
            );
        }

        /** @var array<int, string> $generated */
        $generated = [];
        $nextTime = $this->calculateNextRecurrence($notification->scheduled_for ?? Carbon::now(), $recurringConfig);
        $instances = 0;
        /** @var int $maxInstances */
        $maxInstances = $recurringConfig['max_occurrences'] ?? 100;
        /** @var string|null $endDateString */
        $endDateString = $recurringConfig['end_date'] ?? null;
        $endDate = $endDateString ? Carbon::parse($endDateString) : null;

        while ($nextTime <= $upTo && $instances < $maxInstances) {
            // Check if we've reached the end date
            if ($endDate instanceof Carbon && $nextTime > $endDate) {
                break;
            }

            // Check if this instance already exists
            if (! $this->instanceExists($notification, $nextTime)) {
                $instance = $this->createRecurringInstance($notification, $nextTime);
                $generated[] = $instance->id;
                $instances++;
            }

            $nextTime = $this->calculateNextRecurrence($nextTime, $recurringConfig);
        }

        return $generated;
    }

    /**
     * Calculate the next recurrence time based on configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function calculateNextRecurrence(DateTimeInterface $currentTime, array $config): Carbon
    {
        $carbon = $currentTime instanceof Carbon ? $currentTime->copy() : Carbon::instance($currentTime);
        /** @var string $frequency */
        $frequency = $config['frequency'];
        /** @var int $interval */
        $interval = $config['interval'];

        return match ($frequency) {
            'minutes' => $carbon->addMinutes($interval),
            'hours' => $carbon->addHours($interval),
            'days' => $carbon->addDays($interval),
            'weeks' => $carbon->addWeeks($interval),
            'months' => $carbon->addMonths($interval),
            default => throw NotificationException::invalidData(
                'frequency',
                "Invalid recurring frequency: {$frequency}",
            ),
        };
    }

    /**
     * Check if a recurring instance already exists for the given time.
     */
    private function instanceExists(Notification $notification, DateTimeInterface $scheduledTime): bool
    {
        /** @var string|null $scheduleId */
        $scheduleId = $notification->metadata['schedule_id'] ?? null;

        if ($scheduleId === null) {
            return false;
        }

        $carbon = $scheduledTime instanceof Carbon ? $scheduledTime : Carbon::instance($scheduledTime);

        $existing = $this->repository->findByFilters([
            'metadata->schedule_id' => $scheduleId,
            'scheduled_for' => $carbon,
        ], 1);

        return count($existing) > 0;
    }

    /**
     * Create a new recurring instance.
     */
    private function createRecurringInstance(Notification $notification, DateTimeInterface $scheduledTime): Notification
    {
        $command = new CreateNotificationCommand(
            recipientId: $notification->notifiable_id,
            title: $notification->title ?? 'Notification',
            message: $notification->message ?? '',
            type: $notification->type,
            channel: $notification->channel ?? 'database',
            priority: $notification->priority ?? 'normal',
            senderId: $notification->sender_id,
            data: $notification->data ?? [],
            metadata: array_merge($notification->metadata ?? [], [
                'recurring_instance' => true,
                'parent_notification_id' => $notification->id,
                'generated_at' => Carbon::now()->toISOString(),
            ]),
            scheduledFor: $scheduledTime instanceof Carbon ? $scheduledTime : Carbon::instance($scheduledTime),
        );

        return $this->createHandler->handle($command);
    }

    /**
     * Check if a notification is part of a recurring series.
     */
    private function isRecurringNotification(Notification $notification): bool
    {
        /** @var bool $isRecurring */
        $isRecurring = $notification->metadata['is_recurring'] ?? false;

        return $isRecurring === true;
    }

    /**
     * Process special logic for recurring notifications.
     */
    private function processRecurringNotification(Notification $notification): void
    {
        // For now, just log that this is a recurring notification
        // In a full implementation, you might want to:
        // - Update occurrence counters
        // - Check if the series should be deactivated
        // - Generate the next instance if needed

        /** @var string|null $scheduleId */
        $scheduleId = $notification->metadata['schedule_id'] ?? null;
        /** @var bool $isInstance */
        $isInstance = $notification->metadata['recurring_instance'] ?? false;

        $this->logger->info('Processing recurring notification', [
            'notification_id' => $notification->id,
            'schedule_id' => $scheduleId,
            'is_instance' => $isInstance,
        ]);
    }
}

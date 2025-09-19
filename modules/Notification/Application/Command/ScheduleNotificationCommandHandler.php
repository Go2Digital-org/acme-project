<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationScheduledEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObject\NotificationChannel;
use Modules\Notification\Domain\ValueObject\NotificationPriority;
use Modules\Notification\Domain\ValueObject\NotificationType;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for scheduling notifications.
 *
 * Creates scheduled notifications with proper validation and supports
 * both one-time and recurring notifications.
 */
final readonly class ScheduleNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the schedule notification command.
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof ScheduleNotificationCommand) {
            throw new InvalidArgumentException('Expected ScheduleNotificationCommand');
        }

        $this->validateCommand($command);

        return DB::transaction(function () use ($command): array {
            $scheduleId = $command->scheduleId ?? Str::uuid()->toString();

            $metadata = array_merge($command->metadata, [
                'schedule_id' => $scheduleId,
                'scheduled_at' => Carbon::now()->toISOString(),
                'is_recurring' => $command->recurring,
            ]);

            if ($command->recurring && $command->recurringConfig !== null) {
                $metadata['recurring_config'] = $command->recurringConfig;
            }

            try {
                $notification = $this->repository->create([
                    'notifiable_id' => $command->recipientId,
                    'sender_id' => $command->senderId,
                    'title' => $command->title,
                    'message' => $command->message,
                    'type' => $command->type,
                    'channel' => $command->channel,
                    'priority' => $command->priority,
                    'status' => 'scheduled',
                    'data' => $command->data,
                    'metadata' => $metadata,
                    'scheduled_for' => $command->scheduledFor,
                ]);

                // Dispatch scheduled event
                Event::dispatch(new NotificationScheduledEvent(
                    notification: $notification,
                    scheduledFor: $command->scheduledFor,
                    scheduleId: $scheduleId,
                    isRecurring: $command->recurring,
                    recurringConfig: $command->recurringConfig,
                    source: 'command_handler',
                    context: [
                        'command_class' => $command::class,
                        'scheduled_via' => 'application_service',
                    ],
                ));

                $this->logger->info('Notification scheduled successfully', [
                    'notification_id' => $notification->id,
                    'schedule_id' => $scheduleId,
                    'notifiable_id' => $command->recipientId,
                    'scheduled_for' => $command->scheduledFor->toISOString(),
                    'type' => $command->type,
                    'channel' => $command->channel,
                    'recurring' => $command->recurring,
                ]);

                return [
                    'notification' => $notification,
                    'schedule_id' => $scheduleId,
                    'scheduled_for' => $command->scheduledFor->toISOString(),
                    'recurring' => $command->recurring,
                ];
            } catch (Exception $e) {
                $this->logger->error('Failed to schedule notification', [
                    'notifiable_id' => $command->recipientId,
                    'scheduled_for' => $command->scheduledFor->toISOString(),
                    'type' => $command->type,
                    'error' => $e->getMessage(),
                ]);

                throw NotificationException::schedulingFailed(
                    "Failed to schedule notification: {$e->getMessage()}",
                );
            }
        });
    }

    /**
     * Validate the schedule notification command.
     */
    private function validateCommand(ScheduleNotificationCommand $command): void
    {
        // Validate recipient ID
        if ($command->recipientId <= 0) {
            throw NotificationException::invalidRecipient('Recipient ID must be a positive integer');
        }

        // Validate notification type
        if (! NotificationType::isValid($command->type)) {
            throw NotificationException::invalidType($command->type);
        }

        // Validate notification channel
        if (! NotificationChannel::isValid($command->channel)) {
            throw NotificationException::unsupportedChannel($command->channel);
        }

        // Validate notification priority
        if (! NotificationPriority::isValid($command->priority)) {
            throw NotificationException::invalidPriority($command->priority);
        }

        // Validate title and message
        if (in_array(trim($command->title), ['', '0'], true)) {
            throw NotificationException::invalidData('title', 'Title cannot be empty');
        }

        if (in_array(trim($command->message), ['', '0'], true)) {
            throw NotificationException::invalidData('message', 'Message cannot be empty');
        }

        // Validate scheduled time
        if ($command->scheduledFor->isPast()) {
            throw NotificationException::invalidData(
                'scheduled_for',
                'Scheduled time cannot be in the past',
            );
        }

        // Don't allow scheduling more than 1 year in advance
        $maxScheduleTime = Carbon::now()->addYear();

        if ($command->scheduledFor->isAfter($maxScheduleTime)) {
            throw NotificationException::invalidData(
                'scheduled_for',
                'Cannot schedule notifications more than 1 year in advance',
            );
        }

        // data and metadata are already typed as arrays in the command

        // Validate recurring configuration if provided
        if ($command->recurring) {
            $this->validateRecurringConfig($command->recurringConfig);

            return;
        }

        if ($command->recurringConfig !== null) {
            throw NotificationException::invalidData(
                'recurring_config',
                'Recurring config provided but recurring is false',
            );
        }
    }

    /**
     * Validate recurring notification configuration.
     *
     * @param  array<string, mixed>|null  $recurringConfig
     */
    private function validateRecurringConfig(?array $recurringConfig): void
    {
        if ($recurringConfig === null) {
            throw NotificationException::invalidData(
                'recurring_config',
                'Recurring config is required when recurring is true',
            );
        }

        $requiredKeys = ['frequency', 'interval'];

        foreach ($requiredKeys as $key) {
            if (! isset($recurringConfig[$key])) {
                throw NotificationException::invalidData(
                    "recurring_config.{$key}",
                    "Recurring config must include {$key}",
                );
            }
        }

        // Validate frequency
        $validFrequencies = ['minutes', 'hours', 'days', 'weeks', 'months'];

        if (! in_array($recurringConfig['frequency'], $validFrequencies, true)) {
            throw NotificationException::invalidData(
                'recurring_config.frequency',
                'Frequency must be one of: ' . implode(', ', $validFrequencies),
            );
        }

        // Validate interval
        if (! is_int($recurringConfig['interval']) || $recurringConfig['interval'] < 1) {
            throw NotificationException::invalidData(
                'recurring_config.interval',
                'Interval must be a positive integer',
            );
        }

        // Validate interval limits based on frequency
        $maxIntervals = [
            'minutes' => 1440, // Max 1440 minutes (24 hours)
            'hours' => 168,    // Max 168 hours (1 week)
            'days' => 365,     // Max 365 days (1 year)
            'weeks' => 52,     // Max 52 weeks (1 year)
            'months' => 12,    // Max 12 months (1 year)
        ];

        $frequency = $recurringConfig['frequency'];

        if ($recurringConfig['interval'] > $maxIntervals[$frequency]) {
            throw NotificationException::invalidData(
                'recurring_config.interval',
                "Interval cannot exceed {$maxIntervals[$frequency]} for {$frequency}",
            );
        }

        // Validate end conditions if provided
        if (isset($recurringConfig['end_date'])) {
            if (! $recurringConfig['end_date'] instanceof DateTimeInterface) {
                throw NotificationException::invalidData(
                    'recurring_config.end_date',
                    'End date must be a DateTime instance',
                );
            }

            if ($recurringConfig['end_date']->getTimestamp() <= time()) {
                throw NotificationException::invalidData(
                    'recurring_config.end_date',
                    'Recurring end date cannot be in the past',
                );
            }
        }

        if (isset($recurringConfig['max_occurrences'])) {
            if (! is_int($recurringConfig['max_occurrences']) || $recurringConfig['max_occurrences'] < 1) {
                throw NotificationException::invalidData(
                    'recurring_config.max_occurrences',
                    'Max occurrences must be a positive integer',
                );
            }

            if ($recurringConfig['max_occurrences'] > 1000) {
                throw NotificationException::invalidData(
                    'recurring_config.max_occurrences',
                    'Max occurrences cannot exceed 1000',
                );
            }
        }
    }
}

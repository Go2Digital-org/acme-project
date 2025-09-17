<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObject\NotificationChannel;
use Modules\Notification\Domain\ValueObject\NotificationPriority;
use Modules\Notification\Domain\ValueObject\NotificationType;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Handler for creating notifications.
 *
 * Validates input, creates the notification entity, and dispatches domain events.
 */
class CreateNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Handle the create notification command.
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof CreateNotificationCommand) {
            throw new InvalidArgumentException('Expected CreateNotificationCommand');
        }

        // Validate the command data
        $this->validateCommand($command);

        return DB::transaction(function () use ($command): Notification {
            // Create the notification
            $notification = $this->repository->create([
                'notifiable_id' => $command->recipientId,
                'sender_id' => $command->senderId,
                'title' => $command->title,
                'message' => $command->message,
                'type' => $command->type,
                'channel' => $command->channel,
                'priority' => $command->priority,
                'status' => 'pending',
                'data' => $command->data,
                'metadata' => $command->metadata,
                'scheduled_for' => $command->scheduledFor,
            ]);

            // Dispatch domain event
            Event::dispatch(new NotificationCreatedEvent(
                notification: $notification,
                source: 'command_handler',
                context: [
                    'command_class' => $command::class,
                    'created_via' => 'application_service',
                ],
            ));

            return $notification;
        });
    }

    /**
     * Validate the command data.
     */
    private function validateCommand(CreateNotificationCommand $command): void
    {
        // Validate recipient ID is provided
        if ($command->recipientId === '' || $command->recipientId === '0') {
            throw NotificationException::invalidRecipient('Recipient ID cannot be empty');
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

        // Validate scheduled time if provided
        if ($command->scheduledFor instanceof DateTime && $command->scheduledFor < new DateTime) {
            throw NotificationException::invalidData(
                'scheduled_for',
                'Scheduled time cannot be in the past',
            );
        }

        // data and metadata are already typed as arrays in the command
    }
}

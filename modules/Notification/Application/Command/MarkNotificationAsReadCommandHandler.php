<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationReadEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Handler for marking notifications as read.
 */
final readonly class MarkNotificationAsReadCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof MarkNotificationAsReadCommand) {
            throw new InvalidArgumentException('Expected MarkNotificationAsReadCommand');
        }

        $notification = $this->repository->findById($command->notificationId);

        if (! $notification instanceof Notification) {
            throw NotificationException::notFound($command->notificationId);
        }

        // Verify the user has permission to mark this notification as read
        if ($notification->notifiable_id !== $command->userId) {
            throw NotificationException::invalidRecipient(
                'User does not have permission to mark this notification as read',
            );
        }

        // Mark as read using the domain model
        $notification->markAsRead();

        // Dispatch domain event
        Event::dispatch(new NotificationReadEvent(
            notification: $notification,
            readBy: $command->userId,
            readContext: $command->context,
        ));

        return true;
    }
}

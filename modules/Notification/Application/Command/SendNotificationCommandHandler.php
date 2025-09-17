<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Application\Service\NotificationDeliveryService;
use Modules\Notification\Domain\Event\NotificationFailedEvent;
use Modules\Notification\Domain\Event\NotificationSentEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Throwable;

/**
 * Handler for sending notifications.
 */
final readonly class SendNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private NotificationDeliveryService $deliveryService,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof SendNotificationCommand) {
            throw new InvalidArgumentException('Expected SendNotificationCommand');
        }

        $notification = $this->repository->findById($command->notificationId);

        if (! $notification instanceof Notification) {
            throw NotificationException::notFound($command->notificationId);
        }

        // Check if notification can be sent
        if (! $command->forceImmediate && ! $notification->canBeSentNow()) {
            throw NotificationException::cannotSend(
                $notification,
                'Notification is not ready to be sent',
            );
        }

        try {
            // Use delivery service to send notification
            $deliveryResult = $this->deliveryService->deliver(
                $notification,
                $command->deliveryOptions,
            );

            if ($deliveryResult->isSuccessful()) {
                // Mark as sent
                $notification->markAsSent();

                // Dispatch success event
                Event::dispatch(new NotificationSentEvent(
                    notification: $notification,
                    deliveryChannel: $deliveryResult->getChannel(),
                    deliveryMetadata: $deliveryResult->getMetadata(),
                ));

                return true;
            }

            // Handle delivery failure
            $notification->markAsFailed($deliveryResult->getErrorMessage());

            Event::dispatch(new NotificationFailedEvent(
                notification: $notification,
                failureReason: $deliveryResult->getErrorMessage(),
                failureContext: $deliveryResult->getErrorContext(),
            ));

            return false;
        } catch (Throwable $exception) {
            // Handle unexpected errors
            $notification->markAsFailed($exception->getMessage());

            Event::dispatch(new NotificationFailedEvent(
                notification: $notification,
                failureReason: $exception->getMessage(),
                exception: $exception,
            ));

            throw NotificationException::deliveryFailed(
                $notification,
                $exception->getMessage(),
                $exception,
            );
        }
    }
}

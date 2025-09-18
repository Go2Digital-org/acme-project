<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Notifications\DatabaseNotification;
use InvalidArgumentException;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Service\NotificationApplicationService;
use Modules\Shared\Infrastructure\ApiPlatform\Resource\NotificationResource;

/**
 * @implements ProcessorInterface<object, NotificationResource>
 */
final readonly class SendNotificationProcessor implements ProcessorInterface
{
    public function __construct(
        private NotificationApplicationService $notificationService,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): NotificationResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        // Extract notification data from the request
        $recipientId = property_exists($data, 'recipient_id') ? (string) $data->recipient_id : null;
        $title = property_exists($data, 'title') ? $data->title : null;
        $message = property_exists($data, 'message') ? $data->message : null;
        $type = property_exists($data, 'type') ? $data->type : null;
        $channel = property_exists($data, 'channel') ? $data->channel : 'database';
        $priority = property_exists($data, 'priority') ? $data->priority : 'normal';
        $senderId = property_exists($data, 'sender_id') ? (string) $data->sender_id : null;
        $notificationData = property_exists($data, 'data') ? (array) $data->data : [];
        $metadata = property_exists($data, 'metadata') ? (array) $data->metadata : [];
        $deliveryOptions = property_exists($data, 'delivery_options') ? (array) $data->delivery_options : [];

        if ($recipientId === null || $title === null || $message === null || $type === null) {
            throw new InvalidArgumentException('recipient_id, title, message, and type are required');
        }

        // Create the notification command
        $command = new CreateNotificationCommand(
            recipientId: $recipientId,
            title: $title,
            message: $message,
            type: $type,
            channel: $channel,
            priority: $priority,
            senderId: $senderId,
            data: $notificationData,
            metadata: $metadata,
        );

        // Create and send the notification
        $notification = $this->notificationService->createAndSendNotification(
            $command,
            $deliveryOptions
        );

        // Convert to resource format
        $databaseNotification = new DatabaseNotification;
        $databaseNotification->setAttribute('id', $notification->id);
        $databaseNotification->setAttribute('type', $notification->type);
        $databaseNotification->setAttribute('notifiable_type', 'App\\Models\\User');
        $databaseNotification->setAttribute('notifiable_id', $notification->notifiable_id);
        $databaseNotification->setAttribute('data', [
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'data' => $notification->data,
        ]);
        $databaseNotification->setAttribute('read_at', $notification->read_at);
        $databaseNotification->setAttribute('created_at', $notification->created_at);
        $databaseNotification->setAttribute('updated_at', $notification->updated_at);

        return NotificationResource::fromModel($databaseNotification);
    }
}

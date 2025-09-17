<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Notification\Application\ReadModel\NotificationReadModel;
use Modules\Notification\Domain\Exception\NotificationNotFoundException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;

final readonly class GetNotificationByIdQueryHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * @throws NotificationNotFoundException
     */
    public function handle(GetNotificationByIdQuery $query): NotificationReadModel
    {
        $notification = $this->notificationRepository->findById((string) $query->notificationId);

        if (! $notification instanceof Notification) {
            throw NotificationNotFoundException::forId((string) $query->notificationId);
        }

        // If user ID is specified, ensure the notification belongs to that user
        if ($query->userId && $notification->user_id !== $query->userId) {
            throw NotificationNotFoundException::forUserAndId(
                (string) $query->userId,
                (string) $query->notificationId
            );
        }

        // Transform to array structure expected by ReadModel
        $data = [
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => is_string($notification->data) ? json_decode($notification->data, true) : $notification->data,
            'channel' => $notification->channel ?? 'database',
            'channels' => [$notification->channel ?? 'database'],
            'priority' => $notification->priority ?? 'normal',
            'user_id' => $notification->user_id,
            'user_name' => null,
            'user_email' => null,
            'read_at' => $notification->read_at?->format('c'),
            'dismissed_at' => null,
            'delivered_at' => null,
            'delivery_status' => $notification->delivery_status ?? 'pending',
            'delivery_error' => null,
            'scheduled_for' => $notification->scheduled_for?->format('c'),
            'created_at' => $notification->created_at->format('c'),
            'updated_at' => $notification->updated_at->format('c'),
            'sent_at' => $notification->sent_at?->format('c'),
        ];

        return new NotificationReadModel((string) $notification->id, $data);
    }
}

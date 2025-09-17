<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Application\Event\AbstractDomainEvent;

/**
 * Domain event fired when a notification is successfully sent.
 */
final class NotificationSentEvent extends AbstractDomainEvent
{
    public function __construct(
        public Notification $notification,
        public string $deliveryChannel,
        /** @var array<string, mixed> */
        public array $deliveryMetadata = [],
    ) {}

    public function getEventName(): string
    {
        return 'notification.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'delivery_channel' => $this->deliveryChannel,
            'delivery_metadata' => $this->deliveryMetadata,
            'sent_at' => $this->notification->sent_at?->format('c'),
        ];
    }

    public function getAggregateId(): string
    {
        return (string) $this->notification->id;
    }

    public function getEventVersion(): int
    {
        return 1;
    }

    public function getContext(): string
    {
        return 'Notification';
    }

    public function isAsync(): bool
    {
        return true;
    }
}

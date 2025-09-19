<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Application\Event\AbstractDomainEvent;

/**
 * Domain event fired when a new notification is created.
 *
 * This event is triggered during the notification creation process and can be
 * used to trigger additional business logic such as analytics or audit logging.
 */
class NotificationCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public Notification $notification,
        public string $source = 'system',
        /** @var array<string, mixed> */
        public array $context = [],
    ) {
        parent::__construct();
    }

    /**
     * Get the event name for broadcasting or logging.
     */
    public function getEventName(): string
    {
        return 'notification.created';
    }

    /**
     * Get event data for serialization.
     */
    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'priority' => $this->notification->priority,
            'source' => $this->source,
            'context' => $this->context,
            'created_at' => $this->notification->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if this event should be broadcast in real-time.
     */
    public function shouldBroadcast(): bool
    {
        return $this->notification->supportsRealTimeBroadcast();
    }

    /**
     * Get broadcast channel name.
     */
    public function getBroadcastChannel(): string
    {
        return "user.{$this->notification->notifiable_id}.notifications";
    }

    /**
     * Convert event to array for serialization (test compatibility).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'priority' => $this->notification->priority,
            'source' => $this->source,
            'context' => $this->context,
            'created_at' => $this->notification->created_at->format('c'),
        ];
    }

    public function getAggregateId(): string|int|null
    {
        return $this->notification->id;
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

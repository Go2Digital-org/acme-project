<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use DateTimeImmutable;
use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Application\Event\AbstractDomainEvent;

/**
 * Domain event fired when a notification is marked as read by the recipient.
 */
final class NotificationReadEvent extends AbstractDomainEvent
{
    public readonly DateTimeImmutable $readAt;

    /**
     * @param  array<string, mixed>  $readContext
     */
    public function __construct(
        public Notification $notification,
        public string $readBy,
        /** @var array<string, mixed> */
        public array $readContext = [],
        ?DateTimeImmutable $readAt = null,
    ) {
        parent::__construct();
        $this->readAt = $readAt ?? new DateTimeImmutable;
    }

    public function getEventName(): string
    {
        return 'notification.read';
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
            'read_by' => $this->readBy,
            'read_context' => $this->readContext,
            'read_at' => $this->readAt->format('c'),
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

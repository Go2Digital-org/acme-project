<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event dispatched when a scheduled notification is cancelled.
 */
final readonly class NotificationCancelledEvent implements DomainEventInterface
{
    public function __construct(
        public Notification $notification,
        public int $cancelledBy,
        public CarbonInterface $cancelledAt,
        public ?string $reason,
        public bool $recurringCancellation,
        public string $source,
        /** @var array<string, mixed> */
        /** @var array<string, mixed> */
        public array $context = [],
    ) {}

    public function getEventName(): string
    {
        return 'notification.cancelled';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->cancelledAt->toDateTimeString());
    }

    /** @return array<string, mixed> */
    public function getEventData(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'sender_id' => $this->notification->sender_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'cancelled_by' => $this->cancelledBy,
            'cancelled_at' => $this->cancelledAt->toDateTimeString(),
            'reason' => $this->reason,
            'recurring_cancellation' => $this->recurringCancellation,
            'source' => $this->source,
            'context' => $this->context,
            'occurred_at' => $this->getOccurredAt()->format('c'),
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

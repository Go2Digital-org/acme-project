<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event dispatched when a notification is scheduled for future delivery.
 */
final readonly class NotificationScheduledEvent implements DomainEventInterface
{
    public function __construct(
        public Notification $notification,
        public CarbonInterface $scheduledFor,
        public string $scheduleId,
        public bool $isRecurring,
        /** @var array<string, mixed>|null */
        public ?array $recurringConfig,
        public string $source,
        /** @var array<string, mixed> */
        public array $context = [],
    ) {}

    public function getEventName(): string
    {
        return 'notification.scheduled';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'sender_id' => $this->notification->sender_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'scheduled_for' => $this->scheduledFor->format('c'),
            'schedule_id' => $this->scheduleId,
            'is_recurring' => $this->isRecurring,
            'recurring_config' => $this->recurringConfig,
            'source' => $this->source,
            'context' => $this->context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->getEventData();
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

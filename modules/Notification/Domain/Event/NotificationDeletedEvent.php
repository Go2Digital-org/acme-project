<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event dispatched when a notification is deleted.
 */
final readonly class NotificationDeletedEvent implements DomainEventInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $notificationId,
        public int $userId,
        public CarbonInterface $deletedAt,
        public bool $hardDelete,
        public ?string $reason,
        public Notification $originalNotification,
        public string $source,
        /** @var array<string, mixed> */
        public array $context = [],
    ) {}

    public function getEventName(): string
    {
        return 'notification.deleted';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->deletedAt->toDateTimeString());
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'notifiable_id' => $this->originalNotification->notifiable_id,
            'sender_id' => $this->originalNotification->sender_id,
            'type' => $this->originalNotification->type,
            'channel' => $this->originalNotification->channel,
            'user_id' => $this->userId,
            'deleted_at' => $this->deletedAt->toDateTimeString(),
            'hard_delete' => $this->hardDelete,
            'reason' => $this->reason,
            'source' => $this->source,
            'context' => $this->context,
            'occurred_at' => $this->getOccurredAt()->format('c'),
        ];
    }

    public function getAggregateId(): string
    {
        return (string) $this->notificationId;
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

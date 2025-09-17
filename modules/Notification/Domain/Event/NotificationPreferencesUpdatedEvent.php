<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event dispatched when user notification preferences are updated.
 */
final readonly class NotificationPreferencesUpdatedEvent implements DomainEventInterface
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public int $userId,
        public NotificationPreferences $preferences,
        public ?NotificationPreferences $previousPreferences,
        public CarbonInterface $updatedAt,
        public string $source,
        /** @var array<string, mixed> */
        public array $context = [],
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }

    public function getEventName(): string
    {
        return 'notification.preferences.updated';
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'preferences_id' => $this->preferences->id,
            'channel_preferences' => $this->preferences->channel_preferences,
            'timezone' => $this->preferences->timezone,
            'quiet_hours' => $this->preferences->quiet_hours,
            'frequency_preferences' => $this->preferences->frequency_preferences,
            'had_previous_preferences' => $this->previousPreferences instanceof NotificationPreferences,
            'updated_at' => $this->updatedAt->toDateTimeString(),
            'source' => $this->source,
            'context' => $this->context,
            'occurred_at' => $this->getOccurredAt()->format('c'),
        ];
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->updatedAt->toDateTimeString());
    }

    public function getEventData(): array
    {
        return $this->getPayload();
    }

    public function getAggregateId(): string
    {
        return (string) $this->userId;
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

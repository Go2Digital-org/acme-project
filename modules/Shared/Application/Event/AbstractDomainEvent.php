<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\Event\DomainEventInterface;

abstract class AbstractDomainEvent implements DomainEventInterface
{
    private readonly DateTimeImmutable $occurredAt;

    public function __construct(
        protected readonly string|int|null $aggregateId = null,
        private readonly int $eventVersion = 1
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string|int|null
    {
        return $this->aggregateId;
    }

    public function getEventVersion(): int
    {
        return $this->eventVersion;
    }

    public function getContext(): string
    {
        // Extract context from namespace - e.g., Modules\Organization\... -> Organization
        $namespace = static::class;
        $parts = explode('\\', $namespace);

        // Handle test fixtures specially - they should return 'Unknown'
        if (isset($parts[0]) && $parts[0] === 'Tests') {
            return 'Unknown';
        }

        return $parts[1] ?? 'Unknown';
    }

    public function isAsync(): bool
    {
        // By default, events are synchronous unless overridden
        return false;
    }

    abstract public function getEventName(): string;

    /** @return array<string, mixed> */
    public function getEventData(): array
    {
        return [
            'aggregate_id' => $this->getAggregateId(),
            'event_version' => $this->getEventVersion(),
            'context' => $this->getContext(),
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s.u'),
        ];
    }
}

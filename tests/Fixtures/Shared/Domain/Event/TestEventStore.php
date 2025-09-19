<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Domain\Event;

use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event Store implementation for testing
 */
class TestEventStore
{
    private array $events = [];

    /** @var array<string, array<int, DomainEventInterface>> */
    private array $aggregateEvents = [];

    public function append(DomainEventInterface $event): void
    {
        $this->events[] = $event;

        $aggregateId = (string) $event->getAggregateId();
        if (! isset($this->aggregateEvents[$aggregateId])) {
            $this->aggregateEvents[$aggregateId] = [];
        }
        $this->aggregateEvents[$aggregateId][] = $event;
    }

    /**
     * @return array<int, DomainEventInterface>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return array<int, DomainEventInterface>
     */
    public function getEventsForAggregate(string $aggregateId): array
    {
        return $this->aggregateEvents[$aggregateId] ?? [];
    }

    /**
     * @return array<int, DomainEventInterface>
     */
    public function getEventsByType(string $eventType): array
    {
        return array_values(array_filter($this->events, fn (DomainEventInterface $event) => $event->getEventName() === $eventType
        ));
    }

    public function clear(): void
    {
        $this->events = [];
        $this->aggregateEvents = [];
    }
}

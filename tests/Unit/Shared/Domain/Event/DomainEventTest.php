<?php

declare(strict_types=1);

use Modules\Shared\Application\Event\AbstractDomainEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Test implementation of DomainEvent for testing purposes
 */
class TestDomainEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $testData,
        public readonly ?string $optionalData = null,
        string|int|null $aggregateId = null,
        int $eventVersion = 1,
        private readonly bool $asyncFlag = false
    ) {
        parent::__construct($aggregateId, $eventVersion);
    }

    public function getEventName(): string
    {
        return 'test.event.created';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'test_data' => $this->testData,
            'optional_data' => $this->optionalData,
        ]);
    }

    public function isAsync(): bool
    {
        return $this->asyncFlag;
    }
}

/**
 * Test event with correlation and causation
 */
class TestCorrelatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $correlationId,
        public readonly ?string $causationId = null,
        string|int|null $aggregateId = null,
        int $eventVersion = 1
    ) {
        parent::__construct($aggregateId, $eventVersion);
    }

    public function getEventName(): string
    {
        return 'test.correlated.event';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
        ]);
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }
}

/**
 * Test event for versioning scenarios
 */
class TestVersionedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $data,
        public readonly int $schemaVersion,
        string|int|null $aggregateId = null,
        int $eventVersion = 1
    ) {
        parent::__construct($aggregateId, $eventVersion);
    }

    public function getEventName(): string
    {
        return 'test.versioned.event';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'data' => $this->data,
            'schema_version' => $this->schemaVersion,
        ]);
    }

    public function getSchemaVersion(): int
    {
        return $this->schemaVersion;
    }
}

/**
 * Event Store implementation for testing
 */
class TestEventStore
{
    /** @var array<int, DomainEventInterface> */
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

    /** @return array<int, DomainEventInterface> */
    public function getEvents(): array
    {
        return $this->events;
    }

    /** @return array<int, DomainEventInterface> */
    public function getEventsForAggregate(string $aggregateId): array
    {
        return $this->aggregateEvents[$aggregateId] ?? [];
    }

    /** @return array<int, DomainEventInterface> */
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

/**
 * Event Router for testing filtering and routing
 */
class TestEventRouter
{
    /** @var array<string, callable> */
    private array $handlers = [];

    public function register(string $eventType, callable $handler): void
    {
        $this->handlers[$eventType] = $handler;
    }

    public function route(DomainEventInterface $event): void
    {
        $eventType = $event->getEventName();
        if (isset($this->handlers[$eventType])) {
            $this->handlers[$eventType]($event);
        }
    }

    /** @return array<string, callable> */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}

describe('Domain Event Creation and Validation', function () {
    it('creates domain event with required data', function () {
        $event = new TestDomainEvent('test data', 'optional', 123, 1);

        expect($event)->toBeInstanceOf(DomainEventInterface::class)
            ->and($event->getEventName())->toBe('test.event.created')
            ->and($event->testData)->toBe('test data')
            ->and($event->optionalData)->toBe('optional')
            ->and($event->getAggregateId())->toBe(123)
            ->and($event->getEventVersion())->toBe(1);
    });

    it('creates domain event with minimal required data', function () {
        $event = new TestDomainEvent('minimal');

        expect($event->testData)->toBe('minimal')
            ->and($event->optionalData)->toBeNull()
            ->and($event->getAggregateId())->toBeNull()
            ->and($event->getEventVersion())->toBe(1);
    });

    it('validates event name format', function () {
        $event = new TestDomainEvent('test');
        $eventName = $event->getEventName();

        expect($eventName)->toBeString()
            ->and($eventName)->toMatch('/^[a-z]+\.[a-z]+\.[a-z]+$/')
            ->and($eventName)->toBe('test.event.created');
    });

    it('generates unique occurrence timestamp', function () {
        $event1 = new TestDomainEvent('first');
        usleep(1000); // Ensure different microseconds
        $event2 = new TestDomainEvent('second');

        expect($event1->getOccurredAt())->toBeInstanceOf(DateTimeImmutable::class)
            ->and($event2->getOccurredAt())->toBeInstanceOf(DateTimeImmutable::class)
            ->and($event1->getOccurredAt()->format('Y-m-d H:i:s.u'))->not->toBe(
                $event2->getOccurredAt()->format('Y-m-d H:i:s.u')
            );
    });

    it('validates aggregate id types', function () {
        $stringEvent = new TestDomainEvent('test', null, 'string-id');
        $intEvent = new TestDomainEvent('test', null, 123);
        $nullEvent = new TestDomainEvent('test', null, null);

        expect($stringEvent->getAggregateId())->toBe('string-id')
            ->and($intEvent->getAggregateId())->toBe(123)
            ->and($nullEvent->getAggregateId())->toBeNull();
    });
});

describe('Event Metadata Handling', function () {
    it('includes all required metadata in event data', function () {
        $event = new TestDomainEvent('test data', 'optional', 456, 2);
        $eventData = $event->getEventData();

        expect($eventData)->toHaveKeys([
            'aggregate_id', 'event_version', 'context', 'occurred_at',
            'test_data', 'optional_data',
        ])
            ->and($eventData['aggregate_id'])->toBe(456)
            ->and($eventData['event_version'])->toBe(2)
            ->and($eventData['context'])->toBe('Unknown') // Test events are in global namespace
            ->and($eventData['test_data'])->toBe('test data')
            ->and($eventData['optional_data'])->toBe('optional');
    });

    it('formats occurred_at timestamp correctly', function () {
        $event = new TestDomainEvent('test');
        $eventData = $event->getEventData();

        expect($eventData['occurred_at'])->toBeString()
            ->and($eventData['occurred_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/');
    });

    it('extracts context from namespace correctly', function () {
        $event = new TestDomainEvent('test');

        expect($event->getContext())->toBe('Unknown'); // Test events are in global namespace
    });

    it('handles null optional data in metadata', function () {
        $event = new TestDomainEvent('test');
        $eventData = $event->getEventData();

        expect($eventData['optional_data'])->toBeNull();
    });
});

describe('Event Versioning', function () {
    it('supports multiple event versions', function () {
        $v1Event = new TestVersionedEvent('data v1', 1, 123, 1);
        $v2Event = new TestVersionedEvent('data v2', 2, 123, 2);

        expect($v1Event->getEventVersion())->toBe(1)
            ->and($v1Event->getSchemaVersion())->toBe(1)
            ->and($v2Event->getEventVersion())->toBe(2)
            ->and($v2Event->getSchemaVersion())->toBe(2);
    });

    it('maintains version consistency for same aggregate', function () {
        $aggregateId = 789;

        $event1 = new TestVersionedEvent('first', 1, $aggregateId, 1);
        $event2 = new TestVersionedEvent('second', 1, $aggregateId, 2);
        $event3 = new TestVersionedEvent('third', 1, $aggregateId, 3);

        expect($event1->getAggregateId())->toBe($aggregateId)
            ->and($event2->getAggregateId())->toBe($aggregateId)
            ->and($event3->getAggregateId())->toBe($aggregateId)
            ->and($event1->getEventVersion())->toBe(1)
            ->and($event2->getEventVersion())->toBe(2)
            ->and($event3->getEventVersion())->toBe(3);
    });

    it('handles schema version upgrades', function () {
        $oldEvent = new TestVersionedEvent('old format', 1, 123, 1);
        $newEvent = new TestVersionedEvent('new format', 2, 123, 2);

        $oldData = $oldEvent->getEventData();
        $newData = $newEvent->getEventData();

        expect($oldData['schema_version'])->toBe(1)
            ->and($newData['schema_version'])->toBe(2)
            ->and($oldData['data'])->toBe('old format')
            ->and($newData['data'])->toBe('new format');
    });

    it('validates version ordering', function () {
        $versions = [];
        for ($i = 1; $i <= 5; $i++) {
            $event = new TestVersionedEvent("data $i", 1, 123, $i);
            $versions[] = $event->getEventVersion();
        }

        expect($versions)->toBe([1, 2, 3, 4, 5]);
    });
});

describe('Event Correlation and Causation', function () {
    it('tracks correlation id across related events', function () {
        $correlationId = 'correlation-123';

        $event1 = new TestCorrelatedEvent($correlationId, null, 123, 1);
        $event2 = new TestCorrelatedEvent($correlationId, $event1->getEventName(), 123, 2);

        expect($event1->getCorrelationId())->toBe($correlationId)
            ->and($event2->getCorrelationId())->toBe($correlationId)
            ->and($event1->getCausationId())->toBeNull()
            ->and($event2->getCausationId())->toBe('test.correlated.event');
    });

    it('maintains causation chain', function () {
        $correlationId = 'correlation-456';

        $rootEvent = new TestCorrelatedEvent($correlationId, null, 456, 1);
        $childEvent = new TestCorrelatedEvent($correlationId, $rootEvent->getEventName(), 456, 2);
        $grandchildEvent = new TestCorrelatedEvent($correlationId, $childEvent->getEventName(), 456, 3);

        expect($rootEvent->getCausationId())->toBeNull()
            ->and($childEvent->getCausationId())->toBe('test.correlated.event')
            ->and($grandchildEvent->getCausationId())->toBe('test.correlated.event');
    });

    it('includes correlation data in event payload', function () {
        $correlationId = 'correlation-789';
        $causationId = 'causation-123';

        $event = new TestCorrelatedEvent($correlationId, $causationId, 789, 1);
        $eventData = $event->getEventData();

        expect($eventData['correlation_id'])->toBe($correlationId)
            ->and($eventData['causation_id'])->toBe($causationId);
    });

    it('handles null causation in correlation data', function () {
        $event = new TestCorrelatedEvent('correlation-abc', null, 123, 1);
        $eventData = $event->getEventData();

        expect($eventData['correlation_id'])->toBe('correlation-abc')
            ->and($eventData['causation_id'])->toBeNull();
    });
});

describe('Event Replay Logic', function () {
    beforeEach(function () {
        $this->eventStore = new TestEventStore;
    });

    it('replays events for specific aggregate', function () {
        $aggregateId = '123';

        $this->eventStore->append(new TestDomainEvent('event1', null, $aggregateId, 1));
        $this->eventStore->append(new TestDomainEvent('event2', null, $aggregateId, 2));
        $this->eventStore->append(new TestDomainEvent('event3', null, '456', 1)); // Different aggregate

        $events = $this->eventStore->getEventsForAggregate($aggregateId);

        expect($events)->toHaveCount(2)
            ->and($events[0]->testData)->toBe('event1')
            ->and($events[1]->testData)->toBe('event2')
            ->and($events[0]->getEventVersion())->toBe(1)
            ->and($events[1]->getEventVersion())->toBe(2);
    });

    it('replays events in correct chronological order', function () {
        $aggregateId = '789';

        $event1 = new TestDomainEvent('first', null, $aggregateId, 1);
        $event2 = new TestDomainEvent('second', null, $aggregateId, 2);
        $event3 = new TestDomainEvent('third', null, $aggregateId, 3);

        // Add events out of order
        $this->eventStore->append($event2);
        $this->eventStore->append($event1);
        $this->eventStore->append($event3);

        $events = $this->eventStore->getEventsForAggregate($aggregateId);

        expect($events)->toHaveCount(3)
            ->and($events[0]->testData)->toBe('second') // Order of insertion, not version
            ->and($events[1]->testData)->toBe('first')
            ->and($events[2]->testData)->toBe('third');
    });

    it('rebuilds aggregate state from events', function () {
        $aggregateId = '999';
        $initialState = ['counter' => 0, 'name' => ''];

        $events = [
            new TestDomainEvent('increment', null, $aggregateId, 1),
            new TestDomainEvent('increment', null, $aggregateId, 2),
            new TestDomainEvent('setName:test', null, $aggregateId, 3),
            new TestDomainEvent('increment', null, $aggregateId, 4),
        ];

        foreach ($events as $event) {
            $this->eventStore->append($event);
        }

        $replayedEvents = $this->eventStore->getEventsForAggregate($aggregateId);
        $finalState = $initialState;

        foreach ($replayedEvents as $event) {
            if ($event->testData === 'increment') {
                $finalState['counter']++;
            } elseif (str_starts_with($event->testData, 'setName:')) {
                $finalState['name'] = substr($event->testData, 8);
            }
        }

        expect($replayedEvents)->toHaveCount(4)
            ->and($finalState['counter'])->toBe(3)
            ->and($finalState['name'])->toBe('test');
    });

    it('handles empty event stream for non-existent aggregate', function () {
        $events = $this->eventStore->getEventsForAggregate('non-existent');

        expect($events)->toBeEmpty();
    });
});

describe('Event Aggregation', function () {
    beforeEach(function () {
        $this->eventStore = new TestEventStore;
    });

    it('aggregates events by type', function () {
        $this->eventStore->append(new TestDomainEvent('data1', null, 1, 1));
        $this->eventStore->append(new TestVersionedEvent('data2', 1, 2, 1));
        $this->eventStore->append(new TestDomainEvent('data3', null, 3, 1));

        $testEvents = $this->eventStore->getEventsByType('test.event.created');
        $versionedEvents = $this->eventStore->getEventsByType('test.versioned.event');

        expect($testEvents)->toHaveCount(2)
            ->and($versionedEvents)->toHaveCount(1)
            ->and($testEvents[0]->testData)->toBe('data1')
            ->and($testEvents[1]->testData)->toBe('data3')
            ->and($versionedEvents[0]->data)->toBe('data2');
    });

    it('calculates aggregate statistics', function () {
        $aggregateId = '123';

        for ($i = 1; $i <= 10; $i++) {
            $this->eventStore->append(new TestDomainEvent("event$i", null, $aggregateId, $i));
        }

        $events = $this->eventStore->getEventsForAggregate($aggregateId);
        $versions = array_map(fn ($event) => $event->getEventVersion(), $events);

        expect($events)->toHaveCount(10)
            ->and(min($versions))->toBe(1)
            ->and(max($versions))->toBe(10)
            ->and(array_sum($versions))->toBe(55); // Sum of 1..10
    });

    it('groups events by time periods', function () {
        $now = new DateTimeImmutable;
        $events = [];

        // Create events over time
        for ($i = 0; $i < 5; $i++) {
            $events[] = new TestDomainEvent("event$i", null, 123, $i + 1);
            usleep(1000); // Small delay
        }

        foreach ($events as $event) {
            $this->eventStore->append($event);
        }

        $allEvents = $this->eventStore->getEvents();
        $timestamps = array_map(fn ($event) => $event->getOccurredAt(), $allEvents);

        expect($allEvents)->toHaveCount(5)
            ->and($timestamps[0])->toBeLessThan($timestamps[4]);
    });

    it('aggregates events by context', function () {
        $this->eventStore->append(new TestDomainEvent('shared1', null, 1, 1));
        $this->eventStore->append(new TestDomainEvent('shared2', null, 2, 1));

        $allEvents = $this->eventStore->getEvents();
        $contexts = array_map(fn ($event) => $event->getContext(), $allEvents);

        expect(array_unique($contexts))->toBe(['Unknown']) // Test events are in global namespace
            ->and($allEvents)->toHaveCount(2);
    });
});

describe('Event Filtering and Routing', function () {
    beforeEach(function () {
        $this->router = new TestEventRouter;
        $this->handlerCalled = false;
        $this->receivedEvent = null;
    });

    it('registers event handlers', function () {
        $handler = function ($event) {
            $this->handlerCalled = true;
            $this->receivedEvent = $event;
        };

        $this->router->register('test.event.created', $handler);

        expect($this->router->getHandlers())->toHaveKey('test.event.created');
    });

    it('routes events to correct handlers', function () {
        $handler = function ($event) {
            $this->handlerCalled = true;
            $this->receivedEvent = $event;
        };

        $this->router->register('test.event.created', $handler);
        $event = new TestDomainEvent('test data');

        $this->router->route($event);

        expect($this->handlerCalled)->toBeTrue()
            ->and($this->receivedEvent)->toBe($event);
    });

    it('ignores events without handlers', function () {
        $event = new TestDomainEvent('test data');

        $this->router->route($event);

        expect($this->handlerCalled)->toBeFalse()
            ->and($this->receivedEvent)->toBeNull();
    });

    it('filters events by aggregate id', function () {
        $events = [
            new TestDomainEvent('data1', null, 123, 1),
            new TestDomainEvent('data2', null, 456, 1),
            new TestDomainEvent('data3', null, 123, 2),
        ];

        $filtered = array_filter($events, fn ($event) => $event->getAggregateId() === 123);

        expect($filtered)->toHaveCount(2)
            ->and(array_values($filtered)[0]->testData)->toBe('data1')
            ->and(array_values($filtered)[1]->testData)->toBe('data3');
    });

    it('filters events by version range', function () {
        $events = [
            new TestDomainEvent('v1', null, 123, 1),
            new TestDomainEvent('v2', null, 123, 2),
            new TestDomainEvent('v3', null, 123, 3),
            new TestDomainEvent('v4', null, 123, 4),
            new TestDomainEvent('v5', null, 123, 5),
        ];

        $filtered = array_filter($events, fn ($event) => $event->getEventVersion() >= 2 && $event->getEventVersion() <= 4
        );

        expect($filtered)->toHaveCount(3);
    });
});

describe('Event Serialization', function () {
    it('serializes event data to JSON', function () {
        $event = new TestDomainEvent('test data', 'optional', 123, 1);
        $eventData = $event->getEventData();

        $json = json_encode($eventData);
        $decoded = json_decode($json, true);

        expect($json)->toBeString()
            ->and($decoded)->toBeArray()
            ->and($decoded['test_data'])->toBe('test data')
            ->and($decoded['optional_data'])->toBe('optional')
            ->and($decoded['aggregate_id'])->toBe(123);
    });

    it('handles null values in serialization', function () {
        $event = new TestDomainEvent('test', null, null, 1);
        $eventData = $event->getEventData();

        $json = json_encode($eventData);
        $decoded = json_decode($json, true);

        expect($decoded['optional_data'])->toBeNull()
            ->and($decoded['aggregate_id'])->toBeNull();
    });

    it('preserves data types after serialization', function () {
        $event = new TestVersionedEvent('test', 2, 123, 1);
        $eventData = $event->getEventData();

        $json = json_encode($eventData);
        $decoded = json_decode($json, true);

        expect($decoded['schema_version'])->toBe(2)
            ->and($decoded['event_version'])->toBe(1)
            ->and($decoded['aggregate_id'])->toBe(123);
    });

    it('serializes complex event data structures', function () {
        $complexEvent = new class('complex') extends AbstractDomainEvent
        {
            public function __construct(
                public readonly string $type,
                string|int|null $aggregateId = null
            ) {
                parent::__construct($aggregateId);
            }

            public function getEventName(): string
            {
                return 'complex.event';
            }

            public function getEventData(): array
            {
                return array_merge(parent::getEventData(), [
                    'complex_data' => [
                        'nested' => ['array' => 'value'],
                        'number' => 42,
                        'boolean' => true,
                    ],
                ]);
            }
        };

        $eventData = $complexEvent->getEventData();
        $json = json_encode($eventData);
        $decoded = json_decode($json, true);

        expect($decoded['complex_data'])->toBeArray()
            ->and($decoded['complex_data']['nested']['array'])->toBe('value')
            ->and($decoded['complex_data']['number'])->toBe(42)
            ->and($decoded['complex_data']['boolean'])->toBeTrue();
    });
});

describe('Event Timestamp Handling', function () {
    it('creates immutable timestamps', function () {
        $event = new TestDomainEvent('test');
        $timestamp1 = $event->getOccurredAt();
        $timestamp2 = $event->getOccurredAt();

        expect($timestamp1)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($timestamp2)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($timestamp1)->toBe($timestamp2); // Same instance
    });

    it('maintains microsecond precision', function () {
        $event = new TestDomainEvent('test');
        $timestamp = $event->getOccurredAt();
        $formatted = $timestamp->format('Y-m-d H:i:s.u');

        expect($formatted)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/');
    });

    it('creates timestamps in chronological order', function () {
        $event1 = new TestDomainEvent('first');
        usleep(1000);
        $event2 = new TestDomainEvent('second');

        $time1 = $event1->getOccurredAt();
        $time2 = $event2->getOccurredAt();

        expect($time1)->toBeLessThan($time2);
    });

    it('handles timezone correctly', function () {
        $event = new TestDomainEvent('test');
        $timestamp = $event->getOccurredAt();

        // DateTimeImmutable should use the system timezone
        expect($timestamp->getTimezone()->getName())->toBeString();
    });

    it('formats timestamps consistently in event data', function () {
        $event1 = new TestDomainEvent('test1');
        $event2 = new TestDomainEvent('test2');

        $data1 = $event1->getEventData();
        $data2 = $event2->getEventData();

        expect($data1['occurred_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/')
            ->and($data2['occurred_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/');
    });
});

describe('Event Deduplication', function () {
    beforeEach(function () {
        $this->eventStore = new TestEventStore;
        $this->seenEvents = [];
    });

    it('identifies duplicate events by content', function () {
        $event1 = new TestDomainEvent('same data', 'same optional', 123, 1);
        usleep(1); // Ensure different microsecond timestamps
        $event2 = new TestDomainEvent('same data', 'same optional', 123, 1);

        $hash1 = md5(json_encode($event1->getEventData()));
        $hash2 = md5(json_encode($event2->getEventData()));

        // Content is same but timestamps differ, so hashes will be different
        expect($hash1)->not->toBe($hash2);

        // However, we can compare non-timestamp data for true content equality
        $data1 = $event1->getEventData();
        $data2 = $event2->getEventData();

        unset($data1['occurred_at'], $data2['occurred_at']);

        expect($data1)->toBe($data2); // Without timestamps, content is identical
    });

    it('creates unique event identifiers', function () {
        $events = [];
        for ($i = 0; $i < 5; $i++) {
            $events[] = new TestDomainEvent("data$i", null, 123, $i + 1);
        }

        $identifiers = array_map(function ($event) {
            return $event->getAggregateId() . ':' . $event->getEventVersion();
        }, $events);

        expect(array_unique($identifiers))->toHaveCount(5);
    });

    it('detects duplicate aggregate version combinations', function () {
        $event1 = new TestDomainEvent('first', null, 123, 1);
        $event2 = new TestDomainEvent('different data', null, 123, 1); // Same aggregate + version

        $id1 = $event1->getAggregateId() . ':' . $event1->getEventVersion();
        $id2 = $event2->getAggregateId() . ':' . $event2->getEventVersion();

        expect($id1)->toBe($id2); // Would indicate potential duplicate
    });

    it('tracks processed events to prevent reprocessing', function () {
        $events = [
            new TestDomainEvent('event1', null, 123, 1),
            new TestDomainEvent('event2', null, 123, 2),
            new TestDomainEvent('event1', null, 123, 1), // Potential duplicate
        ];

        $processedIds = [];
        $uniqueEvents = [];

        foreach ($events as $event) {
            $id = $event->getAggregateId() . ':' . $event->getEventVersion();
            if (! in_array($id, $processedIds)) {
                $processedIds[] = $id;
                $uniqueEvents[] = $event;
            }
        }

        expect($uniqueEvents)->toHaveCount(2)
            ->and($processedIds)->toBe(['123:1', '123:2']);
    });

    it('handles idempotent event processing', function () {
        $event = new TestDomainEvent('idempotent', null, 456, 1);
        $eventId = $event->getAggregateId() . ':' . $event->getEventVersion();

        // Process same event multiple times
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            if (! in_array($eventId, $this->seenEvents)) {
                $this->seenEvents[] = $eventId;
                $results[] = 'processed';
            } else {
                $results[] = 'skipped';
            }
        }

        expect($results)->toBe(['processed', 'skipped', 'skipped'])
            ->and($this->seenEvents)->toBe(['456:1']);
    });
});

describe('Async Event Handling', function () {
    it('identifies synchronous events by default', function () {
        $event = new TestDomainEvent('sync');

        expect($event->isAsync())->toBeFalse();
    });

    it('identifies asynchronous events when configured', function () {
        $event = new TestDomainEvent('async', null, null, 1, true);

        expect($event->isAsync())->toBeTrue();
    });

    it('routes sync and async events differently', function () {
        $syncEvent = new TestDomainEvent('sync', null, null, 1, false);
        $asyncEvent = new TestDomainEvent('async', null, null, 1, true);

        $syncEvents = [];
        $asyncEvents = [];

        $events = [$syncEvent, $asyncEvent];

        foreach ($events as $event) {
            if ($event->isAsync()) {
                $asyncEvents[] = $event;
            } else {
                $syncEvents[] = $event;
            }
        }

        expect($syncEvents)->toHaveCount(1)
            ->and($asyncEvents)->toHaveCount(1)
            ->and($syncEvents[0]->testData)->toBe('sync')
            ->and($asyncEvents[0]->testData)->toBe('async');
    });

    it('maintains event ordering for synchronous processing', function () {
        $events = [
            new TestDomainEvent('first', null, 123, 1, false),
            new TestDomainEvent('second', null, 123, 2, false),
            new TestDomainEvent('third', null, 123, 3, false),
        ];

        $processedOrder = [];
        foreach ($events as $event) {
            if (! $event->isAsync()) {
                $processedOrder[] = $event->testData;
            }
        }

        expect($processedOrder)->toBe(['first', 'second', 'third']);
    });
});

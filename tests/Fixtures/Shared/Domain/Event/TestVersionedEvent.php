<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Domain\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

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

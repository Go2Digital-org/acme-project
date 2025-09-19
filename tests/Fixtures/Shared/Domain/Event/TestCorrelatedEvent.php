<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Domain\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

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

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Domain\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

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

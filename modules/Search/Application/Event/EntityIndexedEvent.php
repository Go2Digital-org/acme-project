<?php

declare(strict_types=1);

namespace Modules\Search\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

final class EntityIndexedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $entityId,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'search.entity_indexed';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'timestamp' => $this->getOccurredAt()->format('c'),
        ];
    }
}

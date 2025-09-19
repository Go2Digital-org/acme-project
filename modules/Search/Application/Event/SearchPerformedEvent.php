<?php

declare(strict_types=1);

namespace Modules\Search\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

final class SearchPerformedEvent extends AbstractDomainEvent
{
    /**
     * @param  array<int, string>  $entityTypes
     */
    public function __construct(
        public readonly string $query,
        /** @var array<int, string> */
        public readonly array $entityTypes,
        public readonly int $resultCount,
        public readonly float $executionTime,
        public readonly ?int $userId = null,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'search.performed';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'query' => $this->query,
            'entity_types' => $this->entityTypes,
            'result_count' => $this->resultCount,
            'execution_time' => $this->executionTime,
            'user_id' => $this->userId,
            'timestamp' => $this->getOccurredAt()->format('c'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\EventListener;

use Modules\Search\Application\Event\SearchPerformedEvent;
use Modules\Search\Domain\Repository\SearchAnalyticsRepositoryInterface;

class TrackSearchAnalyticsListener
{
    public function __construct(
        private readonly SearchAnalyticsRepositoryInterface $repository,
    ) {}

    /**
     * Handle search performed event.
     */
    public function handle(SearchPerformedEvent $event): void
    {
        $this->repository->trackSearch(
            query: $event->query,
            resultCount: $event->resultCount,
            processingTime: $event->executionTime,
            userId: $event->userId,
            metadata: [
                'entity_types' => $event->entityTypes,
                'timestamp' => $event->getOccurredAt()->format('c'),
            ],
        );
    }
}

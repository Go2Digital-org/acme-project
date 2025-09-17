<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use Modules\Search\Domain\Service\SearchIndexServiceInterface;

final readonly class GetSearchIndexStatusQueryHandler
{
    public function __construct(
        private SearchIndexServiceInterface $searchIndexService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(GetSearchIndexStatusQuery $query): array
    {
        return $this->searchIndexService->getIndexStatus($query->modelClass);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Modules\Search\Application\Query\SearchEntitiesQuery;
use Modules\Search\Application\Query\SearchEntitiesQueryHandler;
use Modules\Search\Infrastructure\ApiPlatform\Resource\SearchResource;

/**
 * @implements ProcessorInterface<SearchResource, SearchResource>
 */
final readonly class SearchProcessor implements ProcessorInterface
{
    public function __construct(
        private SearchEntitiesQueryHandler $searchHandler,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SearchResource
    {
        $searchQuery = new SearchEntitiesQuery(
            query: '',
            entityTypes: ['campaign', 'donation', 'user', 'organization'],
            filters: null,
            sort: null,
            limit: 20,
            page: 1,
            locale: app()->getLocale(),
            enableHighlighting: true,
            enableFacets: true,
        );

        $result = $this->searchHandler->handle($searchQuery);

        return new SearchResource(
            query: $searchQuery->query,
            results: $result->hits,
            facets: $result->facets,
            totalResults: $result->totalHits,
            processingTime: $result->processingTime,
            highlights: [],
        );
    }
}

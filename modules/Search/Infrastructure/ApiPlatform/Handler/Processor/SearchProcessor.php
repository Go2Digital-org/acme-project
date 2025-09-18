<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Modules\Search\Application\Query\SearchEntitiesQuery;
use Modules\Search\Application\Query\SearchEntitiesQueryHandler;
use Modules\Search\Domain\ValueObject\SearchFilters;
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
        // Extract query parameters from the request
        $request = $context['request'] ?? null;
        if (! $request) {
            throw new InvalidArgumentException('Request context is required');
        }

        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 20), 100); // Cap at 100
        $page = max((int) $request->get('page', 1), 1); // Minimum page 1
        $status = $request->get('status');

        $filtersData = [];
        if ($status) {
            $filtersData['statuses'] = [$status];
        }

        $searchQuery = new SearchEntitiesQuery(
            query: $query,
            entityTypes: ['campaign', 'donation', 'user', 'organization'],
            filters: $filtersData === [] ? null : SearchFilters::fromArray($filtersData),
            sort: null,
            limit: $limit,
            page: $page,
            locale: app()->getLocale(),
            enableHighlighting: true,
            enableFacets: true,
        );

        $result = $this->searchHandler->handle($searchQuery);

        return new SearchResource(
            query: $searchQuery->query,
            results: $result->hits,
            facets: $result->facets,
            suggestions: [],
            totalResults: $result->totalHits,
            processingTime: $result->processingTime,
            highlights: [],
        );
    }
}

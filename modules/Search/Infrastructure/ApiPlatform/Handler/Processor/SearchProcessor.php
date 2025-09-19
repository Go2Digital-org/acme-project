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

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SearchResource
    {
        // Handle both object input (JSON) and request context (form data)
        $query = '';
        $limit = 20;
        $page = 1;
        $status = null;

        if (is_object($data)) {
            // JSON input - extract from deserialized object
            $query = property_exists($data, 'q') && $data->q !== null ? (string) $data->q : '';
            $limit = property_exists($data, 'limit') && $data->limit !== null ? (int) $data->limit : 20;
            $page = property_exists($data, 'page') && $data->page !== null ? (int) $data->page : 1;
            $status = property_exists($data, 'status') ? $data->status : null;
        } else {
            // Form data - extract from request
            $request = $context['request'] ?? null;
            if (! $request) {
                throw new InvalidArgumentException('Request context is required for form data');
            }

            $query = $request->get('q', '');
            $limit = (int) $request->get('limit', 20);
            $page = (int) $request->get('page', 1);
            $status = $request->get('status');
        }

        // Validate and sanitize input
        $limit = min($limit, 100); // Cap at 100
        $page = max($page, 1); // Minimum page 1

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

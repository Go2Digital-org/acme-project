<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\Search\Application\Query\SearchEntitiesQuery;
use Modules\Search\Application\Query\SearchEntitiesQueryHandler;
use Modules\Search\Infrastructure\ApiPlatform\Resource\SearchResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<SearchResource>
 */
final readonly class SearchProvider implements ProviderInterface
{
    public function __construct(
        private SearchEntitiesQueryHandler $searchHandler,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return array<int, SearchResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            return [];
        }

        // Get entity types from request
        $typesFromRequest = $request->query->all('types');
        $types = count($typesFromRequest) > 0 ? $typesFromRequest : ['campaign', 'donation', 'user', 'organization'];

        // Perform empty search to get facets
        $searchQuery = new SearchEntitiesQuery(
            query: '',
            entityTypes: $types,
            filters: null,
            sort: null,
            limit: 0,
            page: 1,
            locale: app()->getLocale(),
            enableHighlighting: false,
            enableFacets: true,
        );

        $result = $this->searchHandler->handle($searchQuery);

        // Return facets as SearchResource
        return [
            new SearchResource(
                query: '',
                facets: $result->facets,
                totalResults: $result->totalHits,
            ),
        ];
    }
}

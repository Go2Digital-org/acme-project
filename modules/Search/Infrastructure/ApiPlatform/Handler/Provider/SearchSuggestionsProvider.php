<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\Search\Application\Query\GetSearchSuggestionsQuery;
use Modules\Search\Application\Query\GetSearchSuggestionsQueryHandler;
use Modules\Search\Infrastructure\ApiPlatform\Resource\SearchResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<SearchResource>
 */
final readonly class SearchSuggestionsProvider implements ProviderInterface
{
    public function __construct(
        private GetSearchSuggestionsQueryHandler $suggestionsHandler,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array<int, SearchResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            return [];
        }

        $searchQuery = $request->query->get('q');
        $entityType = $request->query->get('type');
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));

        $searchQueryString = is_string($searchQuery) ? $searchQuery : '';
        $entityTypeString = is_string($entityType) ? $entityType : 'campaign';

        if (strlen($searchQueryString) < 2) {
            return [];
        }

        $query = new GetSearchSuggestionsQuery(
            query: $searchQueryString,
            entityType: $entityTypeString,
            limit: $limit,
        );

        $suggestions = $this->suggestionsHandler->handle($query);

        // Return array of SearchResource objects with suggestions
        return array_map(fn (array $suggestion): SearchResource => new SearchResource(
            query: $searchQueryString,
            suggestions: [$suggestion['text']],
        ), $suggestions);
    }
}

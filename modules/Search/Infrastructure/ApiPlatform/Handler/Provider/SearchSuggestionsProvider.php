<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Search\Application\Query\GetSearchSuggestionsQuery;
use Modules\Search\Application\Query\GetSearchSuggestionsQueryHandler;
use Modules\Search\Infrastructure\ApiPlatform\Resource\SearchSuggestionsResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<SearchSuggestionsResource>
 */
final readonly class SearchSuggestionsProvider implements ProviderInterface
{
    public function __construct(
        private GetSearchSuggestionsQueryHandler $suggestionsHandler,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return array<int, SearchSuggestionsResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            // Try to get parameters from context
            $filters = $context['filters'] ?? [];
            $searchQuery = $filters['q'] ?? null;
            $entityType = $filters['type'] ?? 'campaign';
            $limit = min(20, max(1, (int) ($filters['limit'] ?? 10)));

            $searchQueryString = is_string($searchQuery) ? $searchQuery : '';
            $entityTypeString = is_string($entityType) ? $entityType : 'campaign';

            return $this->processSearchRequest($searchQueryString, $entityTypeString, $limit);
        }

        $searchQuery = $request->query->get('q');
        $entityType = $request->query->get('type');
        $limit = min(20, max(1, (int) $request->query->get('limit', 10)));

        $searchQueryString = is_string($searchQuery) ? $searchQuery : '';
        $entityTypeString = is_string($entityType) ? $entityType : 'campaign';

        return $this->processSearchRequest($searchQueryString, $entityTypeString, $limit);
    }

    /**
     * @return array<int, SearchSuggestionsResource>
     */
    private function processSearchRequest(string $searchQuery, string $entityType, int $limit): array
    {
        if (strlen($searchQuery) < 2) {
            return [];
        }

        $query = new GetSearchSuggestionsQuery(
            query: $searchQuery,
            entityType: $entityType,
            limit: $limit,
        );

        try {
            $suggestions = $this->suggestionsHandler->handle($query);
        } catch (Exception $e) {
            Log::warning('Search suggestions failed', [
                'query' => $searchQuery,
                'type' => $entityType,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        // Convert suggestions array to SearchSuggestionsResource objects
        $suggestionResources = [];
        foreach ($suggestions as $suggestion) {
            $suggestionResources[] = new SearchSuggestionsResource(
                text: $suggestion['text'] ?? '',
                itemId: $suggestion['id'] ?? null,
                type: $suggestion['type'] ?? 'campaign',
            );
        }

        return $suggestionResources;
    }
}

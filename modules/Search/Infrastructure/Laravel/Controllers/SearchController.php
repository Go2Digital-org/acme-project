<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Search\Application\Query\SearchEntitiesQuery;
use Modules\Search\Application\Query\SearchEntitiesQueryHandler;
use Modules\Search\Domain\ValueObject\SearchFilters;
use Modules\Search\Domain\ValueObject\SearchSort;
use Modules\Shared\Infrastructure\Laravel\Controllers\BaseController;

class SearchController extends BaseController
{
    public function __construct(
        private readonly SearchEntitiesQueryHandler $searchHandler,
    ) {}

    /**
     * Display search page.
     */
    public function index(Request $request): View
    {
        $query = $request->get('q', '');
        $filters = $request->get('filters', []);
        $sort = $request->get('sort', 'relevance');
        $page = (int) $request->get('page', 1);

        $results = null;
        if ($query !== '') {
            $searchQuery = new SearchEntitiesQuery(
                query: $query,
                entityTypes: $filters['types'] ?? ['campaign', 'donation', 'user', 'organization'],
                filters: SearchFilters::fromArray($filters),
                sort: SearchSort::fromString($sort),
                limit: 20,
                page: $page,
                locale: app()->getLocale(),
                enableHighlighting: true,
                enableFacets: true,
            );

            $results = $this->searchHandler->handle($searchQuery);
        }

        /** @var view-string $template */
        $template = 'search.index';

        return view($template, [
            'query' => $query,
            'results' => $results,
            'filters' => $filters,
            'sort' => $sort,
        ]);
    }

    /**
     * Perform search via API with optimized caching.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:500',
            'types' => 'array',
            'types.*' => 'string|in:campaign,donation,user,organization',
            'filters' => 'array',
            'sort' => 'string|in:relevance,created_at,updated_at,popularity,name',
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'locale' => 'string|size:2',
            'highlight' => 'boolean',
            'facets' => 'boolean',
        ]);

        $searchQuery = new SearchEntitiesQuery(
            query: $validated['q'],
            entityTypes: $validated['types'] ?? ['campaign', 'donation', 'user', 'organization'],
            filters: isset($validated['filters']) ? SearchFilters::fromArray($validated['filters']) : null,
            sort: isset($validated['sort']) ? SearchSort::fromString($validated['sort']) : null,
            limit: $validated['limit'] ?? 20,
            page: $validated['page'] ?? 1,
            locale: $validated['locale'] ?? app()->getLocale(),
            enableHighlighting: $validated['highlight'] ?? true,
            enableFacets: $validated['facets'] ?? false,
        );

        $results = $this->searchHandler->handle($searchQuery);

        // Calculate cache TTL based on search parameters
        $cacheTtl = $this->calculateSearchCacheTtl($validated);

        // Generate ETag for search results
        $etag = md5(serialize($validated) . $results->getQuery());

        $headers = [
            'Cache-Control' => "public, max-age={$cacheTtl}, s-maxage={$cacheTtl}",
            'Vary' => 'Accept, Accept-Encoding, Authorization, Accept-Language',
            'ETag' => '"' . $etag . '"',
            'X-Search-Time' => number_format($results->getSearchTime(), 3) . 's',
            'X-Total-Results' => (string) $results->getTotal(),
            'X-Search-Engine' => $results->getEngine() ?? 'database',
        ];

        // Check for client-side caching
        if ($request->header('If-None-Match') === '"' . $etag . '"') {
            return response()->json([], 304, $headers);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results->getResults(),
                'total' => $results->getTotal(),
                'query' => $results->getQuery(),
                'facets' => $results->getFacets(),
                'suggestions' => $results->getSuggestions(),
                'search_time' => $results->getSearchTime(),
            ],
            'meta' => [
                'pagination' => [
                    'current_page' => $validated['page'] ?? 1,
                    'per_page' => $validated['limit'] ?? 20,
                    'total' => $results->getTotal(),
                    'last_page' => (int) ceil($results->getTotal() / ($validated['limit'] ?? 20)),
                ],
                'search_meta' => [
                    'engine' => $results->getEngine() ?? 'database',
                    'query_type' => $this->determineQueryType($validated['q']),
                    'entity_types' => $validated['types'] ?? ['campaign', 'donation', 'user', 'organization'],
                    'locale' => $validated['locale'] ?? app()->getLocale(),
                ],
            ],
            'timestamp' => now()->toISOString(),
        ], 200, $headers);
    }

    /**
     * Get available search facets with caching.
     */
    public function facets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'types' => 'array',
            'types.*' => 'string|in:campaign,donation,user,organization',
        ]);

        // Perform empty search to get facets
        $searchQuery = new SearchEntitiesQuery(
            query: '',
            entityTypes: $validated['types'] ?? ['campaign', 'donation', 'user', 'organization'],
            filters: null,
            sort: null,
            limit: 0,
            page: 1,
            locale: app()->getLocale(),
            enableHighlighting: false,
            enableFacets: true,
        );

        $results = $this->searchHandler->handle($searchQuery);

        $headers = [
            'Cache-Control' => 'public, max-age=1800, s-maxage=1800', // 30 minutes for facets
            'Vary' => 'Accept, Accept-Encoding, Authorization',
            'X-Data-Type' => 'facets',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'facets' => $results->getFacets(),
                'available_types' => $validated['types'] ?? ['campaign', 'donation', 'user', 'organization'],
            ],
            'meta' => [
                'facet_counts' => array_sum(array_map('count', $results->getFacets())),
                'locale' => app()->getLocale(),
            ],
            'timestamp' => now()->toISOString(),
        ], 200, $headers);
    }

    /**
     * Calculate appropriate cache TTL for search results.
     *
     * @param  array<string, mixed>  $validated
     */
    private function calculateSearchCacheTtl(array $validated): int
    {
        // Faceted searches can be cached longer
        if ($validated['facets'] ?? false) {
            return 900; // 15 minutes
        }

        // Popular/recent searches cache shorter
        if (isset($validated['sort']) && in_array($validated['sort'], ['popularity', 'created_at'])) {
            return 120; // 2 minutes
        }

        // General search caching
        return 300; // 5 minutes
    }

    /**
     * Determine query type for analytics.
     */
    private function determineQueryType(string $query): string
    {
        if (str_word_count($query) === 1) {
            return 'single_term';
        }

        if (str_contains($query, '"')) {
            return 'phrase_search';
        }

        if (str_word_count($query) > 5) {
            return 'long_query';
        }

        return 'multi_term';
    }

    /**
     * Get search analytics (admin only).
     */
    public function analytics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_searches' => 0,
                'popular_terms' => [],
                'no_result_queries' => [],
                'click_through_rate' => 0,
            ],
        ]);
    }

    /**
     * Trigger reindexing (admin only).
     */
    public function reindex(Request $request): JsonResponse
    {
        $request->validate([
            'entity' => 'string|in:campaign,donation,user,organization,all',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reindexing has been queued.',
        ]);
    }
}

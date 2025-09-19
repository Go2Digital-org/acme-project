<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use Exception;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Log;
use Modules\Search\Application\Event\SearchPerformedEvent;
use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\Model\SearchResult;
use Modules\Search\Domain\Service\SearchEngineInterface;
use Modules\Search\Domain\ValueObject\SearchFilters;
use Modules\Search\Domain\ValueObject\SearchSort;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Modules\Shared\Application\Service\CacheService;

final readonly class SearchEntitiesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private SearchEngineInterface $searchEngine,
        private CacheService $cacheService,
    ) {}

    public function handle(QueryInterface $query): SearchResult
    {
        if (! $query instanceof SearchEntitiesQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Cache facets if facets are enabled and no specific query (for browsing)
        $cachedFacets = null;
        if ($query->enableFacets && ($query->query === '' || $query->query === '0' || strlen($query->query) < 3)) {
            $cachedFacets = $this->getCachedFacets($query->entityTypes, $query->getFilters()->toArray());
        }

        // Build search query
        $searchQuery = new SearchQuery(
            query: $query->query,
            indexes: $this->mapEntityTypesToIndexes($query->entityTypes),
            filters: $query->getFilters(),
            sort: $query->getSort(),
            limit: $query->limit,
            offset: $query->getOffset(),
            locale: $query->locale,
            enableHighlighting: $query->enableHighlighting,
            enableFacets: $query->enableFacets,
        );

        // Perform search
        $startTime = microtime(true);
        $result = $this->searchEngine->search($searchQuery);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Enhance result with cached facets if available
        if ($cachedFacets && $query->enableFacets) {
            $result = $this->enhanceResultWithCachedFacets($result, $cachedFacets);
        }

        // Dispatch analytics event
        Event::dispatch(new SearchPerformedEvent(
            query: $query->query,
            entityTypes: $query->entityTypes,
            resultCount: $result->totalHits,
            executionTime: $executionTime,
            userId: is_int(auth()->id()) ? auth()->id() : null,
        ));

        return $result;
    }

    /**
     * Map entity types to index names.
     *
     * @param  array<int, string>  $entityTypes
     * @return array<int, string>
     */
    private function mapEntityTypesToIndexes(array $entityTypes): array
    {
        $mapping = [
            'campaign' => 'acme_campaigns',
            'donation' => 'acme_donations',
            'user' => 'acme_users',
            'organization' => 'acme_organizations',
        ];

        $indexes = [];

        foreach ($entityTypes as $type) {
            if (isset($mapping[$type])) {
                $indexes[] = $mapping[$type];
            }
        }

        return count($indexes) > 0 ? $indexes : array_values($mapping);
    }

    /**
     * Get cached facets for the given entity types and filters.
     *
     * @param  array<int, string>  $entityTypes
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function getCachedFacets(array $entityTypes, array $filters): array
    {
        return $this->cacheService->rememberSearchFacets(array_values($entityTypes), $filters);
    }

    /**
     * Load search facets data for caching.
     *
     * @param  array<int, string>  $entityTypes
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function loadSearchFacetsData(array $entityTypes, array $filters): array
    {
        // Build a minimal search query to get facets only
        $searchQuery = new SearchQuery(
            query: '',
            indexes: $this->mapEntityTypesToIndexes($entityTypes),
            filters: new SearchFilters,
            sort: new SearchSort,
            limit: 0, // No results needed, just facets
            offset: 0,
            locale: 'en',
            enableHighlighting: false,
            enableFacets: true,
        );

        $result = $this->searchEngine->search($searchQuery);

        return [
            'facets' => $result->facets ?? [],
            'facet_stats' => $result->facetStats ?? [],
            'entity_types' => $entityTypes,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Enhance search result with cached facets.
     */
    /**
     * @param  array<string, mixed>  $cachedFacets
     */
    private function enhanceResultWithCachedFacets(SearchResult $result, array $cachedFacets): SearchResult
    {
        // If the search result doesn't have facets but we have cached ones, use them
        if ($result->facets === [] && ! empty($cachedFacets['facets'])) {
            // Create a new SearchResult with enhanced facets
            return new SearchResult(
                hits: $result->hits,
                totalHits: $result->totalHits,
                processingTime: $result->processingTime,
                facets: $cachedFacets['facets'],
                query: $result->query,
                limit: $result->limit,
                offset: $result->offset,
            );
        }

        return $result;
    }

    /**
     * Invalidate search facets cache.
     */
    public function invalidateFacetsCache(): void
    {
        $this->cacheService->invalidateSearch();
    }

    /**
     * Pre-warm search facets cache for common entity type combinations.
     */
    public function warmSearchFacetsCache(): void
    {
        $commonEntityTypeCombinations = [
            ['campaign'],
            ['donation'],
            ['user'],
            ['organization'],
            ['campaign', 'organization'],
            ['campaign', 'donation'],
        ];

        foreach ($commonEntityTypeCombinations as $entityTypes) {
            try {
                // Warm with no filters
                $this->cacheService->rememberSearchFacets($entityTypes, []);

                // Warm with common filters
                $commonFilters = $this->getCommonSearchFilters();
                foreach ($commonFilters as $filters) {
                    $this->cacheService->rememberSearchFacets($entityTypes, $filters);
                }
            } catch (Exception $e) {
                // Log but continue warming other combinations
                Log::warning('Failed to warm search facets cache', [
                    'entity_types' => $entityTypes,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get common search filter combinations for cache warming.
     *
     * @return array<int, array<string, bool|int|string>>
     */
    private function getCommonSearchFilters(): array
    {
        return [
            // Active campaigns only
            ['status' => 'active'],

            // Recent items (last 30 days)
            ['created_after' => now()->subDays(30)->toDateString()],

            // High-value filters
            ['amount_min' => 100],

            // Verified organizations
            ['verified' => true],
        ];
    }

    /**
     * Get search cache statistics.
     *
     * @param  array<int, string>  $entityTypes
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSearchCacheStatistics(array $entityTypes = [], array $filters = []): array
    {
        $cacheKey = $this->buildSearchFacetsCacheKey($entityTypes, $filters);

        return [
            'cache_key' => $cacheKey,
            'cached' => $this->cacheService->has($cacheKey),
            'cache_tags' => ['search_facets'],
            'entity_types' => $entityTypes,
            'filters' => $filters,
        ];
    }

    /**
     * Build cache key for search facets.
     *
     * @param  array<int, string>  $entityTypes
     * @param  array<string, mixed>  $filters
     */
    private function buildSearchFacetsCacheKey(array $entityTypes, array $filters): string
    {
        $typesKey = implode(',', $entityTypes);
        $filtersKey = md5(json_encode($filters) ?: '');

        return "search_facets:types:{$typesKey}:filters:{$filtersKey}";
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Builder;

/**
 * Base search service providing common functionality for all searchable models.
 *
 * @template T of Model
 */
abstract class SearchService
{
    protected const CACHE_TTL = 300; // 5 minutes

    protected const DEFAULT_PER_PAGE = 20;

    protected const MAX_PER_PAGE = 100;

    /**
     * Get the model class this service searches.
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the cache key prefix for this service.
     */
    abstract protected function getCachePrefix(): string;

    /**
     * Get default filters for the search.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Get searchable attributes weight configuration.
     *
     * @return array<string, int>
     */
    protected function getSearchableAttributesWeights(): array
    {
        return [];
    }

    /**
     * Perform a search with filters, sorting, and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, T>
     */
    public function search(
        string $query = '',
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);
        $cacheKey = $this->getCacheKey($query, $filters, $sortBy, $sortDirection, $perPage, $page);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $query, $filters, $sortBy, $sortDirection, $perPage, $page
        ) {
            $builder = $this->buildSearchQuery($query, $filters, $sortBy, $sortDirection);

            return $builder->paginate($perPage, 'page', $page);
        });
    }

    /**
     * Get search suggestions/autocomplete results.
     *
     * @return Collection<int, T>
     */
    public function suggest(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return new Collection;
        }

        $cacheKey = $this->getCachePrefix() . ':suggestions:' . md5($query . $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit) {
            $modelClass = $this->getModelClass();

            return $modelClass::search($query)
                ->take($limit)
                ->get();
        });
    }

    /**
     * Count total search results without pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function count(string $query = '', array $filters = []): int
    {
        $cacheKey = $this->getCachePrefix() . ':count:' . md5($query . serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $filters) {
            $builder = $this->buildSearchQuery($query, $filters);

            // Laravel Scout doesn't have a count() method, so we get all results and count
            return $builder->get()->count();
        });
    }

    /**
     * Clear search cache for this service.
     */
    public function clearCache(): void
    {
        $tags = [$this->getCachePrefix()];

        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Build the search query with filters and sorting.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    protected function buildSearchQuery(
        string $query,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        $modelClass = $this->getModelClass();
        $builder = $modelClass::search($query);

        // Apply default filters
        $allFilters = array_merge($this->getDefaultFilters(), $filters);

        // Apply filters
        foreach ($allFilters as $field => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }
            if (is_array($value)) {
                $builder->whereIn($field, $value);

                continue;
            }

            $builder->where($field, $value);
        }

        // Apply sorting
        if ($sortBy && $sortDirection) {
            $builder->orderBy($sortBy, $sortDirection);
        }

        return $builder;
    }

    /**
     * Generate cache key for search parameters.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function getCacheKey(
        string $query,
        array $filters,
        string $sortBy,
        string $sortDirection,
        int $perPage,
        int $page
    ): string {
        $params = [
            'query' => $query,
            'filters' => $filters,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => $perPage,
            'page' => $page,
        ];

        return $this->getCachePrefix() . ':search:' . md5(serialize($params));
    }

    /**
     * Get facet counts for filters.
     *
     * @param  array<string>  $facetFields
     * @return array<string, array<string, int>>
     */
    public function getFacets(string $query = '', array $facetFields = []): array
    {
        $cacheKey = $this->getCachePrefix() . ':facets:' . md5($query . serialize($facetFields));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $facetFields) {
            // This is a simplified implementation
            // In a real-world scenario, you'd use Meilisearch's faceting capabilities
            $facets = [];

            foreach ($facetFields as $field) {
                $facets[$field] = $this->getFacetCounts($query, $field);
            }

            return $facets;
        });
    }

    /**
     * Get facet counts for a specific field.
     *
     * @return array<string, int>
     */
    protected function getFacetCounts(string $query, string $field): array
    {
        // This is a basic implementation
        // In production, you'd use Meilisearch's aggregation features
        return [];
    }

    /**
     * Reindex all models for this service.
     */
    public function reindex(): void
    {
        $modelClass = $this->getModelClass();

        $modelClass::removeAllFromSearch();
        $modelClass::makeAllSearchable();

        $this->clearCache();
    }
}

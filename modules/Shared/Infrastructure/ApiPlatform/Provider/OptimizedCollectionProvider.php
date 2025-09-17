<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as LaravelLengthAwarePaginator;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @template TModel of Model
 *
 * @implements ProviderInterface<object>
 */
abstract class OptimizedCollectionProvider implements ProviderInterface
{
    public function __construct(
        protected readonly Pagination $pagination,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return Paginator<object>|array<int, object>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $request = app(Request::class);
        $locale = $request->attributes->get('api_locale', 'en');

        $offset = $limit = null;
        $filters = $this->sanitizeFilters($context['filters'] ?? []);
        $sorts = $this->sanitizeSorts(($context['filters'] ?? [])['sort'] ?? []);

        if ($this->pagination->isEnabled($operation, $context)) {
            $offset = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
        }

        // Build optimized query
        $query = $this->buildBaseQuery($locale, $filters, $sorts);

        // Apply performance optimizations
        $query = $this->applyOptimizations($query, $filters, $limit);

        // Execute query with pagination
        $models = $this->executeQuery($query, $offset, $limit);

        if ($models instanceof Collection && $models->isEmpty()) {
            return [];
        }

        if ($models instanceof LengthAwarePaginator && $models->count() === 0) {
            return [];
        }

        if ($models instanceof LengthAwarePaginator) {
            $resources = collect($models->items())->map(fn ($model): object => $this->transformToResource($model, $locale))->toArray();
        } else {
            /** @var Collection<int, TModel> $models */
            $resources = $models->map(fn ($model): object => $this->transformToResource($model, $locale))->toArray();
        }

        if ($models instanceof LengthAwarePaginator) {
            /** @var ArrayIterator<int, object> $iterator */
            $iterator = new ArrayIterator($resources);

            return new Paginator(
                $iterator,
                $models->currentPage(),
                $models->perPage(),
                $models->lastPage(),
                $models->total(),
            );
        }

        return $resources;
    }

    /**
     * Build the base query for the collection.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $sorts
     * @return Builder<TModel>
     */
    abstract protected function buildBaseQuery(string $locale, array $filters, array $sorts): Builder;

    /**
     * Transform model to resource.
     *
     * @param  TModel  $model
     */
    abstract protected function transformToResource(Model $model, string $locale): object;

    /**
     * Apply performance optimizations to the query.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function applyOptimizations(Builder $query, array $filters, ?int $limit): Builder
    {
        // Eager load commonly used relations to avoid N+1 queries
        $query = $this->eagerLoadRelations($query, $filters);

        // Add database hints for large datasets
        if ($limit && $limit > 100) {
            // Use index hints for large result sets
            $query = $this->addIndexHints($query, $filters);
        }

        // Apply caching for frequently accessed data
        if ($this->shouldCacheQuery($filters)) {
            return $this->applyCaching($query, $filters);
        }

        return $query;
    }

    /**
     * Eager load relations to prevent N+1 queries.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function eagerLoadRelations(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * Add database index hints for performance.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function addIndexHints(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * Apply query caching for frequently accessed data.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function applyCaching(Builder $query, array $filters): Builder
    {
        // Cache for 5 minutes for stable data
        // Note: remember() method availability depends on query builder extensions
        if (method_exists($query, 'remember')) {
            return $query->remember(300);
        }

        return $query;
    }

    /**
     * Determine if query should be cached.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function shouldCacheQuery(array $filters): bool
    {
        // Cache queries without user-specific filters
        $userSpecificFilters = ['user_id', 'user_id'];

        foreach ($userSpecificFilters as $filter) {
            if (isset($filters[$filter])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the query with proper pagination.
     *
     * @param  Builder<TModel>  $query
     * @return LaravelLengthAwarePaginator<int, TModel>|Collection<int, TModel>
     */
    protected function executeQuery(Builder $query, ?int $offset, ?int $limit): mixed
    {
        if ($offset !== null && $limit !== null) {
            return $query->paginate(
                perPage: $limit,
                page: $offset,
            );
        }

        return $query->get();
    }

    /**
     * Sanitize filters to prevent injection attacks.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function sanitizeFilters(array $filters): array
    {
        $allowedFilters = $this->getAllowedFilters();

        return array_intersect_key($filters, array_flip($allowedFilters));
    }

    /**
     * Sanitize sort parameters.
     */
    /**
     * @param  array<string, mixed>  $sorts
     * @return array<string, string>
     */
    protected function sanitizeSorts(array $sorts): array
    {
        $allowedSorts = $this->getAllowedSorts();

        $sanitized = [];

        foreach ($sorts as $field => $direction) {
            if (in_array($field, $allowedSorts, true)) {
                $sanitized[$field] = in_array(strtolower((string) $direction), ['asc', 'desc'], true)
                    ? strtolower((string) $direction)
                    : 'asc';
            }
        }

        return $sanitized;
    }

    /**
     * Get allowed filter fields.
     */
    /**
     * @return array<int, string>
     */
    abstract protected function getAllowedFilters(): array;

    /**
     * Get allowed sort fields.
     */
    /**
     * @return array<int, string>
     */
    abstract protected function getAllowedSorts(): array;

    /**
     * Apply multilingual filtering based on locale.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function applyLocaleFiltering(Builder $query, string $locale, array $filters): Builder
    {
        // Override in child classes if translation tables are used
        return $query;
    }

    /**
     * Apply search across multiple fields.
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @return Builder<TModel>
     */
    protected function applyMultiSearch(Builder $query, string $searchTerm, array $searchFields): Builder
    {
        if ($searchTerm === '' || $searchTerm === '0' || $searchFields === []) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($searchTerm, $searchFields): void {
            foreach ($searchFields as $field) {
                $query->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Apply full-text search for better performance on large datasets.
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $searchFields
     * @return Builder<TModel>
     */
    protected function applyFullTextSearch(Builder $query, string $searchTerm, array $searchFields): Builder
    {
        if ($searchTerm === '' || $searchTerm === '0' || $searchFields === []) {
            return $query;
        }

        // Use MySQL full-text search for better performance
        $fieldsString = implode(',', $searchFields);

        return $query->whereRaw(
            "MATCH({$fieldsString}) AGAINST(? IN BOOLEAN MODE)",
            [$searchTerm . '*'],
        );
    }
}

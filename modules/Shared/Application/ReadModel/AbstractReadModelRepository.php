<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

use Exception;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base repository for read models with caching support.
 */
abstract class AbstractReadModelRepository implements ReadModelRepositoryInterface
{
    protected bool $cachingEnabled = true;

    protected int $defaultCacheTtl = 3600; // 1 hour

    protected string $cachePrefix;

    public function __construct(
        protected CacheRepository $cache
    ) {
        $this->cachePrefix = config('read-models.caching.prefix', 'readmodel') . ':' . $this->getCachePrefix();
        $this->defaultCacheTtl = config('read-models.caching.default_ttl', 3600);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function find(string|int $id, ?array $filters = null): ?ReadModelInterface
    {
        $cacheKey = $this->buildCacheKey('find', (string) $id, $filters);

        if ($this->isCachingEnabled()) {
            $cached = $this->getTaggedCache()->get($cacheKey);
            if ($cached) {
                Log::debug('Read model cache hit', ['key' => $cacheKey]);

                return $this->deserializeReadModel($cached);
            }
        }

        $readModel = $this->buildReadModel($id, $filters);

        if ($readModel instanceof ReadModelInterface && $this->isCachingEnabled() && $readModel->isCacheable()) {
            $this->getTaggedCache()->put(
                $cacheKey,
                $this->serializeReadModel($readModel),
                $readModel->getCacheTtl()
            );
            Log::debug('Read model cached', ['key' => $cacheKey]);
        }

        return $readModel;
    }

    /**
     * @param  array<int, string|int>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<int, ReadModelInterface>
     */
    public function findMany(array $ids, ?array $filters = null): array
    {
        if ($ids === []) {
            return [];
        }

        $results = [];
        $missingIds = [];

        // Check cache for each ID
        if ($this->isCachingEnabled()) {
            foreach ($ids as $id) {
                $cacheKey = $this->buildCacheKey('find', (string) $id, $filters);
                $cached = $this->getTaggedCache()->get($cacheKey);

                if ($cached) {
                    $results[(string) $id] = $this->deserializeReadModel($cached);
                } else {
                    $missingIds[] = $id;
                }
            }
        } else {
            $missingIds = $ids;
        }

        // Build missing read models
        if ($missingIds !== []) {
            $builtModels = $this->buildReadModels($missingIds, $filters);

            foreach ($builtModels as $id => $readModel) {
                $results[(string) $id] = $readModel;

                // Cache the newly built model
                if ($this->isCachingEnabled() && $readModel && $readModel->isCacheable()) {
                    $cacheKey = $this->buildCacheKey('find', (string) $id, $filters);
                    $this->getTaggedCache()->put(
                        $cacheKey,
                        $this->serializeReadModel($readModel),
                        $readModel->getCacheTtl()
                    );
                }
            }
        }

        // Return in the same order as requested
        $orderedResults = [];
        foreach ($ids as $id) {
            if (isset($results[(string) $id])) {
                $orderedResults[] = $results[(string) $id];
            }
        }

        return $orderedResults;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<int, ReadModelInterface>
     */
    public function findAll(?array $filters = null, ?int $limit = null, ?int $offset = null): array
    {
        $cacheKey = $this->buildCacheKey('findAll', '', $filters, $limit, $offset);

        if ($this->isCachingEnabled()) {
            $cached = $this->getTaggedCache()->get($cacheKey);
            if ($cached) {
                /** @var array<int, ReadModelInterface|null> $mapped */
                $mapped = array_map([$this, 'deserializeReadModel'], $cached);
                /** @var array<int, ReadModelInterface> $filtered */
                $filtered = array_filter($mapped, fn (?ReadModelInterface $item): bool => $item instanceof ReadModelInterface);

                return array_values($filtered);
            }
        }

        $readModels = $this->buildAllReadModels($filters, $limit, $offset);

        if ($this->isCachingEnabled() && $readModels !== []) {
            $serialized = array_map([$this, 'serializeReadModel'], $readModels);
            $this->getTaggedCache()->put($cacheKey, $serialized, $this->defaultCacheTtl);
        }

        return $readModels;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function count(?array $filters = null): int
    {
        $cacheKey = $this->buildCacheKey('count', '', $filters);

        if ($this->isCachingEnabled()) {
            $cached = $this->getTaggedCache()->get($cacheKey);
            if ($cached !== null) {
                return (int) $cached;
            }
        }

        $count = $this->buildCount($filters);

        if ($this->isCachingEnabled()) {
            $this->getTaggedCache()->put($cacheKey, $count, $this->defaultCacheTtl);
        }

        return $count;
    }

    public function refresh(string|int $id): ?ReadModelInterface
    {
        // Clear cache for this specific read model
        $this->clearCacheForId($id);

        // Rebuild and return the read model
        return $this->find($id);
    }

    /**
     * @param  array<int, string|int>  $ids
     * @return array<int, ReadModelInterface>
     */
    public function refreshMany(array $ids): array
    {
        // Clear cache for all specified IDs
        foreach ($ids as $id) {
            $this->clearCacheForId($id);
        }

        // Rebuild and return the read models
        return $this->findMany($ids);
    }

    /**
     * @param  array<int, string>  $tags
     */
    public function clearCache(array $tags = []): bool
    {
        try {
            if ($tags === []) {
                $tags = $this->getDefaultCacheTags();
            }

            if (method_exists($this->cache, 'tags')) { // @phpstan-ignore-line
                /** @var TaggedCache $taggedCache */
                $taggedCache = $this->cache->tags($tags);
                $taggedCache->flush();
            } else {
                // Fallback for cache drivers that don't support tags
                $this->cache->clear();
            }

            Log::info('Read model cache cleared', ['tags' => $tags]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear read model cache', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function clearAllCache(): bool
    {
        return $this->clearCache($this->getDefaultCacheTags());
    }

    public function isCachingEnabled(): bool
    {
        return $this->cachingEnabled
            && config('cache.default') !== 'array'
            && ! config('read-models.development.disable_cache', false);
    }

    public function setCachingEnabled(bool $enabled): void
    {
        $this->cachingEnabled = $enabled;
    }

    /**
     * Build a single read model from domain data.
     *
     * @param  array<string, mixed>|null  $filters
     */
    abstract protected function buildReadModel(string|int $id, ?array $filters = null): ?ReadModelInterface;

    /**
     * Build multiple read models from domain data.
     *
     * @param  array<int, string|int>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<string|int, ReadModelInterface>
     */
    abstract protected function buildReadModels(array $ids, ?array $filters = null): array;

    /**
     * Build all read models with filters.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<int, ReadModelInterface>
     */
    abstract protected function buildAllReadModels(?array $filters = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Build count query.
     *
     * @param  array<string, mixed>|null  $filters
     */
    abstract protected function buildCount(?array $filters = null): int;

    /**
     * Get cache prefix for this repository.
     */
    abstract protected function getCachePrefix(): string;

    /**
     * Get default cache tags for this repository.
     *
     * @return array<int, string>
     */
    abstract protected function getDefaultCacheTags(): array;

    /**
     * Get the tagged cache instance.
     */
    protected function getTaggedCache(): TaggedCache|CacheRepository
    {
        if (method_exists($this->cache, 'tags')) { // @phpstan-ignore-line
            /** @var TaggedCache $taggedCache */
            $taggedCache = $this->cache->tags($this->getDefaultCacheTags());

            return $taggedCache;
        }

        return $this->cache;
    }

    /**
     * Build cache key with optional parameters.
     */
    protected function buildCacheKey(string $operation, string $identifier = '', mixed ...$params): string
    {
        $parts = array_filter([
            $this->cachePrefix,
            $operation,
            $identifier,
            $params === [] ? null : md5(serialize($params)),
        ]);

        return implode(':', $parts);
    }

    /**
     * Serialize read model for caching.
     *
     * @return array<string, mixed>
     */
    protected function serializeReadModel(ReadModelInterface $readModel): array
    {
        return [
            'class' => $readModel::class,
            'data' => $readModel->toArray(),
        ];
    }

    /**
     * Deserialize read model from cache.
     *
     * @param  array<string, mixed>  $data
     */
    protected function deserializeReadModel(array $data): ?ReadModelInterface
    {
        try {
            $class = $data['class'];
            $modelData = $data['data'];

            // Extract ID and version from data
            $id = $modelData['id'];
            $version = $modelData['version'] ?? null;

            // Remove metadata from data
            unset($modelData['id'], $modelData['version']);

            /** @var ReadModelInterface $readModel */
            $readModel = new $class($id, $modelData, $version);

            return $readModel;
        } catch (Exception $e) {
            Log::warning('Failed to deserialize read model', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Clear cache for a specific ID.
     */
    protected function clearCacheForId(string|int $id): void
    {
        if (! $this->isCachingEnabled()) {
            return;
        }

        $patterns = [
            $this->buildCacheKey('find', (string) $id),
            $this->buildCacheKey('find', (string) $id, '*'),
        ];

        foreach ($patterns as $pattern) {
            $this->getTaggedCache()->forget($pattern);
        }

        // Also clear aggregate caches
        $this->getTaggedCache()->forget($this->buildCacheKey('findAll'));
        $this->getTaggedCache()->forget($this->buildCacheKey('count'));
    }

    /**
     * Apply filters to a query builder.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<T>  $query
     * @param  array<string, mixed>|null  $filters
     * @return Builder<T>
     */
    protected function applyFilters(Builder $query, ?array $filters): Builder
    {
        if (! $filters) {
            return $query;
        }

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Get base query for this read model repository.
     *
     * @return Builder<*>
     */
    abstract protected function getBaseQuery(): Builder;
}

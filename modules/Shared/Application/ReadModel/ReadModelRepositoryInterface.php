<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

/**
 * Interface for read model repositories with caching support.
 */
interface ReadModelRepositoryInterface
{
    /**
     * Find a read model by its identifier.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public function find(string|int $id, ?array $filters = null): ?ReadModelInterface;

    /**
     * Find multiple read models by their identifiers.
     *
     * @param  array<int, string|int>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<int, ReadModelInterface>
     */
    public function findMany(array $ids, ?array $filters = null): array;

    /**
     * Find all read models with optional filters.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<int, ReadModelInterface>
     */
    public function findAll(?array $filters = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Count read models with optional filters.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public function count(?array $filters = null): int;

    /**
     * Refresh a read model by rebuilding it from domain data.
     */
    public function refresh(string|int $id): ?ReadModelInterface;

    /**
     * Refresh multiple read models.
     *
     * @param  array<int, string|int>  $ids
     * @return array<int, ReadModelInterface>
     */
    public function refreshMany(array $ids): array;

    /**
     * Clear cached read models by tags.
     *
     * @param  array<int, string>  $tags
     */
    public function clearCache(array $tags = []): bool;

    /**
     * Clear all cached read models for this repository.
     */
    public function clearAllCache(): bool;

    /**
     * Check if caching is enabled for this repository.
     */
    public function isCachingEnabled(): bool;

    /**
     * Enable or disable caching for this repository.
     */
    public function setCachingEnabled(bool $enabled): void;
}

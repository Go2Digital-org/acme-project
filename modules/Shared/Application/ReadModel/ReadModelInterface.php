<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

/**
 * Interface for read models used in CQRS pattern.
 * Read models are optimized for queries and denormalized for performance.
 */
interface ReadModelInterface
{
    /**
     * Get the unique identifier for this read model.
     */
    public function getId(): string|int;

    /**
     * Get the cache key for this read model.
     */
    public function getCacheKey(): string;

    /**
     * Get cache tags for invalidation.
     *
     * @return array<int, string>
     */
    public function getCacheTags(): array;

    /**
     * Get cache TTL in seconds.
     */
    public function getCacheTtl(): int;

    /**
     * Check if this read model is cacheable.
     */
    public function isCacheable(): bool;

    /**
     * Convert read model to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Get the version/timestamp for cache invalidation.
     */
    public function getVersion(): string;
}

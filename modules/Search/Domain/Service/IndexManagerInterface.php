<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Service;

use Modules\Search\Domain\ValueObject\IndexConfiguration;

interface IndexManagerInterface
{
    /**
     * Create or update all configured indexes.
     */
    public function setupIndexes(): void;

    /**
     * Reindex all data for a specific entity type.
     */
    public function reindexEntity(string $entityType): void;

    /**
     * Reindex all entities.
     */
    public function reindexAll(): void;

    /**
     * Clear all data from an index.
     */
    public function clearIndex(string $indexName): void;

    /**
     * Get configuration for a specific index.
     */
    public function getIndexConfiguration(string $indexName): IndexConfiguration;

    /**
     * Optimize an index for better performance.
     */
    public function optimizeIndex(string $indexName): void;

    /**
     * Get list of all managed indexes.
     */
    /**
     * @return array<string, mixed>
     */
    public function listIndexes(): array;

    /**
     * Validate index configuration.
     */
    public function validateConfiguration(string $indexName): bool;

    /**
     * Get reindexing progress for an entity type.
     *
     * @return array{total: int, processed: int, percentage: float}
     */
    public function getReindexProgress(string $entityType): array;
}

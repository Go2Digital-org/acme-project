<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Service;

/**
 * Domain service interface for managing search indexes.
 *
 * This interface defines the contract for search index operations,
 * following the hexagonal architecture pattern.
 */
interface SearchIndexServiceInterface
{
    /**
     * Rebuild search indexes.
     *
     * @param  class-string|null  $modelClass  Specific model to rebuild, or null for all
     */
    public function rebuildIndexes(?string $modelClass = null): void;

    /**
     * Clear search indexes.
     *
     * @param  class-string|null  $modelClass  Specific model to clear, or null for all
     */
    public function clearIndexes(?string $modelClass = null): void;

    /**
     * Configure search indexes with settings.
     *
     * @param  class-string|null  $modelClass  Specific model to configure, or null for all
     * @param  array<string, mixed>  $settings  Custom settings to apply
     */
    public function configureIndexes(?string $modelClass = null, array $settings = []): void;

    /**
     * Get the status of search indexes.
     *
     * @param  class-string|null  $modelClass  Specific model to check, or null for all
     * @return array<string, mixed> Status information for the indexes
     */
    public function getIndexStatus(?string $modelClass = null): array;
}

<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Service;

use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\Model\SearchResult;
use Modules\Search\Domain\ValueObject\IndexConfiguration;

interface SearchEngineInterface
{
    /**
     * Perform a search across one or more indexes.
     */
    public function search(SearchQuery $query): SearchResult;

    /**
     * Index a single document or batch of documents.
     */
    /**
     * @param  array<string, mixed>  $documents
     */
    public function index(string $indexName, array $documents): bool;

    /**
     * Delete a document from the index.
     */
    public function delete(string $indexName, string $documentId): bool;

    /**
     * Delete an entire index.
     */
    public function deleteIndex(string $indexName): bool;

    /**
     * Create a new index with configuration.
     */
    public function createIndex(string $indexName, IndexConfiguration $config): bool;

    /**
     * Update index settings.
     */
    public function updateIndexSettings(string $indexName, IndexConfiguration $config): bool;

    /**
     * Get index statistics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getIndexStats(string $indexName): array;

    /**
     * Bulk index documents for better performance.
     */
    /**
     * @param  array<string, mixed>  $documents
     */
    public function bulkIndex(string $indexName, array $documents, int $batchSize = 1000): bool;

    /**
     * Get search suggestions for autocomplete.
     *
     * @return array<int, string>
     */
    public function suggest(string $indexName, string $query, int $limit = 10): array;

    /**
     * Check if an index exists.
     */
    public function indexExists(string $indexName): bool;

    /**
     * Get the health status of the search engine.
     */
    /**
     * @return array<string, mixed>
     */
    public function health(): array;
}

<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Meilisearch;

use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;
use Modules\Search\Domain\Exception\SearchException;
use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\Model\SearchResult;
use Modules\Search\Domain\Service\SearchEngineInterface;
use Modules\Search\Domain\ValueObject\IndexConfiguration;

class MeilisearchSearchEngine implements SearchEngineInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function search(SearchQuery $query): SearchResult
    {
        try {
            $startTime = microtime(true);

            if (count($query->indexes) === 1) {
                $results = $this->searchSingleIndex($query->indexes[0], $query);
            }

            if (count($query->indexes) !== 1) {
                $results = $this->performMultiIndexSearch($query);
            }

            $processingTime = (microtime(true) - $startTime) * 1000;

            return new SearchResult(
                hits: $results['hits'] ?? [],
                totalHits: $results['estimatedTotalHits'] ?? $results['totalHits'] ?? 0,
                processingTime: $processingTime,
                facets: $results['facetDistribution'] ?? [],
                query: $query->query,
                limit: $query->limit,
                offset: $query->offset,
                estimatedTotalHits: $results['estimatedTotalHits'] ?? null,
            );
        } catch (ApiException $e) {
            throw SearchException::searchFailed($query->query, $e->getMessage());
        }
    }

    public function index(string $indexName, array $documents): bool
    {
        try {
            $index = $this->client->index($indexName);
            $task = $index->addDocuments($documents);
            $this->client->waitForTask($task['taskUid']);

            return true;
        } catch (ApiException $e) {
            throw SearchException::indexingFailed($indexName, $e->getMessage());
        }
    }

    public function delete(string $indexName, string $documentId): bool
    {
        try {
            $index = $this->client->index($indexName);
            $task = $index->deleteDocument($documentId);
            $this->client->waitForTask($task['taskUid']);

            return true;
        } catch (ApiException $e) {
            throw SearchException::indexingFailed($indexName, $e->getMessage());
        }
    }

    public function deleteIndex(string $indexName): bool
    {
        try {
            $task = $this->client->deleteIndex($indexName);
            $this->client->waitForTask($task['taskUid']);

            return true;
        } catch (ApiException $e) {
            throw SearchException::indexingFailed($indexName, $e->getMessage());
        }
    }

    public function createIndex(string $indexName, IndexConfiguration $config): bool
    {
        try {
            // Create index
            $task = $this->client->createIndex($indexName, [
                'primaryKey' => $config->primaryKey,
            ]);
            $this->client->waitForTask($task['taskUid']);

            // Update settings
            $this->updateIndexSettings($indexName, $config);

            return true;
        } catch (ApiException $e) {
            throw SearchException::indexCreationFailed($indexName, $e->getMessage());
        }
    }

    public function updateIndexSettings(string $indexName, IndexConfiguration $config): bool
    {
        try {
            $index = $this->client->index($indexName);
            $settings = $config->toMeilisearchSettings();

            $task = $index->updateSettings($settings);
            $this->client->waitForTask($task['taskUid']);

            return true;
        } catch (ApiException $e) {
            throw SearchException::indexingFailed($indexName, $e->getMessage());
        }
    }

    public function getIndexStats(string $indexName): array
    {
        try {
            $index = $this->client->index($indexName);
            $stats = $index->stats();

            return [
                'numberOfDocuments' => $stats['numberOfDocuments'] ?? 0,
                'isIndexing' => $stats['isIndexing'] ?? false,
                'fieldDistribution' => $stats['fieldDistribution'] ?? [],
            ];
        } catch (ApiException) {
            throw SearchException::indexNotFound($indexName);
        }
    }

    public function bulkIndex(string $indexName, array $documents, int $batchSize = 1000): bool
    {
        try {
            if ($batchSize < 1) {
                $batchSize = 1000;
            }

            $index = $this->client->index($indexName);
            $chunks = array_chunk($documents, $batchSize);
            $tasks = [];

            foreach ($chunks as $chunk) {
                $task = $index->addDocuments($chunk);
                $tasks[] = $task['taskUid'];
            }

            // Wait for all tasks to complete
            foreach ($tasks as $taskId) {
                $this->client->waitForTask($taskId);
            }

            return true;
        } catch (ApiException $e) {
            throw SearchException::bulkIndexingFailed(
                $indexName,
                count($documents),
                $e->getMessage(),
            );
        }
    }

    /**
     * Get search suggestions.
     *
     * @return array<int, array{text: string, id: mixed, type: string}>
     */
    public function suggest(string $indexName, string $query, int $limit = 10): array
    {
        try {
            $index = $this->client->index($indexName);
            $results = $index->search($query, [
                'limit' => $limit,
                'attributesToRetrieve' => ['title', 'name', 'id'],
                'attributesToHighlight' => ['title', 'name'],
            ]);

            // Extract suggestions from hits
            $suggestions = [];
            $hits = $results->getHits();

            foreach ($hits as $hit) {
                $text = $hit['title'] ?? $hit['name'] ?? '';

                if (! empty($text)) {
                    $suggestions[] = [
                        'text' => $text,
                        'id' => $hit['id'] ?? null,
                        'type' => $indexName,
                    ];
                }
            }

            return $suggestions;
        } catch (ApiException) {
            return [];
        }
    }

    public function indexExists(string $indexName): bool
    {
        try {
            $this->client->index($indexName)->stats();

            return true;
        } catch (ApiException) {
            return false;
        }
    }

    public function health(): array
    {
        try {
            $health = $this->client->health();

            return [
                'status' => $health['status'] ?? 'unknown',
            ];
        } catch (ApiException $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Search a single index.
     *
     * @return array<string, mixed>
     */
    private function searchSingleIndex(string $indexName, SearchQuery $query): array
    {
        $index = $this->client->index($indexName);

        $searchParams = [
            'limit' => $query->limit,
            'offset' => $query->offset,
        ];

        // Add filters
        $filterString = $query->filters->toMeilisearchFilter();

        if ($filterString !== '') {
            $searchParams['filter'] = $filterString;
        }

        // Add sort
        $sort = $query->sort->toMeilisearchSort();

        if ($sort !== []) {
            $searchParams['sort'] = $sort;
        }

        // Add highlighting
        if ($query->enableHighlighting) {
            $searchParams['attributesToHighlight'] = ['*'];
        }

        // Add facets
        if ($query->enableFacets) {
            $searchParams['facets'] = ['*'];
        }

        return $index->search($query->query, $searchParams)->toArray();
    }

    /**
     * Perform multi-index search.
     *
     * @return array<string, mixed>
     */
    private function performMultiIndexSearch(SearchQuery $query): array
    {
        // Fallback to single search results merged together
        $allResults = [];
        $totalHits = 0;
        $estimatedTotalHits = 0;
        $facetDistribution = [];

        foreach ($query->indexes as $indexName) {
            $singleResult = $this->searchSingleIndex($indexName, $query);

            $allResults = array_merge($allResults, $singleResult['hits'] ?? []);
            $totalHits += $singleResult['totalHits'] ?? 0;
            $estimatedTotalHits += $singleResult['estimatedTotalHits'] ?? 0;

            // Merge facets
            if (isset($singleResult['facetDistribution'])) {
                foreach ($singleResult['facetDistribution'] as $facet => $distribution) {
                    if (! isset($facetDistribution[$facet])) {
                        $facetDistribution[$facet] = [];
                    }

                    foreach ($distribution as $value => $count) {
                        $facetDistribution[$facet][$value] = ($facetDistribution[$facet][$value] ?? 0) + $count;
                    }
                }
            }
        }

        return [
            'hits' => $allResults,
            'totalHits' => $totalHits,
            'estimatedTotalHits' => $estimatedTotalHits,
            'facetDistribution' => $facetDistribution,
        ];
    }
}

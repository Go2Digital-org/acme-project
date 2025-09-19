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
                $indexName = $query->indexes[0] ?? '';
                $results = $this->searchSingleIndex($indexName, $query);
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

    /**
     * @param  array<string, mixed>  $documents
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $documents
     */
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
     */
    /**
     * @return array<int, string>
     */
    public function suggest(string $indexName, string $query, int $limit = 10): array
    {
        try {
            $index = $this->client->index($indexName);

            // Determine attributes based on entity type
            $attributesToRetrieve = $this->getAttributesForIndex($indexName);

            $results = $index->search($query, [
                'limit' => $limit,
                'attributesToRetrieve' => $attributesToRetrieve,
                'attributesToHighlight' => array_slice($attributesToRetrieve, 0, 2), // Highlight first 2 attributes
            ]);

            // Extract suggestions from hits
            $suggestions = [];
            $hits = $results->getHits();

            foreach ($hits as $hit) {
                $text = $this->extractTextFromHit($hit, $indexName);

                if ($text !== '' && $text !== '0') {
                    $suggestions[] = [
                        'text' => $text,
                        'id' => $hit['id'] ?? null,
                        'type' => $this->getEntityTypeFromIndex($indexName),
                    ];
                }
            }

            return array_values(array_column($suggestions, 'text'));
        } catch (ApiException) {
            return [];
        }
    }

    /**
     * Get attributes to retrieve based on index type.
     *
     * @return array<int, string>
     */
    private function getAttributesForIndex(string $indexName): array
    {
        $mapping = [
            'acme_campaigns' => ['title', 'description', 'id'],
            'acme_users' => ['name', 'email', 'id'],
            'acme_organizations' => ['name', 'description', 'id'],
            'acme_donations' => ['amount', 'id', 'campaign_title'],
        ];

        return $mapping[$indexName] ?? ['title', 'name', 'id'];
    }

    /**
     * Extract display text from hit based on entity type.
     */
    /**
     * @param  array<string, mixed>  $hit
     */
    private function extractTextFromHit(array $hit, string $indexName): string
    {
        return match ($indexName) {
            'acme_campaigns' => $hit['title'] ?? '',
            'acme_users' => $hit['name'] ?? $hit['email'] ?? '',
            'acme_organizations' => $hit['name'] ?? '',
            'acme_donations' => ($hit['campaign_title'] ?? '') . ' - ' . ($hit['amount'] ?? ''),
            default => $hit['title'] ?? $hit['name'] ?? '',
        };
    }

    /**
     * Get entity type from index name.
     */
    private function getEntityTypeFromIndex(string $indexName): string
    {
        return match ($indexName) {
            'acme_campaigns' => 'campaign',
            'acme_users' => 'user',
            'acme_organizations' => 'organization',
            'acme_donations' => 'donation',
            default => 'campaign',
        };
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

    /**
     * @return array<string, mixed>
     */
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
     */
    /**
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
     */
    /**
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

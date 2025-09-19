<?php

declare(strict_types=1);

namespace Tests\Feature\Search\Helpers;

use Mockery;
use Mockery\MockInterface;
use Modules\Search\Domain\Model\SearchResult;
use Modules\Search\Domain\Service\SearchCacheInterface;
use Modules\Search\Domain\Service\SearchEngineInterface;

class MockSearchServices
{
    /**
     * Create a mock SearchEngineInterface that returns predefined results.
     */
    public static function createMockSearchEngine(array $searchResults = []): MockInterface
    {
        $mock = Mockery::mock(SearchEngineInterface::class);

        // Configure search method to return mock results
        $mock->shouldReceive('search')
            ->andReturnUsing(function ($query) use ($searchResults) {
                return new SearchResult(
                    hits: $searchResults['hits'] ?? [],
                    totalHits: $searchResults['totalHits'] ?? 0,
                    processingTime: $searchResults['processingTime'] ?? 10.0,
                    facets: $searchResults['facets'] ?? [],
                    query: $query->query,
                    limit: $query->limit,
                    offset: $query->offset,
                );
            });

        // Configure suggest method
        $mock->shouldReceive('suggest')
            ->andReturn($searchResults['suggestions'] ?? []);

        // Configure index methods
        $mock->shouldReceive('index')->andReturn(true);
        $mock->shouldReceive('delete')->andReturn(true);
        $mock->shouldReceive('deleteIndex')->andReturn(true);
        $mock->shouldReceive('createIndex')->andReturn(true);
        $mock->shouldReceive('updateIndexSettings')->andReturn(true);
        $mock->shouldReceive('bulkIndex')->andReturn(true);
        $mock->shouldReceive('indexExists')->andReturn(true);

        // Configure health and stats methods
        $mock->shouldReceive('health')->andReturn(['status' => 'available']);
        $mock->shouldReceive('getIndexStats')->andReturn([
            'numberOfDocuments' => $searchResults['totalHits'] ?? 0,
            'isIndexing' => false,
            'fieldDistribution' => [],
        ]);

        return $mock;
    }

    /**
     * Create a mock SearchCacheInterface that returns null (cache miss).
     */
    public static function createMockSearchCache(bool $returnCachedResults = false): MockInterface
    {
        $mock = Mockery::mock(SearchCacheInterface::class);

        if ($returnCachedResults) {
            // Configure to return cached results
            $mock->shouldReceive('get')->andReturn(null); // Simulate cache miss most of the time
            $mock->shouldReceive('has')->andReturn(false);
        } else {
            // Configure to always miss cache
            $mock->shouldReceive('get')->andReturn(null);
            $mock->shouldReceive('has')->andReturn(false);
        }

        // Configure cache writing methods
        $mock->shouldReceive('put')->andReturn(true);
        $mock->shouldReceive('forget')->andReturn(true);
        $mock->shouldReceive('flush')->andReturn(true);
        $mock->shouldReceive('many')->andReturn([]);
        $mock->shouldReceive('putMany')->andReturn(true);
        $mock->shouldReceive('warmPopularSearches')->andReturnNull();
        $mock->shouldReceive('getStats')->andReturn([
            'hits' => 0,
            'misses' => 0,
            'hit_ratio' => 0.0,
            'size' => 0,
        ]);
        $mock->shouldReceive('invalidateByPattern')->andReturn(0);

        return $mock;
    }

    /**
     * Create search results for campaigns matching a query.
     *
     * @return array<string, mixed>
     */
    public static function createCampaignSearchResults(string $query, array $campaigns = []): array
    {
        $hits = [];
        $totalHits = 0;

        if (! empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                // Only include campaigns that match the query (simple contains check)
                if (empty($query) || str_contains(strtolower($campaign['title']), strtolower($query))) {
                    $hits[] = [
                        'id' => $campaign['id'],
                        'title' => $campaign['title'],
                        'description' => $campaign['description'] ?? 'Test description',
                        'status' => $campaign['status'] ?? 'active',
                        'category' => $campaign['category'] ?? 'general',
                        'organization' => $campaign['organization'] ?? 'Test Organization',
                        'goal_amount' => $campaign['goal_amount'] ?? 10000,
                        'current_amount' => $campaign['current_amount'] ?? 0,
                        '_formatted' => $query ? [
                            'title' => str_ireplace($query, "<mark>{$query}</mark>", $campaign['title']),
                        ] : [],
                    ];
                    $totalHits++;
                }
            }
        }

        return [
            'hits' => $hits,
            'totalHits' => $totalHits,
            'processingTime' => 5.5,
            'facets' => self::generateFacetsFromHits($hits),
            'suggestions' => self::generateSuggestionsFromQuery($query, $hits),
        ];
    }

    /**
     * Generate facets from search hits.
     *
     * @return array<string, array<string, int>>
     */
    private static function generateFacetsFromHits(array $hits): array
    {
        $facets = [
            'category' => [],
            'status' => [],
        ];

        foreach ($hits as $hit) {
            $category = $hit['category'] ?? 'general';
            $status = $hit['status'] ?? 'active';

            $facets['category'][$category] = ($facets['category'][$category] ?? 0) + 1;
            $facets['status'][$status] = ($facets['status'][$status] ?? 0) + 1;
        }

        return $facets;
    }

    /**
     * Generate suggestions based on query and available hits.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function generateSuggestionsFromQuery(string $query, array $hits): array
    {
        if (empty($query) || empty($hits)) {
            return [];
        }

        $suggestions = [];
        foreach (array_slice($hits, 0, 5) as $hit) {
            $suggestions[] = [
                'text' => $hit['title'],
                'id' => $hit['id'],
                'type' => 'campaign',
            ];
        }

        return $suggestions;
    }
}

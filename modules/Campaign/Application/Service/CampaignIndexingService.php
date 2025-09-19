<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Meilisearch\Client as MeilisearchClient;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Jobs\IndexCampaignChunkJob;
use RuntimeException;

class CampaignIndexingService
{
    private readonly string $indexName;

    public function __construct(
        private readonly MeilisearchClient $meilisearch,
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly string $scoutPrefix = ''
    ) {
        $this->indexName = $this->scoutPrefix . 'campaigns';
    }

    /**
     * Get index statistics and configuration
     *
     * @return array{index_name: string, document_count: int, is_indexing: bool, filterable_count: int, sortable_count: int, searchable_count: int, filterable_attributes: array<string>, sortable_attributes: array<string>, searchable_attributes: array<string>}
     */
    /**
     * @return array<string, mixed>
     */
    public function getIndexStatistics(): array
    {
        try {
            $index = $this->meilisearch->index($this->indexName);
            $stats = $index->stats();
            $settings = $index->getSettings();

            return [
                'index_name' => $this->indexName,
                'document_count' => $stats['numberOfDocuments'] ?? 0,
                'is_indexing' => $stats['isIndexing'] ?? false,
                'filterable_count' => count($settings['filterableAttributes'] ?? []),
                'sortable_count' => count($settings['sortableAttributes'] ?? []),
                'searchable_count' => count($settings['searchableAttributes'] ?? []),
                'filterable_attributes' => $settings['filterableAttributes'] ?? [],
                'sortable_attributes' => $settings['sortableAttributes'] ?? [],
                'searchable_attributes' => $settings['searchableAttributes'] ?? [],
            ];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get index statistics: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Reindex campaigns asynchronously using queue
     *
     * @return array{queued: int, chunks: int, chunk_size: int}
     */
    public function reindexAsync(int $chunkSize = 500): array
    {
        $total = Campaign::count();
        $chunks = (int) ceil($total / $chunkSize);
        $queued = 0;

        Campaign::query()
            ->select('id')
            ->chunk($chunkSize, function ($campaignIds) use (&$queued): void {
                $ids = $campaignIds->pluck('id')->toArray();

                // Dispatch job for this chunk
                IndexCampaignChunkJob::dispatch($ids)
                    ->onQueue('scout');

                $queued += count($ids);
            });

        return [
            'queued' => $queued,
            'chunks' => $chunks,
            'chunk_size' => $chunkSize,
        ];
    }

    /**
     * Reindex campaigns synchronously with progress callback
     *
     * @param  callable(int): void|null  $progressCallback
     */
    public function reindexSync(int $chunkSize = 500, ?callable $progressCallback = null): int
    {
        $indexed = 0;

        Campaign::with(['organization', 'creator', 'categoryModel'])
            ->chunk($chunkSize, function ($campaigns) use (&$indexed, $progressCallback): void {
                foreach ($campaigns as $campaign) {
                    if ($campaign->shouldBeSearchable()) {
                        $campaign->searchable();
                        $indexed++;
                    }
                }

                if ($progressCallback !== null) {
                    $progressCallback($campaigns->count());
                }
            });

        return $indexed;
    }

    /**
     * Clear the entire campaign index
     */
    public function clearIndex(): void
    {
        try {
            // Use Scout to flush the index
            Artisan::call('scout:flush', [
                'model' => Campaign::class,
            ]);
        } catch (Exception) {
            // If Scout fails, try direct Meilisearch API
            $this->meilisearch->index($this->indexName)->deleteAllDocuments();
        }
    }

    /**
     * Configure index settings (filterable, sortable, searchable attributes)
     */
    public function configureIndex(): void
    {
        $index = $this->meilisearch->index($this->indexName);

        // Define index settings based on Campaign model requirements
        $settings = [
            'searchableAttributes' => [
                'title',
                'description',
                'organization_name',
                'category_name',
                'tags',
            ],
            'filterableAttributes' => [
                'id',
                'status',
                'category_id',
                'organization_id',
                'user_id',
                'is_featured',
                'created_at',
                'start_date',
                'end_date',
                'goal_percentage',
                'current_amount',
                'goal_amount',
            ],
            'sortableAttributes' => [
                'created_at',
                'updated_at',
                'start_date',
                'end_date',
                'current_amount',
                'goal_amount',
                'goal_percentage',
                'donations_count',
                'title',
            ],
            'displayedAttributes' => [
                '*', // Display all attributes
            ],
            'stopWords' => [
                'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            ],
            'synonyms' => [
                'donate' => ['contribute', 'give', 'support'],
                'campaign' => ['fundraiser', 'project', 'initiative'],
                'help' => ['assist', 'aid', 'support'],
            ],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
                'is_featured:desc',
                'goal_percentage:desc',
            ],
        ];

        // Update index settings
        $index->updateSettings($settings);
    }

    /**
     * Index a single campaign
     */
    public function indexCampaign(Campaign $campaign): void
    {
        if ($campaign->shouldBeSearchable()) {
            $campaign->searchable();
        }
    }

    /**
     * Remove a campaign from the index
     */
    public function removeCampaign(Campaign $campaign): void
    {
        $campaign->unsearchable();
    }

    /**
     * Batch index multiple campaigns
     */
    /**
     * @param  array<int>  $campaignIds
     */
    public function indexCampaigns(array $campaignIds): int
    {
        $indexed = 0;

        $campaigns = Campaign::with(['organization', 'creator', 'categoryModel'])
            ->whereIn('id', $campaignIds)
            ->get();

        foreach ($campaigns as $campaign) {
            if ($campaign->shouldBeSearchable()) {
                $campaign->searchable();
                $indexed++;
            }
        }

        return $indexed;
    }

    /**
     * Get campaigns that should be indexed but aren't
     */
    /**
     * @return array<int>
     */
    public function getMissingCampaigns(int $limit = 100): array
    {
        try {
            // Get all indexed IDs
            $index = $this->meilisearch->index($this->indexName);
            $indexedIds = [];

            $results = $index->search('', [
                'limit' => 1000000,
                'attributesToRetrieve' => ['id'],
            ]);

            $hits = $results->getHits();
            foreach ($hits as $hit) {
                $indexedIds[] = $hit['id'];
            }

            // Find campaigns not in the index
            return Campaign::whereNotIn('id', $indexedIds)
                ->where('status', 'active')
                ->limit($limit)
                ->pluck('id')
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Validate index health
     *
     * @return array{healthy: bool, issues: array<string>, stats: array{index_name: string, document_count: int, is_indexing: bool, filterable_count: int, sortable_count: int, searchable_count: int, filterable_attributes: array<string>, sortable_attributes: array<string>, searchable_attributes: array<string>}}
     */
    /**
     * @return array<string, mixed>
     */
    public function validateIndexHealth(): array
    {
        $stats = $this->getIndexStatistics();
        $dbCount = $this->campaignRepository->count();
        $issues = [];

        // Check document count
        if ($stats['document_count'] !== $dbCount) {
            $diff = abs($dbCount - $stats['document_count']);
            $issues[] = "Document count mismatch: {$diff} difference";
        }

        // Check required attributes
        $requiredFilterable = ['status', 'category_id', 'organization_id'];
        $missingFilterable = array_diff($requiredFilterable, $stats['filterable_attributes']);
        if ($missingFilterable !== []) {
            $issues[] = 'Missing filterable attributes: ' . implode(', ', $missingFilterable);
        }

        $requiredSortable = ['created_at', 'current_amount', 'goal_percentage'];
        $missingSortable = array_diff($requiredSortable, $stats['sortable_attributes']);
        if ($missingSortable !== []) {
            $issues[] = 'Missing sortable attributes: ' . implode(', ', $missingSortable);
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'stats' => $stats,
        ];
    }
}

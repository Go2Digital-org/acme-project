<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Service;

use Meilisearch\Client;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Service\CampaignIndexerInterface;

final readonly class MeilisearchCampaignIndexer implements CampaignIndexerInterface
{
    private Client $client;

    private string $indexName;

    public function __construct()
    {
        $this->client = app(Client::class);
        $this->indexName = config('scout.prefix') . 'campaigns';
    }

    public function index(Campaign $campaign): void
    {
        $index = $this->client->index($this->indexName);

        $document = $this->transformToDocument($campaign);
        $index->addDocuments([$document]);
    }

    /**
     * @param  array<string, mixed>  $campaigns
     */
    public function indexBatch(array $campaigns): void
    {
        if ($campaigns === []) {
            return;
        }

        $index = $this->client->index($this->indexName);
        $documents = array_map([$this, 'transformToDocument'], $campaigns);
        $index->addDocuments($documents);
    }

    public function remove(Campaign $campaign): void
    {
        $index = $this->client->index($this->indexName);
        $index->deleteDocument($campaign->id);
    }

    public function flush(): void
    {
        $index = $this->client->index($this->indexName);
        $index->deleteAllDocuments();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformToDocument(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'uuid' => $campaign->uuid,
            'title' => $campaign->getTitle(),
            'description' => $campaign->getDescription(),
            'slug' => $campaign->slug,
            'status' => $campaign->status->value,
            'category_id' => $campaign->category_id,
            'organization_id' => $campaign->organization_id,
            'created_at' => $campaign->created_at?->toIso8601String(),
            'start_date' => $campaign->start_date?->toIso8601String(),
            'end_date' => $campaign->end_date?->toIso8601String(),
            'goal_amount' => $campaign->goal_amount,
            'current_amount' => $campaign->current_amount,
            'donations_count' => $campaign->donations_count,
            'is_featured' => $campaign->is_featured,
        ];
    }
}

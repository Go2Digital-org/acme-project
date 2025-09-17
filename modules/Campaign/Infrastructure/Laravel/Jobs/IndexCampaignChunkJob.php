<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Modules\Campaign\Application\Service\CampaignIndexingService;
use Throwable;

class IndexCampaignChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out
     */
    public int $timeout = 300;

    /**
     * Create a new job instance
     *
     * @param  array<int, int>  $campaignIds
     */
    public function __construct(
        private readonly array $campaignIds
    ) {}

    /**
     * Execute the job
     */
    public function handle(CampaignIndexingService $indexingService): void
    {
        $indexingService->indexCampaigns($this->campaignIds);
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to index campaign chunk', [
            'campaign_ids' => $this->campaignIds,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['campaign-indexing', 'meilisearch'];
    }
}

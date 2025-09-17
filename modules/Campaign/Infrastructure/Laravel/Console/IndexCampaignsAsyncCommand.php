<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Infrastructure\Laravel\Job\IndexCampaignBatchJob;

final class IndexCampaignsAsyncCommand extends Command
{
    protected $signature = 'campaigns:index-async {--chunk=1000 : Number of campaigns per batch}';

    protected $description = 'Index all campaigns to Meilisearch asynchronously using queued jobs';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        // Get total campaigns count
        $totalCampaigns = DB::table('campaigns')->count();
        $totalBatches = (int) ceil($totalCampaigns / $chunkSize);

        $this->info("Queueing {$totalCampaigns} campaigns in {$totalBatches} batches of {$chunkSize}...");

        $bar = $this->output->createProgressBar($totalBatches);
        $bar->start();

        // Queue jobs for each batch
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $offset = $batch * $chunkSize;

            IndexCampaignBatchJob::dispatch($offset, $chunkSize)
                ->onQueue('indexing');

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✓ Queued {$totalBatches} indexing jobs");
        $this->info("Run 'php artisan queue:work --queue=indexing' to process them");

        return self::SUCCESS;
    }
}

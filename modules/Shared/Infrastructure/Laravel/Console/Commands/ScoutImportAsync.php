<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Shared\Infrastructure\Laravel\Jobs\ScoutBatchIndexJob;

class ScoutImportAsync extends Command
{
    protected $signature = 'scout:import-async
                            {model : The model class to index}
                            {--chunk=1000 : Number of records per job}
                            {--batch=5000 : Number of records per batch job}
                            {--start-id=1 : Start indexing from this ID}
                            {--end-id= : End indexing at this ID (optional)}
                            {--dry-run : Preview what will be queued without actually queuing}
                            {--fresh : Clear the index before importing}';

    protected $description = 'Import models to search index asynchronously using optimized batch jobs';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $chunkSize = (int) $this->option('chunk');
        $batchSize = (int) $this->option('batch');
        $startId = (int) $this->option('start-id');
        $endId = $this->option('end-id') ? (int) $this->option('end-id') : null;
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return 1;
        }

        $model = new $modelClass;

        if (! method_exists($model, 'searchable')) {
            $this->error("Model class {$modelClass} is not searchable.");

            return 1;
        }

        if (! method_exists($model, 'getTable')) {
            $this->error("Model class {$modelClass} does not have getTable method.");

            return 1;
        }

        // Get table name and count
        $table = $model->getTable();

        // Get min and max IDs
        $query = DB::table($table);
        if ($endId === null) {
            $endId = (int) $query->max('id');
        }

        // Count records in range
        $total = $query->whereBetween('id', [$startId, $endId])->count();

        if ($total === 0) {
            $this->warn("No records found in range {$startId} to {$endId}");

            return 0;
        }

        // Calculate number of batch jobs needed
        $numJobs = (int) ceil(($endId - $startId + 1) / $batchSize);

        $this->info("Model: {$modelClass}");
        $this->info("Total records in range: {$total}");
        $this->info("ID range: {$startId} to {$endId}");
        $this->info("Batch size: {$batchSize} records per job");
        $this->info("Chunk size: {$chunkSize} records per chunk within each job");
        $this->info("Number of jobs to queue: {$numJobs}");

        if ($dryRun) {
            $this->warn('DRY RUN - No jobs will be queued');
            $this->table(
                ['Job #', 'Start ID', 'End ID', 'Records'],
                $this->getJobPreview($startId, $endId, $batchSize, $numJobs, $table)
            );

            return 0;
        }

        if ($fresh) {
            $this->info('Clearing existing index...');
            try {
                // This might fail with memory issues for large indexes
                // In that case, manually delete from Meilisearch
                $modelClass::removeAllFromSearch();
                $this->info('Index cleared successfully.');
            } catch (Exception $e) {
                $this->warn('Could not clear index: ' . $e->getMessage());
                $this->warn('You may need to manually delete the index from Meilisearch.');
            }
        }

        // Check current queue size
        $queueSizeBefore = (int) Redis::connection()->llen('queues:scout');
        $this->info("Current scout queue size: {$queueSizeBefore}");

        $bar = $this->output->createProgressBar($numJobs);
        $bar->start();

        // Queue batch jobs
        $jobsQueued = 0;
        $currentId = $startId;

        while ($currentId <= $endId) {
            $batchEndId = min($currentId + $batchSize - 1, $endId);

            ScoutBatchIndexJob::dispatch(
                $modelClass,
                $currentId,
                $batchEndId,
                $chunkSize
            )->onQueue('scout');

            $jobsQueued++;
            $bar->advance();

            $currentId = $batchEndId + 1;
        }

        $bar->finish();
        $this->newLine();

        // Check queue size after
        $queueSizeAfter = (int) Redis::connection()->llen('queues:scout');
        $jobsAdded = $queueSizeAfter - $queueSizeBefore;

        $this->info("✓ Queued {$jobsQueued} indexing jobs");
        $this->info("Scout queue size increased by: {$jobsAdded}");
        $this->info("Total scout queue size: {$queueSizeAfter}");

        if ($queueSizeAfter > 0) {
            $this->info('');
            $this->info('Queue workers are already running. Jobs will be processed automatically.');
            $this->info('Monitor progress with: php artisan scout:index-monitor ' . $modelClass);
        }

        return 0;
    }

    /**
     * Get a preview of jobs that will be created.
     *
     * @return list<list<int|string>>
     */
    private function getJobPreview(int $startId, int $endId, int $batchSize, int $numJobs, string $table): array
    {
        $preview = [];
        $currentId = $startId;
        $jobNum = 1;

        while ($currentId <= $endId && $jobNum <= min(5, $numJobs)) {
            $batchEndId = min($currentId + $batchSize - 1, $endId);
            $count = DB::table($table)
                ->whereBetween('id', [$currentId, $batchEndId])
                ->count();

            $preview[] = [
                $jobNum,
                $currentId,
                $batchEndId,
                $count,
            ];

            $currentId = $batchEndId + 1;
            $jobNum++;
        }

        if ($numJobs > 5) {
            $preview[] = ['...', '...', '...', '...'];
        }

        return $preview;
    }
}

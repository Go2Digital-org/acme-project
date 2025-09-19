<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class ScoutIndexMonitor extends Command
{
    protected $signature = 'scout:index-monitor
                            {model : The model class to monitor}
                            {--watch : Continuously monitor the indexing progress}
                            {--interval=5 : Refresh interval in seconds when watching}';

    protected $description = 'Monitor Scout indexing progress and status';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $watch = (bool) $this->option('watch');
        $interval = (int) $this->option('interval');

        if (! is_string($modelClass)) {
            $this->error('Model class must be a string.');

            return 1;
        }

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return 1;
        }

        $model = new $modelClass;

        if (! method_exists($model, 'getTable')) {
            $this->error("Model class {$modelClass} does not have getTable method.");

            return 1;
        }

        $table = $model->getTable();

        // Get index name from Scout
        $indexName = config('scout.prefix') . $table;

        do {
            $this->displayStatus($modelClass, $table, $indexName);

            if ($watch) {
                sleep($interval);
                // Clear screen for watch mode
                if (! $this->output->isQuiet()) {
                    $this->output->write("\033[2J\033[;H");
                }
            }
        } while ($watch);

        return 0;
    }

    private function displayStatus(string $modelClass, string $table, string $indexName): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('Scout Index Monitor - ' . now()->format('Y-m-d H:i:s'));
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Database statistics
        $this->info('📊 Database Statistics');
        $this->line('───────────────────────');

        $totalRecords = DB::table($table)->count();
        $this->line('Total records: ' . number_format($totalRecords));

        // Check if model has status-based indexing
        if (method_exists($modelClass, 'shouldBeSearchable')) {
            // For campaigns, show indexable vs non-indexable
            $activeCount = 0;
            $completedCount = 0;

            try {
                if ($table === 'campaigns') {
                    // Special handling for campaigns
                    $activeCount = DB::table($table)
                        ->where('status', 'active')
                        ->count();
                    $completedCount = DB::table($table)
                        ->where('status', 'completed')
                        ->count();
                    $indexableCount = $activeCount + $completedCount;

                    $this->line('Active campaigns: ' . number_format($activeCount));
                    $this->line('Completed campaigns: ' . number_format($completedCount));
                    $this->line('Total indexable: ' . number_format($indexableCount));
                    $this->line('Non-indexable: ' . number_format($totalRecords - $indexableCount));
                }
            } catch (Exception) {
                $this->line('Unable to determine indexable count');
            }
        }

        $this->newLine();

        // Meilisearch statistics
        $this->info('🔍 Meilisearch Index Statistics');
        $this->line('─────────────────────────────────');

        try {
            $meilisearchHost = config('scout.meilisearch.host');
            $meilisearchKey = config('scout.meilisearch.key');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $meilisearchKey,
            ])->get("{$meilisearchHost}/indexes/{$indexName}/stats");

            if ($response->successful()) {
                $stats = $response->json();
                $indexedCount = $stats['numberOfDocuments'] ?? 0;

                $this->line('Indexed documents: ' . number_format($indexedCount));

                if (isset($indexableCount)) {
                    $missingCount = max(0, $indexableCount - $indexedCount);
                    $percentage = $indexableCount > 0
                        ? round(($indexedCount / $indexableCount) * 100, 2)
                        : 0;

                    $this->line('Missing documents: ' . number_format($missingCount));
                    $this->line("Index coverage: {$percentage}%");

                    // Progress bar
                    if ($indexableCount > 0) {
                        $this->newLine();
                        $bar = $this->output->createProgressBar($indexableCount);
                        $bar->setProgress($indexedCount);
                        $bar->display();
                        $this->newLine(2);
                    }
                }

                $this->line('Index size: ' . $this->formatBytes($stats['rawDocumentDbSize'] ?? 0));
                $this->line('Is indexing: ' . ($stats['isIndexing'] ? 'Yes' : 'No'));
            } else {
                $this->warn("Unable to connect to Meilisearch index: {$indexName}");
                $this->line('Response: ' . $response->body());
            }
        } catch (Exception $e) {
            $this->error('Error fetching Meilisearch stats: ' . $e->getMessage());
        }

        $this->newLine();

        // Queue statistics
        $this->info('📦 Queue Statistics');
        $this->line('───────────────────');

        try {
            $queueSize = (int) Redis::connection()->llen('queues:scout');
            $failedJobs = DB::table('failed_jobs')
                ->where('queue', 'scout')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            $this->line('Scout queue size: ' . number_format($queueSize));
            $this->line('Failed jobs (24h): ' . number_format($failedJobs));

            // Check if workers are running
            $workers = $this->getRunningWorkers();
            $scoutWorkers = array_filter($workers, fn ($worker): bool => str_contains($worker, '--queue=scout') || str_contains($worker, 'scout-indexer'));

            $this->line('Active scout workers: ' . count($scoutWorkers));

            if ($queueSize > 0 && count($scoutWorkers) === 0) {
                $this->warn('⚠️  Jobs are queued but no scout workers are running!');
            }
        } catch (Exception $e) {
            $this->warn('Unable to fetch queue statistics: ' . $e->getMessage());
        }

        $this->newLine();

        // Recent activity
        if ($queueSize > 0 || (isset($stats['isIndexing']) && $stats['isIndexing'])) {
            $this->info('⚡ Status: INDEXING IN PROGRESS');
        } elseif (isset($missingCount) && $missingCount > 0) {
            $this->warn('⚠️  Status: INCOMPLETE - ' . number_format($missingCount) . ' documents missing');
        } else {
            $this->info('✅ Status: UP TO DATE');
        }

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════');
    }

    /**
     * Get list of running queue workers.
     */
    /**
     * @return list<string>
     */
    private function getRunningWorkers(): array
    {
        $workers = [];

        try {
            // Try to get workers from ps command
            exec("ps aux | grep 'artisan queue:work' | grep -v grep", $output);
            $workers = $output;
        } catch (Exception) {
            // Silently fail
        }

        return $workers;
    }

    /**
     * Format bytes to human-readable size.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

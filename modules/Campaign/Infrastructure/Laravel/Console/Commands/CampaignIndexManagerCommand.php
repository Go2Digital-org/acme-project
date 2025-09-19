<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Modules\Campaign\Application\Service\CampaignIndexingService;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;

class CampaignIndexManagerCommand extends Command
{
    protected $signature = 'hex:campaign:index
                            {action : Action to perform (status|reindex|clear|configure)}
                            {--fresh : Clear before reindexing}
                            {--async : Use queue for reindexing}
                            {--chunk=500 : Number of records per chunk}';

    protected $description = 'Manage campaign search indexing (Meilisearch)';

    public function __construct(
        private readonly CampaignIndexingService $indexingService,
        private readonly CampaignRepositoryInterface $campaignRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus(),
            'reindex' => $this->reindex(),
            'clear' => $this->clearIndex(),
            'configure' => $this->configureIndex(),
            default => $this->handleInvalidAction(),
        };
    }

    private function showStatus(): int
    {
        $this->info('📊 Campaign Index Status');
        $this->line('------------------------');

        try {
            $stats = $this->indexingService->getIndexStatistics();
            $dbCount = $this->campaignRepository->count();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Index Name', $stats['index_name']],
                    ['Documents Indexed', number_format($stats['document_count'])],
                    ['Is Indexing', $stats['is_indexing'] ? 'Yes' : 'No'],
                    ['Database Campaigns', number_format($dbCount)],
                    ['Sync Status', $stats['document_count'] === $dbCount ? '✅ Synced' : '⚠️ Out of sync'],
                ]
            );

            $this->newLine();
            $this->info('🔧 Index Configuration:');
            $this->line("Filterable Attributes: {$stats['filterable_count']}");
            $this->line("Sortable Attributes: {$stats['sortable_count']}");
            $this->line("Searchable Attributes: {$stats['searchable_count']}");

            if ($stats['document_count'] !== $dbCount) {
                $diff = abs($dbCount - $stats['document_count']);
                $this->warn("⚠️  Index is out of sync by {$diff} documents");
                $this->line("Run 'php artisan hex:campaign:index reindex' to sync");
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to get index status: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function reindex(): int
    {
        $fresh = $this->option('fresh');
        $async = $this->option('async');
        $chunkSize = (int) $this->option('chunk');

        if ($fresh) {
            $this->warn('🗑️  Clearing existing index...');
            $this->clearIndex();
            sleep(1); // Give Meilisearch time to process
        }

        $this->info('🔄 Starting campaign reindexing...');

        try {
            if ($async) {
                $result = $this->indexingService->reindexAsync($chunkSize);
                $this->info("✅ Queued {$result['queued']} campaigns for indexing");
                $this->info("📝 Processing in {$result['chunks']} chunks");
                $this->newLine();
                $this->info('Run the following to process:');
                $this->line('php artisan queue:work --queue=scout');
            } else {
                $total = $this->campaignRepository->count();
                $bar = $this->output->createProgressBar($total);
                $bar->start();

                $indexed = $this->indexingService->reindexSync($chunkSize, function ($processed) use ($bar): void {
                    $bar->advance($processed);
                });

                $bar->finish();
                $this->newLine(2);
                $this->info("✅ Successfully indexed {$indexed} campaigns");
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Reindexing failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function clearIndex(): int
    {
        $this->warn('🗑️  Clearing campaign index...');

        if (! $this->confirm('Are you sure you want to clear the entire campaign index?')) {
            $this->info('Operation cancelled');

            return Command::SUCCESS;
        }

        try {
            $this->indexingService->clearIndex();
            $this->info('✅ Index cleared successfully');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to clear index: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function configureIndex(): int
    {
        $this->info('⚙️  Configuring campaign index settings...');

        try {
            $this->indexingService->configureIndex();

            $this->info('✅ Index configuration updated');
            $this->newLine();

            // Show the new configuration
            $stats = $this->indexingService->getIndexStatistics();
            $this->info('New configuration:');
            $this->line("- Filterable: {$stats['filterable_count']} attributes");
            $this->line("- Sortable: {$stats['sortable_count']} attributes");
            $this->line("- Searchable: {$stats['searchable_count']} attributes");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to configure index: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    private function handleInvalidAction(): int
    {
        $this->error('Invalid action specified');
        $this->newLine();
        $this->info('Available actions:');
        $this->line('  status    - Show index status and statistics');
        $this->line('  reindex   - Reindex all campaigns');
        $this->line('  clear     - Clear the entire index');
        $this->line('  configure - Update index configuration');
        $this->newLine();
        $this->info('Examples:');
        $this->line('  php artisan hex:campaign:index status');
        $this->line('  php artisan hex:campaign:index reindex --fresh --async');
        $this->line('  php artisan hex:campaign:index configure');

        return Command::FAILURE;
    }
}

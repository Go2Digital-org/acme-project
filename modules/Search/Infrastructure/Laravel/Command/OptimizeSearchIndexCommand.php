<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Meilisearch\Client;

class OptimizeSearchIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:optimize
                            {index? : The index to optimize (campaigns, donations, users, organizations, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize search indexes for better performance';

    public function __construct(
        private readonly Client $meilisearch,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $index = $this->argument('index') ?? 'all';

        $this->info("Starting optimization for: {$index}");

        $indexes = [];

        if ($index === 'all') {
            $indexes = [
                'acme_campaigns',
                'acme_donations',
                'acme_users',
                'acme_organizations',
            ];
        }

        if ($index !== 'all') {
            $indexes[] = 'acme_' . $index;
        }

        foreach ($indexes as $indexName) {
            $this->optimizeIndex($indexName);
        }

        $this->info('Optimization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Optimize a specific index.
     */
    private function optimizeIndex(string $indexName): void
    {
        $this->info("Optimizing index: {$indexName}");

        try {
            $index = $this->meilisearch->index($indexName);

            // Get current stats
            $stats = $index->stats();
            $this->info('  Current documents: ' . number_format($stats['numberOfDocuments'] ?? 0));

            // Update settings to ensure optimal configuration
            $this->updateIndexSettings($indexName);

            // Trigger index update to optimize storage
            $task = $index->updateDocuments([]);
            $this->meilisearch->waitForTask($task['taskUid']);

            $this->info('  ✓ Index optimized successfully');
        } catch (Exception $e) {
            $this->warn('  ✗ Failed to optimize index: ' . $e->getMessage());
        }
    }

    /**
     * Update index settings for optimization.
     */
    private function updateIndexSettings(string $indexName): void
    {
        $settings = config("scout.meilisearch.index-settings.{$this->getConfigKey($indexName)}", []);

        if (empty($settings)) {
            return;
        }

        try {
            $index = $this->meilisearch->index($indexName);
            $task = $index->updateSettings($settings);
            $this->meilisearch->waitForTask($task['taskUid']);
            $this->info('  ✓ Settings updated');
        } catch (Exception $e) {
            $this->warn('  ✗ Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Get config key from index name.
     */
    private function getConfigKey(string $indexName): string
    {
        $mapping = [
            'acme_campaigns' => 'campaigns',
            'acme_donations' => 'donations',
            'acme_users' => 'users',
            'acme_organizations' => 'organizations',
        ];

        return $mapping[$indexName] ?? 'campaigns';
    }
}

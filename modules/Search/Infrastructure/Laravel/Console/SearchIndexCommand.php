<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Meilisearch\Client;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use RuntimeException;

class SearchIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:index 
                            {action : The action to perform (status|rebuild|clear|configure)}
                            {--model= : Specific model to target}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Meilisearch indexes for the application';

    /**
     * Models that are searchable in the application.
     *
     * @var array<class-string>
     */
    private array $searchableModels = [
        Campaign::class,
        Donation::class,
        Organization::class,
        User::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $modelClass = $this->option('model');

        // Ensure we have proper class-string types
        if (! $modelClass) {
            $models = $this->searchableModels;
        }

        if ($modelClass) {
            if (! is_string($modelClass) || ! in_array($modelClass, $this->searchableModels, true)) {
                $modelClassString = is_string($modelClass) ? $modelClass : gettype($modelClass);
                $this->error("Invalid model: {$modelClassString}");

                return Command::FAILURE;
            }
            $models = [$modelClass];
        }

        $actionString = match (true) {
            is_string($action) => $action,
            is_bool($action) => $action ? 'true' : 'false',
            is_array($action) => 'array',
            is_null($action) => 'null',
            default => 'unknown'
        };

        return match ($actionString) {
            'status' => $this->showStatus($models),
            'rebuild' => $this->rebuildIndexes($models),
            'clear' => $this->clearIndexes($models),
            'configure' => $this->configureIndexes($models),
            default => $this->getInvalidActionResult($actionString),
        };
    }

    /**
     * Show the status of search indexes.
     *
     * @param  array<class-string>  $models
     */
    private function showStatus(array $models): int
    {
        $this->info('Search Index Status');
        $this->info('==================');

        try {
            $client = new Client(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );

            foreach ($models as $modelClass) {
                if (! class_exists($modelClass)) {
                    $this->warn("Model {$modelClass} does not exist");

                    continue;
                }

                /** @var Model $model */
                $model = new $modelClass;

                if (! method_exists($model, 'searchableAs')) {
                    $this->warn("Model {$modelClass} is not searchable");

                    continue;
                }

                $indexName = $model->searchableAs();

                try {
                    $index = $client->index($indexName);
                    $stats = $index->stats();

                    $this->info("\n📊 {$modelClass}");
                    $this->info("   Index: {$indexName}");
                    $this->info('   Documents: ' . ($stats['numberOfDocuments'] ?? 0));
                    $this->info('   Is Indexing: ' . ($stats['isIndexing'] ? 'Yes' : 'No'));

                    // Get settings
                    $settings = $index->getSettings();
                    $this->info('   Searchable Attributes: ' . count($settings['searchableAttributes'] ?? []));
                    $this->info('   Filterable Attributes: ' . count($settings['filterableAttributes'] ?? []));
                    $this->info('   Sortable Attributes: ' . count($settings['sortableAttributes'] ?? []));

                } catch (Exception $e) {
                    $this->error('   ❌ Index not found or error: ' . $e->getMessage());
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to connect to Meilisearch: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Rebuild search indexes.
     *
     * @param  array<class-string>  $models
     */
    private function rebuildIndexes(array $models): int
    {
        if (! $this->option('force') && ! $this->confirm('This will rebuild all search indexes. Continue?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Rebuilding search indexes...');

        foreach ($models as $modelClass) {
            if (! class_exists($modelClass)) {
                $this->warn("Model {$modelClass} does not exist");

                continue;
            }

            $modelName = class_basename($modelClass);
            $this->info("\nRebuilding {$modelName} index...");

            try {
                // First, configure the index
                $this->configureModelIndex($modelClass);

                // Import the data
                $this->call('scout:import', [
                    'model' => $modelClass,
                ]);

                $this->info("✅ {$modelName} index rebuilt successfully");
            } catch (Exception $e) {
                $this->error("❌ Failed to rebuild {$modelName} index: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Clear search indexes.
     *
     * @param  array<class-string>  $models
     */
    private function clearIndexes(array $models): int
    {
        if (! $this->option('force') && ! $this->confirm('This will clear all data from search indexes. Continue?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Clearing search indexes...');

        foreach ($models as $modelClass) {
            if (! class_exists($modelClass)) {
                $this->warn("Model {$modelClass} does not exist");

                continue;
            }

            $modelName = class_basename($modelClass);
            $this->info("\nClearing {$modelName} index...");

            try {
                $this->call('scout:flush', [
                    'model' => $modelClass,
                ]);

                $this->info("✅ {$modelName} index cleared successfully");
            } catch (Exception $e) {
                $this->error("❌ Failed to clear {$modelName} index: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Configure search indexes with proper settings.
     *
     * @param  array<class-string>  $models
     */
    private function configureIndexes(array $models): int
    {
        $this->info('Configuring search indexes...');

        foreach ($models as $modelClass) {
            if (! class_exists($modelClass)) {
                $this->warn("Model {$modelClass} does not exist");

                continue;
            }

            try {
                $this->configureModelIndex($modelClass);
                $modelName = class_basename($modelClass);
                $this->info("✅ {$modelName} index configured successfully");
            } catch (Exception $e) {
                $modelName = class_basename($modelClass);
                $this->error("❌ Failed to configure {$modelName} index: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Configure a specific model's index.
     *
     * @param  class-string  $modelClass
     */
    private function configureModelIndex(string $modelClass): void
    {
        /** @var Model $model */
        $model = new $modelClass;

        if (! method_exists($model, 'searchableAs')) {
            throw new RuntimeException("Model {$modelClass} is not searchable");
        }

        $indexName = $model->searchableAs();
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $index = $client->index($indexName);

        // Get settings from config
        $configKey = str_replace('acme_', '', $indexName);
        $settings = config("scout.meilisearch.index-settings.{$configKey}", []);

        if (! empty($settings)) {
            $this->info("   Applying settings for {$indexName}...");

            // Apply settings
            if (isset($settings['filterableAttributes'])) {
                $index->updateFilterableAttributes($settings['filterableAttributes']);
            }

            if (isset($settings['sortableAttributes'])) {
                $index->updateSortableAttributes($settings['sortableAttributes']);
            }

            if (isset($settings['searchableAttributes'])) {
                $index->updateSearchableAttributes($settings['searchableAttributes']);
            }

            if (isset($settings['displayedAttributes'])) {
                $index->updateDisplayedAttributes($settings['displayedAttributes']);
            }

            if (isset($settings['rankingRules'])) {
                $index->updateRankingRules($settings['rankingRules']);
            }
        }
    }

    /**
     * Handle invalid action and return failure code.
     */
    private function getInvalidActionResult(string $action): int
    {
        $this->error("Invalid action: {$action}");

        return Command::FAILURE;
    }
}

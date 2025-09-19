<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Commands;

use Exception;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Modules\Category\Application\Service\CategorySearchService;
use Modules\Donation\Application\Service\DonationSearchService;
use Modules\Organization\Application\Service\OrganizationSearchService;
use Modules\Shared\Application\Service\PageSearchService;
use Modules\User\Application\Service\UserSearchService;

/**
 * Console command to reindex all searchable models.
 */
final class ReindexSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'search:reindex 
                          {--model= : Specific model to reindex (organizations,users,donations,categories,pages)}
                          {--clear-cache : Clear search cache after reindexing}
                          {--force : Force reindexing without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reindex all searchable models for Meilisearch';

    /**
     * Execute the console command.
     */
    public function handle(
        OrganizationSearchService $organizationSearchService,
        UserSearchService $userSearchService,
        DonationSearchService $donationSearchService,
        CategorySearchService $categorySearchService,
        PageSearchService $pageSearchService
    ): int {
        $model = $this->option('model');
        $clearCache = $this->option('clear-cache');
        $force = $this->option('force');

        // Validate model option
        if ($model && ! in_array($model, ['organizations', 'users', 'donations', 'categories', 'pages'], true)) {
            $this->error('Invalid model specified. Available models: organizations, users, donations, categories, pages');

            return 1;
        }

        // Confirmation check
        if (! $force) {
            $message = $model
                ? "Are you sure you want to reindex the '{$model}' model?"
                : 'Are you sure you want to reindex all searchable models?';

            if (! $this->confirm($message)) {
                $this->info('Reindexing cancelled.');

                return 0;
            }
        }

        $this->info('Starting search reindexing...');
        $startTime = microtime(true);

        try {
            if ($model) {
                $this->reindexModel($model, [
                    'organizations' => $organizationSearchService,
                    'users' => $userSearchService,
                    'donations' => $donationSearchService,
                    'categories' => $categorySearchService,
                    'pages' => $pageSearchService,
                ]);
            } else {
                $this->reindexAllModels([
                    'organizations' => $organizationSearchService,
                    'users' => $userSearchService,
                    'donations' => $donationSearchService,
                    'categories' => $categorySearchService,
                    'pages' => $pageSearchService,
                ]);
            }

            if ($clearCache) {
                $this->clearSearchCaches([
                    'organizations' => $organizationSearchService,
                    'users' => $userSearchService,
                    'donations' => $donationSearchService,
                    'categories' => $categorySearchService,
                    'pages' => $pageSearchService,
                ]);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Search reindexing completed successfully in {$duration} seconds!");

            return 0;
        } catch (Exception $e) {
            $this->error('Reindexing failed: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Reindex a specific model.
     */
    /**
     * @param  array<string, mixed>  $services
     */
    private function reindexModel(string $model, array $services): void
    {
        if (! isset($services[$model])) {
            throw new InvalidArgumentException("Service for model '{$model}' not found");
        }

        $this->info("Reindexing {$model}...");

        $progressBar = $this->output->createProgressBar(1);
        $progressBar->start();

        $services[$model]->reindex();

        $progressBar->advance();
        $progressBar->finish();
        $this->newLine();

        $this->info("✓ {$model} reindexed successfully");
    }

    /**
     * Reindex all models.
     */
    /**
     * @param  array<string, mixed>  $services
     */
    private function reindexAllModels(array $services): void
    {
        $models = array_keys($services);
        $progressBar = $this->output->createProgressBar(count($models));
        $progressBar->start();

        foreach ($services as $modelName => $service) {
            $this->info("Reindexing {$modelName}...");
            $service->reindex();
            $progressBar->advance();
            $this->info("✓ {$modelName} reindexed");
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Clear search caches.
     */
    /**
     * @param  array<string, mixed>  $services
     */
    private function clearSearchCaches(array $services): void
    {
        $this->info('Clearing search caches...');

        foreach ($services as $modelName => $service) {
            $service->clearCache();
            $this->info("✓ {$modelName} cache cleared");
        }
    }
}

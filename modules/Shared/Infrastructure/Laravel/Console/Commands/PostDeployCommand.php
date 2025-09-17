<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Illuminate\Console\Command;

class PostDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run post-deployment tasks (publish assets, clear caches, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running post-deployment tasks...');

        // Publish Livewire assets
        $this->info('Publishing Livewire assets...');
        $this->call('vendor:publish', ['--tag' => 'livewire:assets', '--force' => true]);

        // Publish Filament assets
        $this->info('Publishing Filament assets...');
        $this->call('filament:assets');

        // Create storage symlink
        $this->info('Creating storage symlink...');
        $this->call('storage:link', ['--force' => true]);

        // Clear and rebuild caches
        $this->info('Optimizing application...');
        $this->call('optimize');

        // Cache Filament components
        $this->info('Caching Filament components...');
        $this->call('filament:cache-components');

        // Configure Meilisearch settings
        $this->info('Configuring Meilisearch index settings...');
        $this->call('meilisearch:configure');

        $this->info('✅ Post-deployment tasks completed successfully!');

        return Command::SUCCESS;
    }
}

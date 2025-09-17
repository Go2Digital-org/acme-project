<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeilisearchConfigureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:configure
                            {--index= : Specific index to configure (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Meilisearch index settings including pagination limits';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = config('scout.meilisearch.host');
        $key = config('scout.meilisearch.key');
        $prefix = config('scout.prefix', 'acme_');

        if (! $host || ! $key) {
            $this->error('Meilisearch host or key not configured');

            return Command::FAILURE;
        }

        $specificIndex = $this->option('index');

        // Define indices to configure
        $indices = $specificIndex
            ? [$prefix . $specificIndex]
            : [
                $prefix . 'campaigns',
                $prefix . 'donations',
                $prefix . 'users',
                $prefix . 'organizations',
                $prefix . 'employees',
                $prefix . 'categories',
                $prefix . 'pages',
            ];

        $this->info('Configuring Meilisearch indices...');

        foreach ($indices as $index) {
            $this->configureIndex($host, $key, $index);
        }

        $this->info('✅ Meilisearch configuration completed successfully!');

        return Command::SUCCESS;
    }

    private function configureIndex(string $host, string $key, string $index): void
    {
        $this->info("Configuring index: {$index}");

        try {
            // Update pagination settings to allow more results
            $paginationSettings = [
                'maxTotalHits' => 100000, // Increase from default 1000 to 100k
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
            ])->patch("{$host}/indexes/{$index}/settings/pagination", $paginationSettings);

            if (! $response->successful()) {
                $this->warn('  ⚠ Failed to update pagination settings: ' . $response->body());

                return;
            }

            $this->line('  ✓ Updated pagination settings (maxTotalHits: 100000)');

            // Get existing settings from config
            $indexName = str_replace(config('scout.prefix', 'acme_'), '', $index);
            $settings = config("scout.meilisearch.index-settings.{$indexName}", []);

            if (empty($settings)) {
                return;
            }

            $this->applyIndexSettings($host, $key, $index, $settings);

        } catch (Exception $e) {
            $this->error("  ✗ Error configuring index {$index}: " . $e->getMessage());
            Log::error('MeilisearchConfigureCommand: Failed to configure index', [
                'index' => $index,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyIndexSettings(string $host, string $key, string $index, array $settings): void
    {
        foreach ($settings as $settingType => $settingValue) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $key,
                ])->patch("{$host}/indexes/{$index}/settings/{$settingType}", $settingValue);

                if ($response->successful()) {
                    $this->line("  ✓ Applied {$settingType} settings");

                    continue;
                }

                $this->warn("  ⚠ Failed to apply {$settingType} settings: " . $response->body());
            } catch (Exception $e) {
                $this->warn("  ⚠ Error applying {$settingType} settings: " . $e->getMessage());
            }
        }
    }
}

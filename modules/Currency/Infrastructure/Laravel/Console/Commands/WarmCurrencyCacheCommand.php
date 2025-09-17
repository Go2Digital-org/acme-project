<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;

/**
 * Command to warm the currency cache.
 * Useful for deployment scripts and performance optimization.
 */
final class WarmCurrencyCacheCommand extends Command
{
    protected $signature = 'currency:warm-cache {--clear : Clear cache before warming}';

    protected $description = 'Warm the currency cache for optimal performance';

    public function __construct(
        private readonly CurrencyQueryRepositoryInterface $currencyRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔥 Starting currency cache warming...');

        try {
            // Clear cache if requested
            if ($this->option('clear')) {
                $this->info('🗑️ Clearing existing currency cache...');
                $this->currencyRepository->clearCache();
            }

            // Warm the cache
            $this->info('🔥 Warming currency cache...');
            $this->currencyRepository->warmCache();

            // Verify cache is working
            $currencies = $this->currencyRepository->getCurrenciesForView();
            $count = $currencies->count();

            if ($count > 0) {
                $this->info("✅ Currency cache warmed successfully with {$count} currencies");

                return Command::SUCCESS;
            }

            $this->warn('⚠️ Cache warming completed but no currencies found');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Failed to warm currency cache: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

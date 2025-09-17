<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Modules\CacheWarming\Application\Command\StartCacheWarmingCommand;
use Modules\CacheWarming\Application\Command\StartCacheWarmingCommandHandler;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;

final class WarmDashboardCacheCommand extends Command
{
    protected $signature = 'cache:warm 
                            {--strategy=all : Warming strategy (all, widgets, system, specific)}
                            {--keys=* : Specific cache keys to warm}
                            {--force : Force warming even if cache is healthy}';

    protected $description = 'Warm dashboard cache with various options';

    public function __construct(
        private readonly StartCacheWarmingCommandHandler $handler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $strategy = $this->getWarmingStrategy();
            $specificKeys = $this->getSpecificKeys();
            $force = (bool) $this->option('force');

            $this->info('Starting cache warming...');
            $this->info("Strategy: {$strategy}");

            if ($specificKeys !== []) {
                $this->info('Specific keys: ' . implode(', ', array_map(
                    static fn (string $key): string => $key,
                    $specificKeys
                )));
            }

            $command = new StartCacheWarmingCommand(
                strategy: $strategy,
                specificKeys: $specificKeys === [] ? null : $specificKeys,
                forceRegenerate: $force
            );

            $result = $this->handler->handle($command);

            if ($result->isComplete()) {
                $this->info('Cache warming completed successfully!');
                $this->info("Warmed {$result->currentItem} of {$result->totalItems} cache keys.");
                $this->info("Completion percentage: {$result->getPercentageComplete()}%");

                return self::SUCCESS;
            }

            if ($result->status->isFailed()) {
                $this->error('Cache warming failed');

                return self::FAILURE;
            }

            $this->info('Cache warming initiated...');
            $this->info("Progress: {$result->currentItem}/{$result->totalItems} ({$result->getPercentageComplete()}%)");

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('Error during cache warming: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function getWarmingStrategy(): string
    {
        $strategyOption = (string) $this->option('strategy');

        return match ($strategyOption) {
            'all' => 'all',
            'widgets' => 'widget', // Handler expects 'widget', not 'widgets'
            'system' => 'system',
            'specific' => 'specific',
            default => throw new Exception("Invalid strategy: {$strategyOption}. Use: all, widgets, system, or specific")
        };
    }

    /**
     * @return array<string>
     */
    private function getSpecificKeys(): array
    {
        $keysOption = $this->option('keys');

        if ($keysOption === []) {
            return [];
        }

        $keys = [];
        foreach ($keysOption as $keyString) {
            if ($keyString === null) {
                continue;
            }
            try {
                new CacheKey($keyString); // Validate the key
                $keys[] = $keyString;
            } catch (Exception $e) {
                $this->warn("Invalid cache key '{$keyString}': {$e->getMessage()}");
            }
        }

        return $keys;
    }
}

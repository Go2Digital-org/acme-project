<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use InvalidArgumentException;
use Modules\CacheWarming\Domain\Model\CacheWarmingProgress;
use Modules\CacheWarming\Domain\Service\CacheWarmingOrchestrator;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class StartCacheWarmingCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CacheWarmingOrchestrator $orchestrator
    ) {}

    public function handle(CommandInterface $command): CacheWarmingProgress
    {
        if (! $command instanceof StartCacheWarmingCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Handle specific keys provided (treat empty array as null)
        if ($command->specificKeys !== null && $command->specificKeys !== []) {
            $cacheKeys = array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                $command->specificKeys
            );

            return $this->orchestrator->warmCaches($cacheKeys);
        }

        // Handle strategy-based warming
        if ($command->strategy !== null) {
            return $this->warmByStrategy($command->strategy);
        }

        // Default to warming all caches
        return $this->orchestrator->warmAllCaches();
    }

    private function warmByStrategy(string $strategy): CacheWarmingProgress
    {
        return match ($strategy) {
            'all' => $this->orchestrator->warmAllCaches(),
            'widget' => $this->orchestrator->warmWidgetCaches(),
            'system' => $this->orchestrator->warmSystemCaches(),
            'priority' => $this->warmPriorityCaches(),
            default => throw new InvalidArgumentException("Unsupported warming strategy: {$strategy}")
        };
    }

    private function warmPriorityCaches(): CacheWarmingProgress
    {
        $priorityKeys = $this->orchestrator->createWarmingStrategy('priority');

        return $this->orchestrator->warmCaches($priorityKeys);
    }
}

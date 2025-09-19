<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ScheduleCacheWarmingCommand implements CommandInterface
{
    /**
     * @param  array<int, string>|null  $specificKeys
     */
    public function __construct(
        public ?string $cacheType = null,
        public ?array $specificKeys = null,
        public int $delayInSeconds = 0,
        public bool $highPriority = false
    ) {}
}

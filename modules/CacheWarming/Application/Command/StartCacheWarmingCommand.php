<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class StartCacheWarmingCommand implements CommandInterface
{
    public function __construct(
        public ?string $strategy = null,
        /** @var array<string>|null */
        public ?array $specificKeys = null,
        public bool $forceRegenerate = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class InvalidateCacheCommand implements CommandInterface
{
    /**
     * @param  array<string>|null  $specificKeys
     */
    public function __construct(
        public ?string $cacheType = null,
        public ?array $specificKeys = null,
        public bool $forceInvalidation = false
    ) {}
}

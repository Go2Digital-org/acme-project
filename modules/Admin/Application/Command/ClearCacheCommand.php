<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ClearCacheCommand implements CommandInterface
{
    /**
     * @param  array<string>  $cacheTypes  Cache types to clear ['config', 'route', 'view', 'application', 'queue']
     */
    public function __construct(
        public array $cacheTypes,
        public bool $clearAll,
        public int $triggeredBy
    ) {}
}

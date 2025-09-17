<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class WarmUserDashboardCacheCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public bool $force = false
    ) {}
}

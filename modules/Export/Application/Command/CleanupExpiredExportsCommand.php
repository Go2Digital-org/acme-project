<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class CleanupExpiredExportsCommand implements CommandInterface
{
    public function __construct(
        public int $batchSize = 100,
        public bool $dryRun = false,
        public ?int $daysOld = null
    ) {}
}

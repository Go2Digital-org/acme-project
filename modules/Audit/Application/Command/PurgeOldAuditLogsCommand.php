<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class PurgeOldAuditLogsCommand implements CommandInterface
{
    public function __construct(
        public int $daysToKeep = 365,
        public ?string $auditableType = null,
        public ?int $batchSize = 1000
    ) {}
}

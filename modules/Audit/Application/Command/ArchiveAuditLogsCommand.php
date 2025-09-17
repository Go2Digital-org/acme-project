<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ArchiveAuditLogsCommand implements CommandInterface
{
    public function __construct(
        public int $daysOld = 90,
        public ?string $auditableType = null,
        public string $archiveStorage = 'audit-archive',
        public ?int $batchSize = 500
    ) {}
}

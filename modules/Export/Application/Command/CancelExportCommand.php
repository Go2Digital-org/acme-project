<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class CancelExportCommand implements CommandInterface
{
    public function __construct(
        public ExportId $exportId,
        public int $userId,
        public string $reason = 'Cancelled by user'
    ) {}
}

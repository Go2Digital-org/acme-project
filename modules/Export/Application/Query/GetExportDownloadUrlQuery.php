<?php

declare(strict_types=1);

namespace Modules\Export\Application\Query;

use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetExportDownloadUrlQuery implements QueryInterface
{
    public function __construct(
        public ExportId $exportId,
        public int $userId,
        public int $expiresInMinutes = 60
    ) {}
}

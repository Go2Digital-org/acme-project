<?php

declare(strict_types=1);

namespace Modules\Export\Application\Query;

use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetUserExportsQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public int $page = 1,
        public int $perPage = 15,
        public ?ExportStatus $status = null,
        public ?string $resourceType = null,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc'
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchAuditLogsQuery implements QueryInterface
{
    public function __construct(
        public ?string $search = null,
        public ?string $event = null,
        public ?string $auditableType = null,
        public ?int $auditableId = null,
        public ?int $userId = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortOrder = 'desc',
        public int $page = 1,
        public int $perPage = 20
    ) {}
}

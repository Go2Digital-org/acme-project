<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetAuditStatsQuery implements QueryInterface
{
    public function __construct(
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $auditableType = null,
        public ?int $userId = null,
        public ?string $groupBy = 'day' // day, week, month, year
    ) {}
}

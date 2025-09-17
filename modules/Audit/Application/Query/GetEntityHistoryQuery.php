<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetEntityHistoryQuery implements QueryInterface
{
    public function __construct(
        public string $auditableType,
        public int $auditableId,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $event = null,
        public int $page = 1,
        public int $perPage = 50
    ) {}
}

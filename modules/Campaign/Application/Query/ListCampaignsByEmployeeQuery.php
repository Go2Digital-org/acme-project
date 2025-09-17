<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListCampaignsByEmployeeQuery implements QueryInterface
{
    public function __construct(
        public int $employeeId,
        public bool $activeOnly = false,
    ) {}
}

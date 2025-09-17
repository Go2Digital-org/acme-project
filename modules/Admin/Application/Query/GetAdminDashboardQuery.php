<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetAdminDashboardQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public ?string $dateRange = null, // 'week', 'month', 'year'
        public bool $includeSystemStats = true,
        public bool $includeRecentActivity = true
    ) {}
}

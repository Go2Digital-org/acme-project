<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting notification metrics.
 */
final readonly class GetNotificationMetricsQuery implements QueryInterface
{
    public function __construct(
        public CarbonInterface $startDate,
        public CarbonInterface $endDate,
        public string $groupBy = 'day',
        public ?int $userId = null,
        public ?int $organizationId = null,
    ) {}
}

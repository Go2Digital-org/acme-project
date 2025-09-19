<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting notification statistics.
 */
final readonly class GetNotificationStatsQuery implements QueryInterface
{
    /**
     * @param  array<string>  $types
     * @param  array<string>  $channels
     * @param  array<string>  $statuses
     */
    public function __construct(
        public ?int $userId = null,
        public ?int $organizationId = null,
        public ?CarbonInterface $startDate = null,
        public ?CarbonInterface $endDate = null,
        /** @var array<string, mixed> */
        public array $types = [],
        /** @var array<string, mixed> */
        public array $channels = [],
        /** @var array<string, mixed> */
        public array $statuses = [],
        public bool $includeBreakdown = true,
        public bool $includeTrends = false,
        public string $groupBy = 'day',
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting notification digest data.
 */
final readonly class GetNotificationDigestQuery implements QueryInterface
{
    /**
     * @param  array<string>  $includeTypes
     * @param  array<string>  $excludeTypes
     */
    public function __construct(
        public int $userId,
        public string $digestType = 'daily',
        public ?CarbonInterface $startDate = null,
        public ?CarbonInterface $endDate = null,
        /** @var array<string, mixed> */
        public array $includeTypes = [],
        /** @var array<string, mixed> */
        public array $excludeTypes = [],
        public bool $includeRead = false,
        public bool $groupByType = true,
        public bool $includeSummaryStats = true,
        public int $maxNotifications = 100,
    ) {}
}

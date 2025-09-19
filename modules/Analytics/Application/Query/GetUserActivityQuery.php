<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetUserActivityQuery implements QueryInterface
{
    public function __construct(
        public ?int $userId = null,
        public ?int $organizationId = null,
        public ?string $timeRange = null,
        /** @var array<string> */
        public array $activityTypes = [],
        public bool $includeSessionData = false,
        public bool $includeEngagementMetrics = true,
        public bool $includeBehaviorPatterns = true,
        public bool $includeComparisons = false,
        public ?string $granularity = 'day',
        public int $limit = 100,
    ) {}
}

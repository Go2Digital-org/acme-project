<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetCampaignAnalyticsQuery implements QueryInterface
{
    public function __construct(
        public ?int $campaignId = null,
        public ?int $organizationId = null,
        public ?string $timeRange = null,
        /** @var array<string> */
        public array $metrics = [],
        public bool $includeComparisons = false,
        public bool $includeBreakdowns = true,
        public bool $includeTrends = true,
        public ?string $granularity = 'day', // hour, day, week, month
    ) {}
}

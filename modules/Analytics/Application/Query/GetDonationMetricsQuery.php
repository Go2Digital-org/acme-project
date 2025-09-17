<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDonationMetricsQuery implements QueryInterface
{
    /**
     * @param  array<string>  $metrics
     */
    public function __construct(
        public ?int $campaignId = null,
        public ?int $organizationId = null,
        public ?int $donorId = null,
        public ?string $timeRange = null,
        public array $metrics = [],
        public bool $includeComparisons = false,
        public bool $includeTrends = true,
        public bool $includeSegmentation = true,
        public ?string $granularity = 'day',
        public ?string $currency = null,
    ) {}
}

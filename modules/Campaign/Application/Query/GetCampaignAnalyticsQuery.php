<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for retrieving campaign analytics data.
 */
final readonly class GetCampaignAnalyticsQuery implements QueryInterface
{
    public function __construct(
        public int $campaignId,
        /** @var array<string, mixed>|null */
        public ?array $filters = null,
        public bool $forceRefresh = false
    ) {}
}

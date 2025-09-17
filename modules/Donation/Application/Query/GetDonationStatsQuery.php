<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDonationStatsQuery implements QueryInterface
{
    public function __construct(
        public ?int $campaignId = null,
        public ?int $employeeId = null,
        public ?string $period = null, // 'today', 'week', 'month', 'year'
    ) {}
}

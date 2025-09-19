<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDonationsByUserQuery implements QueryInterface
{
    /**
     * @param  array<string>|null  $statuses
     */
    public function __construct(
        public int $userId,
        public ?array $statuses = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $campaignId = null,
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc'
    ) {}
}

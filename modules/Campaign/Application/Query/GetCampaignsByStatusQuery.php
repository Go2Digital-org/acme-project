<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetCampaignsByStatusQuery implements QueryInterface
{
    /**
     * @param  array<int, string>  $statuses
     */
    public function __construct(
        public array $statuses,
        public ?int $organizationId = null,
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc'
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for retrieving organization dashboard data.
 */
final readonly class GetOrganizationDashboardQuery implements QueryInterface
{
    /**
     * @param  array<string, mixed>|null  $filters
     */
    public function __construct(
        public int $organizationId,
        public ?array $filters = null,
        public bool $forceRefresh = false
    ) {}
}

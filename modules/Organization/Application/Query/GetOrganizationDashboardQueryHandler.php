<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use InvalidArgumentException;
use Modules\Organization\Application\ReadModel\OrganizationDashboardReadModel;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationDashboardRepository;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for retrieving organization dashboard using read models.
 */
final readonly class GetOrganizationDashboardQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrganizationDashboardRepository $repository,
    ) {}

    public function handle(QueryInterface $query): ?OrganizationDashboardReadModel
    {
        if (! $query instanceof GetOrganizationDashboardQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        if ($query->forceRefresh) {
            $result = $this->repository->refresh($query->organizationId);

            return $result instanceof OrganizationDashboardReadModel ? $result : null;
        }

        $result = $this->repository->find($query->organizationId, $query->filters);

        return $result instanceof OrganizationDashboardReadModel ? $result : null;
    }
}

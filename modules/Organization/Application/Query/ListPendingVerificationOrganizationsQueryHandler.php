<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use InvalidArgumentException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListPendingVerificationOrganizationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {}

    /**
     * @return array<Organization>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof ListPendingVerificationOrganizationsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        return $this->repository->findPendingVerificationOrganizations();
    }
}

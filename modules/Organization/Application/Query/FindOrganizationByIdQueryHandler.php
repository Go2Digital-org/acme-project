<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use InvalidArgumentException;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

readonly class FindOrganizationByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): Organization
    {
        if (! $query instanceof FindOrganizationByIdQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $organization = $this->repository->findById($query->organizationId);

        if (! $organization instanceof Organization) {
            throw OrganizationException::notFound($query->organizationId);
        }

        return $organization;
    }
}

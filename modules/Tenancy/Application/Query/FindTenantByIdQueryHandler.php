<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Query;

use Modules\Tenancy\Application\ReadModel\TenantReadModel;
use Modules\Tenancy\Domain\Model\Tenant;
use Modules\Tenancy\Domain\Repository\TenantRepositoryInterface;

final readonly class FindTenantByIdQueryHandler
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository
    ) {}

    public function handle(FindTenantByIdQuery $query): ?TenantReadModel
    {
        $tenant = $this->tenantRepository->findById($query->tenantId);

        if (! $tenant instanceof Tenant) {
            return null;
        }

        return TenantReadModel::fromDomainModel($tenant);
    }
}

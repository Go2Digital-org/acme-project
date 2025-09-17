<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Repository;

use Modules\Tenancy\Domain\Model\Tenant;
use Modules\Tenancy\Domain\ValueObject\TenantId;

interface TenantRepositoryInterface
{
    public function findById(TenantId $id): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    public function findBySubdomain(string $subdomain): ?Tenant;

    public function save(Tenant $tenant): void;

    public function delete(Tenant $tenant): void;

    /**
     * @return array<Tenant>
     */
    public function findAll(): array;

    /**
     * @return array<Tenant>
     */
    public function findActive(): array;
}

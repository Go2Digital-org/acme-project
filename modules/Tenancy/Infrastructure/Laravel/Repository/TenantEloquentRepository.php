<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Repository;

use Modules\Tenancy\Domain\Model\Tenant;
use Modules\Tenancy\Domain\Repository\TenantRepositoryInterface;
use Modules\Tenancy\Domain\ValueObject\TenantId;

/**
 * Tenant Eloquent Repository Implementation.
 *
 * Provides tenant data access with multi-tenancy support.
 */
class TenantEloquentRepository implements TenantRepositoryInterface
{
    /**
     * Find tenant by ID.
     */
    public function findById(TenantId $id): ?Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($id->value());

        return $tenant;
    }

    /**
     * Find tenant by domain.
     */
    public function findByDomain(string $domain): ?Tenant
    {
        // Use the base tenant functionality from stancl/tenancy
        /** @var Tenant|null $tenant */
        $tenant = Tenant::where('data->domain', $domain)->first();

        return $tenant;
    }

    /**
     * Find tenant by subdomain.
     */
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        return $tenant;
    }

    /**
     * Save tenant.
     */
    public function save(Tenant $tenant): void
    {
        $tenant->save();
    }

    /**
     * Delete tenant.
     */
    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }

    /**
     * Find all tenants.
     *
     * @return array<int, Tenant>
     */
    public function findAll(): array
    {
        /** @var array<int, Tenant> $tenants */
        $tenants = Tenant::orderBy('created_at', 'desc')->get()->all();

        return $tenants;
    }

    /**
     * Find active tenants.
     *
     * @return array<int, Tenant>
     */
    public function findActive(): array
    {
        /** @var array<int, Tenant> $tenants */
        $tenants = Tenant::where('provisioning_status', Tenant::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        return $tenants;
    }
}

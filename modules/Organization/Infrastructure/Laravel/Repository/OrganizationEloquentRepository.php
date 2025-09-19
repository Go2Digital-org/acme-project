<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Repository;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;

class OrganizationEloquentRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        private readonly Organization $model,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Organization
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, only return if it's the current tenant's ID
            $currentTenant = tenant();

            return ($currentTenant->id === $id) ? $currentTenant : null;
        }

        // In central context, normal query
        return $this->model->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    /**
     * @return array<int, mixed>
     */
    public function findActiveOrganizations(): array
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, return only the current organization
            // The tenant IS the organization
            return [tenant()];
        }

        // In central context, return all active organizations
        return $this->model
            ->where('is_active', true)
            ->where('is_verified', true)
            ->get()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    public function findVerifiedOrganizations(): array
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, return current organization if verified
            $currentTenant = tenant();

            return $currentTenant->is_verified ? [$currentTenant] : [];
        }

        // In central context, return all verified organizations
        return $this->model
            ->where('is_verified', true)
            ->get()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    public function findPendingVerificationOrganizations(): array
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, return current organization if pending
            $currentTenant = tenant();

            return ($currentTenant->is_active && ! $currentTenant->is_verified) ? [$currentTenant] : [];
        }

        // In central context, return all pending organizations
        return $this->model
            ->where('is_active', true)
            ->where('is_verified', false)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Organization>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, return a paginator with only the current tenant
            $collection = collect([tenant()]);

            return new LengthAwarePaginator(
                $collection,
                $collection->count(),
                $perPage,
                $page,
                ['path' => request()->url()]
            );
        }

        // In central context, normal query
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->nl', 'like', "%{$search}%")
                    ->orWhere('name->fr', 'like', "%{$search}%")
                    ->orWhere('description->en', 'like', "%{$search}%")
                    ->orWhere('description->nl', 'like', "%{$search}%")
                    ->orWhere('description->fr', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['status'])) {
            // Status is computed from is_active and is_verified
            switch ($filters['status']) {
                case 'active':
                    $query->where('is_active', true)->where('is_verified', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'unverified':
                case 'pending':
                    $query->where('is_active', true)->where('is_verified', false);
                    break;
            }
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByName(string $name): ?Organization
    {
        // Search in JSON field for any language
        return $this->model->whereJsonContains('name', $name)
            ->orWhere('name->en', $name)
            ->orWhere('name->nl', $name)
            ->orWhere('name->fr', $name)
            ->first();
    }

    public function findByRegistrationNumber(string $registrationNumber): ?Organization
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, check if current tenant matches
            $currentTenant = tenant();

            return ($currentTenant->registration_number === $registrationNumber) ? $currentTenant : null;
        }

        // In central context, search by registration number
        return $this->model->where('registration_number', $registrationNumber)->first();
    }

    public function findByTaxId(string $taxId): ?Organization
    {
        // Check if we're in tenant context
        if (tenant()) {
            // In tenant context, check if current tenant matches
            $currentTenant = tenant();

            return ($currentTenant->tax_id === $taxId) ? $currentTenant : null;
        }

        // In central context, search by tax ID
        return $this->model->where('tax_id', $taxId)->first();
    }

    /**
     * Get all organizations.
     */
    /**
     * @return array<int, mixed>
     */
    public function findAll(): array
    {
        return $this->model->all()->all();
    }
}

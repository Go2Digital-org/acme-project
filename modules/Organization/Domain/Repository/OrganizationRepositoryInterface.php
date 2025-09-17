<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Repository;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Domain\Model\Organization;

interface OrganizationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization;

    public function findById(int $id): ?Organization;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    public function delete(int $id): bool;

    /**
     * @return array<int, Organization>
     */
    public function findActiveOrganizations(): array;

    /**
     * @return array<int, Organization>
     */
    public function findVerifiedOrganizations(): array;

    /**
     * @return array<int, Organization>
     */
    public function findPendingVerificationOrganizations(): array;

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
    ): LengthAwarePaginator;

    public function findByName(string $name): ?Organization;

    public function findByRegistrationNumber(string $registrationNumber): ?Organization;

    public function findByTaxId(string $taxId): ?Organization;

    /**
     * Get all organizations.
     *
     * @return array<int, Organization>
     */
    public function findAll(): array;
}

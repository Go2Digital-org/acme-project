<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Repository;

use Modules\Team\Domain\Model\Team;
use Modules\Team\Domain\ValueObject\TeamId;

/**
 * Team Repository Interface (Port)
 */
interface TeamRepositoryInterface
{
    /**
     * Find team by ID
     */
    public function findById(TeamId $id): ?Team;

    /**
     * Find team by slug within organization
     */
    public function findBySlug(string $slug, int $organizationId): ?Team;

    /**
     * Create new team
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Team;

    /**
     * Update team
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TeamId $id, array $data): bool;

    /**
     * Delete team (soft delete)
     */
    public function delete(TeamId $id): bool;

    /**
     * Get teams for organization
     *
     * @return Team[]
     */
    public function findByOrganization(int $organizationId): array;

    /**
     * Get teams where user is a member
     *
     * @return Team[]
     */
    public function findByUser(int $userId): array;

    /**
     * Check if team slug exists in organization
     */
    public function slugExists(string $slug, int $organizationId, ?TeamId $excludeId = null): bool;

    /**
     * Get team statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(TeamId $id): array;

    /**
     * Search teams by name or description
     *
     * @return Team[]
     */
    public function search(string $query, int $organizationId, int $limit = 20): array;
}

<?php

declare(strict_types=1);

namespace Modules\User\Domain\Repository;

use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Domain\ValueObject\UserRole;
use Modules\User\Domain\ValueObject\UserStatus;

/**
 * User Repository Interface (Port).
 *
 * Defines the contract for user data persistence and retrieval.
 * Implements Repository pattern for clean domain separation.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email address.
     */
    public function findByEmail(EmailAddress $email): ?User;

    /**
     * Check if user exists by email.
     */
    public function existsByEmail(EmailAddress $email): bool;

    /**
     * Save user (create or update).
     */
    public function save(User $user): User;

    /**
     * Delete user by ID.
     */
    public function deleteById(int $id): bool;

    /**
     * Find users by status.
     *
     * @return array<int, User>
     */
    public function findByStatus(UserStatus $status, int $limit = 50, int $offset = 0): array;

    /**
     * Find users by role.
     *
     * @return array<int, User>
     */
    public function findByRole(UserRole $role, int $limit = 50, int $offset = 0): array;

    /**
     * Find active users.
     *
     * @return array<int, User>
     */
    public function findActiveUsers(int $limit = 50, int $offset = 0): array;

    /**
     * Find users requiring verification.
     *
     * @return array<int, User>
     */
    public function findUnverifiedUsers(int $limit = 50, int $offset = 0): array;

    /**
     * Search users by name or email.
     *
     * @return array<int, User>
     */
    public function searchUsers(string $query, int $limit = 50, int $offset = 0): array;

    // findByDepartment method removed as department field was removed from User model

    /**
     * Get user statistics.
     *
     * @return array<string, int>
     */
    public function getUserStats(): array;

    /**
     * Count users by status.
     */
    public function countByStatus(UserStatus $status): int;

    /**
     * Count users by role.
     */
    public function countByRole(UserRole $role): int;

    /**
     * Count total users.
     */
    public function countTotal(): int;

    /**
     * Find recently created users.
     *
     * @return array<int, User>
     */
    public function findRecentUsers(int $days = 7, int $limit = 50): array;

    /**
     * Find users created between dates.
     *
     * @return array<int, User>
     */
    public function findUsersByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 50,
        int $offset = 0,
    ): array;

    /**
     * Find users who have made donations.
     *
     * @return array<int, User>
     */
    public function findDonors(int $limit = 50, int $offset = 0): array;

    /**
     * Find users who have created campaigns.
     *
     * @return array<int, User>
     */
    public function findCampaignCreators(int $limit = 50, int $offset = 0): array;

    /**
     * Find top donors by total amount.
     *
     * @return array<int, User>
     */
    public function findTopDonors(int $limit = 10): array;

    /**
     * Find most active campaign creators.
     *
     * @return array<int, User>
     */
    public function findMostActiveCampaignCreators(int $limit = 10): array;

    /**
     * Find users with two-factor authentication enabled.
     *
     * @return array<int, User>
     */
    public function findUsersWithTwoFactor(int $limit = 50, int $offset = 0): array;

    /**
     * Find administrators.
     *
     * @return array<int, User>
     */
    public function findAdministrators(): array;

    /**
     * Find users by multiple IDs.
     *
     * @param  array<int, int>  $ids
     * @return array<int, User>
     */
    public function findByIds(array $ids): array;

    /**
     * Update user last login timestamp.
     */
    public function updateLastLogin(int $userId, DateTimeInterface $loginTime): void;

    /**
     * Bulk update user statuses.
     *
     * @param  array<int, int>  $userIds
     */
    public function bulkUpdateStatus(array $userIds, UserStatus $status): int;

    /**
     * Get user activity summary.
     *
     * @return array<string, mixed>
     */
    public function getUserActivitySummary(int $userId): array;

    /**
     * Find inactive users (haven't logged in for X days).
     *
     * @return array<int, User>
     */
    public function findInactiveUsers(int $days = 90, int $limit = 50): array;

    /**
     * Paginate users with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    /**
     * Update user by ID with data.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    /**
     * Get total employee count (users with EMPLOYEE role).
     */
    public function getTotalEmployeeCount(): int;
}

<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Domain\ValueObject\UserRole;
use Modules\User\Domain\ValueObject\UserStatus;
use Modules\User\Infrastructure\Laravel\Models\User as EloquentUser;
use RuntimeException;

/**
 * User Eloquent Repository.
 *
 * Laravel Eloquent implementation of the UserRepositoryInterface.
 * Handles conversion between domain models and Eloquent models.
 */
final class UserEloquentRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $eloquentUser = EloquentUser::find($id);

        return $eloquentUser ? $this->toDomainModel($eloquentUser) : null;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email->value)->first();

        return $eloquentUser ? $this->toDomainModel($eloquentUser) : null;
    }

    public function existsByEmail(EmailAddress $email): bool
    {
        return DB::table('users')->where('email', $email->value)->exists();
    }

    public function save(User $user): User
    {
        $data = $this->mapDomainToEloquent($user);

        if ($user->getId() !== 0) {
            // Update existing user
            $eloquentUser = EloquentUser::findOrFail($user->getId());
            $eloquentUser->update($data);
        } else {
            // Create new user
            $eloquentUser = EloquentUser::create($data);
        }

        $freshUser = $eloquentUser->fresh();

        if ($freshUser === null) {
            throw new RuntimeException('User model could not be refreshed after save');
        }

        return $this->toDomainModel($freshUser);
    }

    public function deleteById(int $id): bool
    {
        return EloquentUser::where('id', $id)->delete() > 0;
    }

    /**
     * @return array<int, User>
     */
    public function findByStatus(UserStatus $status, int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::where('status', $status->value)
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findByRole(UserRole $role, int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::where('role', $role->value)
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findActiveUsers(int $limit = 50, int $offset = 0): array
    {
        return $this->findByStatus(UserStatus::ACTIVE, $limit, $offset);
    }

    /**
     * @return array<int, User>
     */
    public function findUnverifiedUsers(int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::whereNull('email_verified_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function searchUsers(string $query, int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::where(function ($q) use ($query): void {
            $q->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('job_title', 'like', "%{$query}%");
        })
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    // findByDepartment method removed as department field was removed from User model

    /**
     * @return array<string, int>
     */
    public function getUserStats(): array
    {
        return [
            'total_users' => DB::table('users')->count(),
            'active_users' => DB::table('users')->where('status', UserStatus::ACTIVE->value)->count(),
            'inactive_users' => DB::table('users')->where('status', UserStatus::INACTIVE->value)->count(),
            'suspended_users' => DB::table('users')->where('status', UserStatus::SUSPENDED->value)->count(),
            'unverified_users' => DB::table('users')->whereNull('email_verified_at')->count(),
            'admins' => DB::table('users')->where('role', UserRole::ADMIN->value)->count(),
            'employees' => DB::table('users')->where('role', UserRole::EMPLOYEE->value)->count(),
            'users_with_2fa' => DB::table('users')->whereNotNull('two_factor_secret')->count(),
            'recent_logins' => DB::table('users')->where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public function countByStatus(UserStatus $status): int
    {
        return DB::table('users')->where('status', $status->value)->count();
    }

    public function countByRole(UserRole $role): int
    {
        return DB::table('users')->where('role', $role->value)->count();
    }

    public function countTotal(): int
    {
        return DB::table('users')->count();
    }

    /**
     * @return array<int, User>
     */
    public function findRecentUsers(int $days = 7, int $limit = 50): array
    {
        $eloquentUsers = EloquentUser::where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findUsersByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $eloquentUsers = EloquentUser::whereBetween('created_at', [$startDate, $endDate])
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findDonors(int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::whereHas('donations')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findCampaignCreators(int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::whereHas('campaigns')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findTopDonors(int $limit = 10): array
    {
        $eloquentUsers = EloquentUser::withSum('donations', 'amount')
            ->orderBy('donations_sum_amount', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findMostActiveCampaignCreators(int $limit = 10): array
    {
        $eloquentUsers = EloquentUser::withCount('campaigns')
            ->orderBy('campaigns_count', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findUsersWithTwoFactor(int $limit = 50, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::whereNotNull('two_factor_secret')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @return array<int, User>
     */
    public function findAdministrators(): array
    {
        $eloquentUsers = EloquentUser::whereIn('role', [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ])->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, User>
     */
    public function findByIds(array $ids): array
    {
        $eloquentUsers = EloquentUser::whereIn('id', $ids)->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    public function updateLastLogin(int $userId, DateTimeInterface $loginTime): void
    {
        EloquentUser::where('id', $userId)->update([
            'last_login_at' => $loginTime,
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function bulkUpdateStatus(array $userIds, UserStatus $status): int
    {
        return EloquentUser::whereIn('id', $userIds)->update([
            'status' => $status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserActivitySummary(int $userId): array
    {
        $user = EloquentUser::with(['donations', 'campaigns'])
            ->withCount(['donations', 'campaigns'])
            ->withSum('donations', 'amount')
            ->find($userId);

        if (! $user) {
            return [];
        }

        return [
            'total_donations' => $user->donations_count ?? 0,
            'total_donated_amount' => $user->donations_sum_amount ?? 0,
            'total_campaigns' => $user->campaigns_count ?? 0,
            'last_login' => $user->last_login_at,
            'account_age_days' => $user->created_at ? now()->diffInDays($user->created_at) : 0,
        ];
    }

    /**
     * @return array<int, User>
     */
    public function findInactiveUsers(int $days = 90, int $limit = 50): array
    {
        $cutoffDate = now()->subDays($days);

        $eloquentUsers = EloquentUser::where(function ($query) use ($cutoffDate): void {
            $query->where('last_login_at', '<', $cutoffDate)
                ->orWhereNull('last_login_at');
        })
            ->where('created_at', '<', $cutoffDate)
            ->limit($limit)
            ->get();

        return $eloquentUsers->map(fn ($user): User => $this->toDomainModel($user))->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        $query = EloquentUser::query();

        // Apply filters
        if (isset($filters['name']) && is_string($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['email']) && is_string($filters['email'])) {
            $query->where('email', 'like', "%{$filters['email']}%");
        }

        if (isset($filters['department']) && is_string($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (isset($filters['job_title']) && is_string($filters['job_title'])) {
            $query->where('job_title', 'like', "%{$filters['job_title']}%");
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $search = $filters['search'];
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        /** @var LengthAwarePaginator<int, EloquentUser> $eloquentPaginator */
        $eloquentPaginator = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform Eloquent models to Domain models
        $domainUsers = $eloquentPaginator->getCollection()->map(
            fn (EloquentUser $eloquentUser): User => $this->toDomainModel($eloquentUser),
        );

        // Create new paginator with Domain models
        /** @var LengthAwarePaginator<int, User> $domainPaginator */
        $domainPaginator = new LengthAwarePaginator(
            $domainUsers,
            $eloquentPaginator->total(),
            $eloquentPaginator->perPage(),
            $eloquentPaginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );

        return $domainPaginator;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return EloquentUser::where('id', $id)->update($data) > 0;
    }

    /**
     * Get total employee count (users with EMPLOYEE role).
     */
    public function getTotalEmployeeCount(): int
    {
        return EloquentUser::where('role', UserRole::EMPLOYEE->value)->count();
    }

    /**
     * Map domain model to Eloquent-compatible array.
     * Only includes fields that exist in the database.
     *
     * @return array<string, mixed>
     */
    private function mapDomainToEloquent(User $user): array
    {
        $data = [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'name' => $user->getFullName(),
            'email' => $user->getEmailString(),
            'status' => $user->getStatus()->value,
            'role' => $user->getRole()->value,
            'profile_photo_path' => $user->getProfilePhotoPath(),
        ];

        // Only include these fields if we're creating a new user
        if ($user->getId() === 0) {
            $data['created_at'] = $user->getCreatedAt();
        }

        // Optional fields - only include if not null
        if ($user->getEmailVerifiedAt() instanceof DateTimeImmutable) {
            $data['email_verified_at'] = $user->getEmailVerifiedAt();
        }

        // Note: We don't update department, job_title, phone_number here
        // as they don't exist in the database schema

        return $data;
    }

    /**
     * Convert Eloquent model to Domain model.
     */
    private function toDomainModel(EloquentUser $eloquentUser): User
    {
        return new User(
            id: $eloquentUser->id,
            firstName: $eloquentUser->first_name ?? explode(' ', $eloquentUser->name)[0],
            lastName: $eloquentUser->last_name ?? (explode(' ', $eloquentUser->name)[1] ?? ''),
            email: new EmailAddress($eloquentUser->email),
            status: UserStatus::from($eloquentUser->status ?? UserStatus::ACTIVE->value),
            role: UserRole::from($eloquentUser->role ?? UserRole::EMPLOYEE->value),
            createdAt: $eloquentUser->created_at ? $eloquentUser->created_at->toDateTimeImmutable() : new DateTimeImmutable,
            emailVerifiedAt: $eloquentUser->email_verified_at?->toDateTimeImmutable(),
            profilePhotoPath: $eloquentUser->profile_photo_path,
            twoFactorSecret: $eloquentUser->two_factor_secret,
            twoFactorRecoveryCodes: is_array($eloquentUser->two_factor_recovery_codes) ? array_values(array_filter($eloquentUser->two_factor_recovery_codes, 'is_string')) : null,
            jobTitle: $eloquentUser->job_title,
            phoneNumber: $eloquentUser->phone,
            preferences: $this->parsePreferences($eloquentUser->notification_preferences),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePreferences(mixed $preferences): array
    {
        if (is_string($preferences)) {
            $decoded = json_decode($preferences, true);

            if (is_array($decoded)) {
                /** @var array<string, mixed> $result */
                $result = [];

                foreach ($decoded as $key => $value) {
                    if (is_string($key)) {
                        $result[$key] = $value;
                    }
                }

                return $result;
            }

            return [];
        }

        if (is_array($preferences)) {
            /** @var array<string, mixed> $result */
            $result = [];

            foreach ($preferences as $key => $value) {
                if (is_string($key)) {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        return [];
    }
}

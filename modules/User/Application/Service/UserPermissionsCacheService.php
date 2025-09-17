<?php

declare(strict_types=1);

namespace Modules\User\Application\Service;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Application\Service\CacheService;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\LoggerInterface;

/**
 * Service for caching user permissions and roles to prevent N+1 queries.
 * Provides intelligent cache invalidation and warming strategies.
 */
class UserPermissionsCacheService
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get cached user permissions.
     *
     * @return array<string, mixed>
     */
    public function getUserPermissions(int $userId): array
    {
        return $this->cacheService->rememberUserPermissions($userId);
    }

    /**
     * Load user permissions data for caching.
     *
     * @return array<string, mixed>
     */
    public function loadUserPermissionsData(int $userId): array
    {
        $startTime = microtime(true);

        // Get user basic info
        $user = DB::table('users')
            ->where('id', $userId)
            ->first();

        if (! $user) {
            return [];
        }

        // Get user roles and permissions in optimized queries
        $userRoles = $this->getUserRoles($userId);
        $userPermissions = $this->getUserDirectPermissions($userId);
        $rolePermissions = $this->getRolePermissions($userRoles);

        // Combine all permissions
        $allPermissions = array_unique(array_merge($userPermissions, $rolePermissions));

        // Build organization-specific permissions
        $organizationPermissions = $this->getOrganizationPermissions();

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $result = [
            'user_id' => $userId,
            'organization_id' => $user->organization_id ?? null, // @phpstan-ignore-line DB query result object
            'roles' => $userRoles,
            'direct_permissions' => $userPermissions,
            'role_permissions' => $rolePermissions,
            'all_permissions' => $allPermissions,
            'organization_permissions' => $organizationPermissions,
            'is_admin' => $this->isAdmin($userRoles),
            'is_org_admin' => $this->isOrgAdmin($userRoles),
            'is_super_admin' => $this->isSuperAdmin($userRoles),
            'cached_at' => now()->toISOString(),
            'load_time_ms' => $executionTime,
        ];

        $this->logger->debug('User permissions loaded', [
            'user_id' => $userId,
            'roles_count' => count($userRoles),
            'permissions_count' => count($allPermissions),
            'execution_time_ms' => $executionTime,
        ]);

        return $result;
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);

        return in_array($permission, $permissions['all_permissions'] ?? []);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);
        $allPermissions = $userPermissions['all_permissions'] ?? [];

        return array_intersect($permissions, $allPermissions) !== [];
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param  array<string>  $permissions
     */
    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($userId);
        $allPermissions = $userPermissions['all_permissions'] ?? [];

        return array_diff($permissions, $allPermissions) === [];
    }

    /**
     * Check if user has specific role.
     */
    public function hasRole(int $userId, string $role): bool
    {
        $permissions = $this->getUserPermissions($userId);

        return in_array($role, $permissions['roles'] ?? []);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param  array<string>  $roles
     */
    public function hasAnyRole(int $userId, array $roles): bool
    {
        $userPermissions = $this->getUserPermissions($userId);
        $userRoles = $userPermissions['roles'] ?? [];

        return array_intersect($roles, $userRoles) !== [];
    }

    /**
     * Get cached permissions for multiple users to prevent N+1.
     *
     * @param  array<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    public function getBulkUserPermissions(array $userIds): array
    {
        $results = [];
        $uncachedIds = [];

        // Try to get from cache first
        foreach ($userIds as $userId) {
            $cached = $this->getUserPermissions($userId);
            if ($cached !== []) {
                $results[$userId] = $cached;

                continue;
            }

            $uncachedIds[] = $userId;
        }

        // Load uncached permissions in bulk
        if ($uncachedIds !== []) {
            $bulkPermissions = $this->loadBulkUserPermissions($uncachedIds);
            foreach ($bulkPermissions as $userId => $permissions) {
                $results[$userId] = $permissions;

                // Cache the result
                $this->cacheService->rememberWithTtl(
                    "permissions:user:{$userId}",
                    fn () => $permissions,
                    3600, // 1 hour
                    ['permissions', 'users', "user:{$userId}"]
                );
            }
        }

        return $results;
    }

    /**
     * Invalidate permissions cache for user.
     */
    public function invalidateUserPermissions(int $userId): void
    {
        $this->cacheService->invalidateUserPermissions($userId);

        $this->logger->info('User permissions cache invalidated', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Invalidate permissions cache for all users with specific role.
     */
    public function invalidateRolePermissions(string $roleName): void
    {
        // Get all users with this role
        $userIds = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', $roleName)
            ->pluck('model_id')
            ->toArray();

        foreach ($userIds as $userId) {
            $this->invalidateUserPermissions($userId);
        }

        $this->logger->info('Role permissions cache invalidated', [
            'role' => $roleName,
            'affected_users' => count($userIds),
        ]);
    }

    /**
     * Warm permissions cache for organization users.
     */
    public function warmOrganizationPermissions(int $organizationId): void
    {
        $userIds = DB::table('users')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        $this->warmPermissionsForUsers($userIds);
    }

    /**
     * Warm permissions cache for multiple users.
     *
     * @param  array<int>  $userIds
     */
    public function warmPermissionsForUsers(array $userIds): void
    {
        $batchSize = 50;
        $batches = array_chunk($userIds, $batchSize);

        foreach ($batches as $batch) {
            try {
                $this->getBulkUserPermissions($batch);
            } catch (Exception $e) {
                $this->logger->warning('Failed to warm permissions for user batch', [
                    'batch_size' => count($batch),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get cache statistics for user permissions.
     *
     * @return array<string, mixed>
     */
    public function getPermissionsCacheStatistics(): array
    {
        $stats = [
            'total_cached_users' => 0,
            'cache_hit_rate' => 0,
            'avg_load_time_ms' => 0,
        ];

        try {
            // Get sample of recent active users
            $recentUsers = DB::table('users')
                ->where('last_login_at', '>=', now()->subDays(7))
                ->limit(100)
                ->pluck('id')
                ->toArray();

            $cached = 0;
            $totalLoadTime = 0;

            foreach ($recentUsers as $userId) {
                $cacheKey = "permissions:user:{$userId}";
                if ($this->cacheService->has($cacheKey)) {
                    $cached++;

                    // Get cached data to check load time
                    $data = Cache::get($cacheKey); // @phpstan-ignore-line Use Laravel facade since CacheService doesn't have get method
                    if (isset($data['load_time_ms'])) {
                        $totalLoadTime += $data['load_time_ms'];
                    }
                }
            }

            $stats['total_cached_users'] = $cached;
            $stats['cache_hit_rate'] = count($recentUsers) > 0 ? ($cached / count($recentUsers)) * 100 : 0;
            $stats['avg_load_time_ms'] = $cached > 0 ? $totalLoadTime / $cached : 0;
        } catch (Exception $e) {
            $this->logger->warning('Failed to get permissions cache statistics', [
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Get user roles.
     *
     * @return array<string>
     */
    private function getUserRoles(int $userId): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->pluck('roles.name')
            ->toArray();
    }

    /**
     * Get user direct permissions.
     *
     * @return array<string>
     */
    private function getUserDirectPermissions(int $userId): array
    {
        return DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_has_permissions.model_id', $userId)
            ->where('model_has_permissions.model_type', User::class)
            ->pluck('permissions.name')
            ->toArray();
    }

    /**
     * Get permissions for roles.
     *
     * @param  array<string>  $roles
     * @return array<string>
     */
    private function getRolePermissions(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        return DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->whereIn('roles.name', $roles)
            ->pluck('permissions.name')
            ->unique()
            ->toArray();
    }

    /**
     * Get organization-specific permissions.
     *
     * @return array<string>
     */
    private function getOrganizationPermissions(): array
    {
        // This would contain organization-specific permission logic
        // For now, return empty array
        return [];
    }

    /**
     * Check if user has admin role.
     *
     * @param  array<string>  $roles
     */
    private function isAdmin(array $roles): bool
    {
        $adminRoles = ['admin', 'organization_admin', 'super_admin'];

        return array_intersect($roles, $adminRoles) !== [];
    }

    /**
     * Check if user has organization admin role.
     *
     * @param  array<string>  $roles
     */
    private function isOrgAdmin(array $roles): bool
    {
        return in_array('organization_admin', $roles) || in_array('super_admin', $roles);
    }

    /**
     * Check if user has super admin role.
     *
     * @param  array<string>  $roles
     */
    private function isSuperAdmin(array $roles): bool
    {
        return in_array('super_admin', $roles);
    }

    /**
     * Load permissions for multiple users in bulk.
     *
     * @param  array<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    private function loadBulkUserPermissions(array $userIds): array
    {
        $results = [];

        // Load all user data in bulk
        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        // Load all roles for these users
        $userRoles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('model_has_roles.model_id', $userIds)
            ->where('model_has_roles.model_type', User::class)
            ->get()
            ->groupBy('model_id');

        // Load all direct permissions for these users
        $userPermissions = DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('model_has_permissions.model_id', $userIds)
            ->where('model_has_permissions.model_type', User::class)
            ->get()
            ->groupBy('model_id');

        // Build results for each user
        foreach ($userIds as $userId) {
            $user = $users->get($userId);
            if (! $user) {
                continue;
            }

            $roles = $userRoles->get($userId)?->pluck('name')->toArray() ?? [];
            $directPermissions = $userPermissions->get($userId)?->pluck('name')->toArray() ?? [];
            $rolePermissions = $this->getRolePermissions($roles);
            $allPermissions = array_unique(array_merge($directPermissions, $rolePermissions));

            $results[$userId] = [
                'user_id' => $userId,
                'organization_id' => $user->organization_id,
                'roles' => $roles,
                'direct_permissions' => $directPermissions,
                'role_permissions' => $rolePermissions,
                'all_permissions' => $allPermissions,
                'organization_permissions' => [],
                'is_admin' => $this->isAdmin($roles),
                'is_org_admin' => $this->isOrgAdmin($roles),
                'is_super_admin' => $this->isSuperAdmin($roles),
                'cached_at' => now()->toISOString(),
                'load_time_ms' => 0, // Not tracked for bulk loads
            ];
        }

        return $results;
    }
}

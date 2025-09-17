<?php

declare(strict_types=1);

namespace Modules\User\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Application\Service\SearchService;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * User search service with specialized filtering and caching.
 *
 * @extends SearchService<User>
 */
class UserSearchService extends SearchService
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getCachePrefix(): string
    {
        return 'user_search';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultFilters(): array
    {
        return [
            'status' => 'active',
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function getSearchableAttributesWeights(): array
    {
        return [
            'name' => 3,
            'first_name' => 3,
            'last_name' => 3,
            'email' => 2,
            'job_title' => 2,
            'department' => 2,
            'phone' => 1,
        ];
    }

    /**
     * Search users by department.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function searchByDepartment(
        string $department,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['department' => $department, 'status' => 'active'],
            sortBy: 'name',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search users by role.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function searchByRole(
        string $role,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['role' => $role, 'status' => 'active'],
            sortBy: 'name',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search users by organization.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function searchByOrganization(
        int $organizationId,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['organization_id' => $organizationId, 'status' => 'active'],
            sortBy: 'name',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get user name suggestions for autocomplete.
     *
     * @return SupportCollection<int, array{id: int, name: string, email: string, job_title: string|null, department: string|null, organization_name: string|null}>
     */
    public function getNameSuggestions(string $query, int $limit = 10): SupportCollection
    {
        if (strlen($query) < 2) {
            return new SupportCollection;
        }

        $cacheKey = $this->getCachePrefix() . ':name_suggestions:' . md5($query . $limit);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit) {
            return User::search($query)
                ->where('status', 'active')
                ->take($limit)
                ->get()
                ->map(function (User $user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->getFullName(),
                        'email' => $user->email,
                        'job_title' => $user->job_title,
                        'department' => $user->department,
                        'organization_name' => $user->organization?->getName(),
                    ];
                });
        });
    }

    /**
     * Get department facets.
     *
     * @return array<string, int>
     */
    public function getDepartmentFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':department_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $builder = User::search($query)->where('status', 'active');
            $users = $builder->take(1000)->get();
            $facets = [];

            foreach ($users as $user) {
                $department = $user->department;
                if ($department) {
                    $facets[$department] = ($facets[$department] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get role facets.
     *
     * @return array<string, int>
     */
    public function getRoleFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':role_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $builder = User::search($query)->where('status', 'active');
            $users = $builder->take(1000)->get();
            $facets = [];

            foreach ($users as $user) {
                $role = $user->getRole();
                if ($role) {
                    $facets[$role] = ($facets[$role] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get organization facets.
     *
     * @return array<string, int>
     */
    public function getOrganizationFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':organization_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $builder = User::search($query)->where('status', 'active');
            $users = $builder->take(1000)->get();
            $facets = [];

            foreach ($users as $user) {
                $orgName = $user->organization?->getName();
                if ($orgName) {
                    $facets[$orgName] = ($facets[$orgName] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get recently joined users.
     *
     * @return Collection<int, User>
     */
    public function getRecentlyJoined(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':recently_joined:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return User::search('')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Search verified users only.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function searchVerified(
        string $query = '',
        array $filters = [],
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['email_verified'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Search users with MFA enabled.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function searchWithMFA(
        string $query = '',
        array $filters = [],
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['mfa_enabled'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Log;
use Modules\Organization\Application\ReadModel\OrganizationDashboardReadModel;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Application\ReadModel\AbstractReadModelRepository;
use Modules\Shared\Application\ReadModel\ReadModelInterface;
use Modules\Shared\Application\Service\CacheService;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Repository for Organization Dashboard Read Models with caching.
 */
class OrganizationDashboardRepository extends AbstractReadModelRepository
{
    public function __construct(
        CacheRepository $cache,
        private readonly CacheService $cacheService,
    ) {
        parent::__construct($cache);
        $this->defaultCacheTtl = config('read-models.caching.ttl.organization_dashboard', 3600);
        $this->cachingEnabled = config('read-models.repositories.organization_dashboard.cache_enabled', true);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function buildReadModel(string|int $id, ?array $filters = null): ?ReadModelInterface
    {
        $organizationId = (int) $id;

        $data = $this->buildOrganizationDashboardData($organizationId);

        if ($data === []) {
            return null;
        }

        return new OrganizationDashboardReadModel(
            $organizationId,
            $data,
            (string) time()
        );
    }

    /**
     * @param  array<string|int>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<int, OrganizationDashboardReadModel>
     */
    protected function buildReadModels(array $ids, ?array $filters = null): array
    {
        $organizationIds = array_map('intval', $ids);
        $results = [];

        foreach ($organizationIds as $organizationId) {
            $data = $this->buildOrganizationDashboardData($organizationId);

            if ($data !== []) {
                $results[] = new OrganizationDashboardReadModel(
                    $organizationId,
                    $data,
                    (string) time()
                );
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<ReadModelInterface>
     */
    protected function buildAllReadModels(?array $filters = null, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->getBaseQuery();
        $this->applyFilters($query, $filters);

        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }

        $organizations = $query->get();
        $results = [];

        foreach ($organizations as $organization) {
            $data = $this->buildOrganizationDashboardData($organization->id);

            if ($data !== []) {
                $results[] = new OrganizationDashboardReadModel(
                    $organization->id,
                    $data,
                    (string) time()
                );
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function buildCount(?array $filters = null): int
    {
        $query = $this->getBaseQuery();
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    protected function getCachePrefix(): string
    {
        return 'organization_dashboard';
    }

    /**
     * @return array<string>
     */
    protected function getDefaultCacheTags(): array
    {
        return ['organization_dashboard', 'organizations', 'campaigns', 'donations'];
    }

    /**
     * @return Builder<Organization>
     */
    protected function getBaseQuery(): Builder
    {
        return Organization::query();
    }

    /**
     * Build comprehensive dashboard data for an organization with advanced caching.
     *
     * @return array<string, mixed>
     */
    private function buildOrganizationDashboardData(int $organizationId): array
    {
        // Use cached organization metrics with intelligent cache segmentation
        return $this->cacheService->rememberOrganizationMetrics($organizationId);
    }

    /**
     * Load comprehensive dashboard data for an organization.
     *
     * @return array<string, mixed>
     */
    public function loadOrganizationDashboardData(int $organizationId): array
    {
        // Base organization data
        $organization = Organization::find($organizationId);

        if (! $organization) {
            return [];
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // Combined employee statistics in a single query
        $employeeStatsQuery = DB::table('users')
            ->leftJoin('model_has_roles', function ($join): void {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('users.organization_id', $organizationId)
            ->selectRaw('
                COUNT(DISTINCT users.id) as total_employees,
                SUM(CASE WHEN users.status = "active" THEN 1 ELSE 0 END) as active_employees,
                SUM(CASE WHEN users.created_at >= ? THEN 1 ELSE 0 END) as new_employees_this_month,
                COUNT(DISTINCT CASE WHEN roles.name IN ("admin", "organization_admin", "super_admin") THEN users.id END) as admin_employees
            ', [$startOfMonth])
            ->first();

        // Employees by department in separate query (only if department field exists)
        $employeesByDepartment = DB::table('users')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNotNull('department')
            ->groupBy('department')
            ->selectRaw('department, COUNT(*) as count')
            ->pluck('count', 'department')
            ->toArray();

        // Combined campaign and financial statistics in a single query
        $campaignFinancialStats = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->selectRaw('
                COUNT(*) as total_campaigns,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_campaigns,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_campaigns,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_campaigns,
                SUM(CASE WHEN status = "pending_approval" THEN 1 ELSE 0 END) as pending_approval_campaigns,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_campaigns,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as campaigns_created_this_month,
                AVG(DATEDIFF(end_date, start_date)) as average_campaign_duration,
                SUM(goal_amount) as total_fundraising_goal,
                SUM(current_amount) as total_amount_raised,
                SUM(CASE WHEN created_at >= ? THEN current_amount ELSE 0 END) as amount_raised_this_month,
                SUM(CASE WHEN created_at >= ? THEN current_amount ELSE 0 END) as amount_raised_this_year
            ', [$startOfMonth, $startOfMonth, $startOfYear])
            ->first();

        // Campaigns by category (separate query due to GROUP BY complexity)
        $campaignsByCategory = DB::table('campaigns')
            ->leftJoin('categories', 'campaigns.category_id', '=', 'categories.id')
            ->where('campaigns.organization_id', $organizationId)
            ->groupBy('categories.id')
            ->selectRaw('MAX(categories.name) as category_name, COUNT(*) as count')
            ->pluck('count', 'category_name')
            ->toArray();

        // Combined donation statistics and corporate matching in a single query
        $donationStats = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->selectRaw('
                COUNT(*) as total_donations,
                COUNT(DISTINCT donations.user_id) as total_unique_donors,
                SUM(CASE WHEN donations.created_at >= ? THEN 1 ELSE 0 END) as donations_this_month,
                COUNT(DISTINCT CASE WHEN donations.created_at >= ? THEN donations.user_id END) as new_donors_this_month,
                SUM(CASE WHEN donations.recurring = 1 THEN 1 ELSE 0 END) as recurring_donations,
                SUM(CASE WHEN donations.recurring = 1 THEN donations.amount ELSE 0 END) as recurring_donation_amount,
                SUM(CASE WHEN donations.status = "completed" THEN COALESCE(donations.corporate_match_amount, 0) ELSE 0 END) as total_corporate_match_amount
            ', [$startOfMonth, $startOfMonth])
            ->first();

        // Donor retention analysis
        $donorRetention = $this->calculateDonorRetention($organizationId);

        // Top performing campaigns
        $topCampaigns = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->where('status', '!=', 'draft')
            ->orderByRaw('(current_amount / NULLIF(goal_amount, 0)) DESC')
            ->limit(5)
            ->select('id', 'title', 'current_amount', 'goal_amount', 'status')
            ->get()
            ->toArray();

        // Most productive employees (by campaigns created and amount raised)
        $productiveEmployees = DB::table('campaigns')
            ->join('users', 'campaigns.user_id', '=', 'users.id')
            ->where('campaigns.organization_id', $organizationId)
            ->groupBy('users.id')
            ->selectRaw('
                users.id,
                MAX(users.name) as name,
                COUNT(*) as campaigns_created,
                SUM(campaigns.current_amount) as total_raised
            ')
            ->orderByDesc('total_raised')
            ->limit(5)
            ->get()
            ->toArray();

        // Monthly fundraising trend (last 12 months)
        $monthlyTrend = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->where('donations.status', 'completed')
            ->where('donations.created_at', '>=', $now->copy()->subMonths(12))
            ->groupBy(DB::raw('CONCAT(YEAR(donations.created_at), "-", LPAD(MONTH(donations.created_at), 2, "0"))'))
            ->selectRaw('
                CONCAT(YEAR(donations.created_at), "-", LPAD(MONTH(donations.created_at), 2, "0")) as month,
                SUM(donations.amount) as amount,
                COUNT(*) as count
            ')
            ->orderBy('month')
            ->get()
            ->toArray();

        // Campaign creation trend
        $campaignCreationTrend = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->where('created_at', '>=', $now->copy()->subMonths(12))
            ->groupBy(DB::raw('CONCAT(YEAR(created_at), "-", LPAD(MONTH(created_at), 2, "0"))'))
            ->selectRaw('
                CONCAT(YEAR(created_at), "-", LPAD(MONTH(created_at), 2, "0")) as month,
                COUNT(*) as count
            ')
            ->orderBy('month')
            ->get()
            ->toArray();

        // Engagement metrics (placeholder until views/shares tracking is implemented)
        $engagementStats = (object) [
            'total_campaign_views' => 0,
            'total_campaign_shares' => 0,
        ];

        // Bookmarks count
        $bookmarksCount = DB::table('bookmarks')
            ->join('campaigns', 'bookmarks.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->count();

        // Recent campaigns
        $recentCampaigns = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->select('id', 'title', 'status', 'current_amount', 'goal_amount', 'created_at')
            ->get()
            ->toArray();

        // Recent donations
        $recentDonations = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->leftJoin('users', 'donations.user_id', '=', 'users.id')
            ->where('campaigns.organization_id', $organizationId)
            ->orderByDesc('donations.created_at')
            ->limit(10)
            ->select(
                'donations.id',
                'donations.amount',
                'donations.status',
                'donations.anonymous',
                'donations.created_at',
                'campaigns.title as campaign_title',
                'users.name as donor_name'
            )
            ->get()
            ->toArray();

        // Upcoming campaign deadlines
        $upcomingDeadlines = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->where('end_date', '>', $now)
            ->where('end_date', '<=', $now->copy()->addDays(30))
            ->orderBy('end_date')
            ->select('id', 'title', 'end_date', 'current_amount', 'goal_amount')
            ->get()
            ->toArray();

        // Calculate performance metrics
        $averageCampaignSuccessRate = $this->calculateAverageCampaignSuccessRate($organizationId);
        $campaignCreationVelocity = $this->calculateCampaignCreationVelocity($organizationId);
        $fundraisingVelocity = $this->calculateFundraisingVelocity($organizationId);

        return [
            // Organization basic info
            'name' => $organization->getName(),
            'description' => $organization->description,
            'website' => $organization->website,
            'logo_url' => $organization->logo_url,
            'status' => $organization->status,
            'type' => $organization->type,
            'country' => $organization->country,
            'city' => $organization->city,
            'is_verified' => $organization->is_verified,
            'verified_at' => $organization->verification_date?->toISOString(),

            // Employee statistics
            'total_employees' => (int) ($employeeStatsQuery->total_employees ?? 0),
            'active_employees' => (int) ($employeeStatsQuery->active_employees ?? 0),
            'admin_employees' => (int) ($employeeStatsQuery->admin_employees ?? 0),
            'new_employees_this_month' => (int) ($employeeStatsQuery->new_employees_this_month ?? 0),
            'employees_by_department' => $employeesByDepartment,

            // Campaign statistics
            'total_campaigns' => (int) ($campaignFinancialStats->total_campaigns ?? 0),
            'active_campaigns' => (int) ($campaignFinancialStats->active_campaigns ?? 0),
            'completed_campaigns' => (int) ($campaignFinancialStats->completed_campaigns ?? 0),
            'draft_campaigns' => (int) ($campaignFinancialStats->draft_campaigns ?? 0),
            'pending_approval_campaigns' => (int) ($campaignFinancialStats->pending_approval_campaigns ?? 0),
            'rejected_campaigns' => (int) ($campaignFinancialStats->rejected_campaigns ?? 0),
            'campaigns_created_this_month' => (int) ($campaignFinancialStats->campaigns_created_this_month ?? 0),
            'campaigns_by_category' => $campaignsByCategory,
            'average_campaign_duration' => (float) ($campaignFinancialStats->average_campaign_duration ?? 0),

            // Financial overview
            'total_fundraising_goal' => (float) ($campaignFinancialStats->total_fundraising_goal ?? 0),
            'total_amount_raised' => (float) ($campaignFinancialStats->total_amount_raised ?? 0),
            'total_amount_raised_this_month' => (float) ($campaignFinancialStats->amount_raised_this_month ?? 0),
            'total_amount_raised_this_year' => (float) ($campaignFinancialStats->amount_raised_this_year ?? 0),
            'total_corporate_match_amount' => (float) ($donationStats->total_corporate_match_amount ?? 0),

            // Donation statistics
            'total_donations' => (int) ($donationStats->total_donations ?? 0),
            'total_unique_donors' => (int) ($donationStats->total_unique_donors ?? 0),
            'donations_this_month' => (int) ($donationStats->donations_this_month ?? 0),
            'new_donors_this_month' => (int) ($donationStats->new_donors_this_month ?? 0),
            'recurring_donations' => (int) ($donationStats->recurring_donations ?? 0),
            'recurring_donation_amount' => (float) ($donationStats->recurring_donation_amount ?? 0),
            'donor_retention_rate' => $donorRetention,

            // Performance metrics
            'average_campaign_success_rate' => $averageCampaignSuccessRate,
            'campaign_creation_velocity' => $campaignCreationVelocity,
            'fundraising_velocity' => $fundraisingVelocity,
            'top_performing_campaigns' => $topCampaigns,
            'most_productive_employees' => $productiveEmployees,

            // Engagement metrics
            'total_campaign_views' => (int) ($engagementStats->total_campaign_views ?? 0),
            'total_campaign_shares' => (int) ($engagementStats->total_campaign_shares ?? 0),
            'total_campaign_bookmarks' => $bookmarksCount,

            // Trends
            'monthly_fundraising_trend' => $monthlyTrend,
            'monthly_campaign_creation_trend' => $campaignCreationTrend,

            // Recent activity
            'recent_campaigns' => $recentCampaigns,
            'recent_donations' => $recentDonations,
            'upcoming_deadlines' => $upcomingDeadlines,

            // Subscription & compliance (placeholder)
            'has_active_subscription' => true,
            'subscription_tier' => 'enterprise',
            'subscription_expires_at' => null,
            'api_usage' => [],
            'storage_used' => 0,
            'storage_limit' => 1000000000, // 1GB

            // Timestamps
            'created_at' => $organization->created_at?->toISOString(),
            'updated_at' => $organization->updated_at?->toISOString(),
            'last_login_at' => null, // To be implemented
            'last_activity_at' => null, // To be implemented
        ];
    }

    /**
     * Calculate donor retention rate for organization.
     */
    private function calculateDonorRetention(int $organizationId): float
    {
        $donorStats = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->where('donations.status', 'completed')
            ->whereNotNull('donations.user_id')
            ->groupBy('donations.user_id')
            ->selectRaw('donations.user_id, COUNT(*) as donation_count')
            ->get();

        $totalDonors = $donorStats->count();
        $returningDonors = $donorStats->where('donation_count', '>', 1)->count();

        if ($totalDonors === 0) {
            return 0.0;
        }

        return ($returningDonors / $totalDonors) * 100;
    }

    /**
     * Calculate average campaign success rate.
     */
    private function calculateAverageCampaignSuccessRate(int $organizationId): float
    {
        $campaigns = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->where('status', '!=', 'draft')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN current_amount >= goal_amount THEN 1 ELSE 0 END) as successful
            ')
            ->first();

        if (! $campaigns || ! isset($campaigns->total) || (int) $campaigns->total === 0) {
            return 0.0;
        }

        if (! isset($campaigns->successful)) {
            return 0.0;
        }

        return ((int) $campaigns->successful / (int) $campaigns->total) * 100;
    }

    /**
     * Calculate campaign creation velocity (campaigns per month).
     */
    private function calculateCampaignCreationVelocity(int $organizationId): float
    {
        $firstCampaign = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->min('created_at');

        if (! $firstCampaign) {
            return 0.0;
        }

        $monthsSinceFirst = (int) now()->diffInMonths($firstCampaign);
        if ($monthsSinceFirst === 0) {
            $monthsSinceFirst = 1;
        }

        $totalCampaigns = DB::table('campaigns')
            ->where('organization_id', $organizationId)
            ->count();

        return $totalCampaigns / $monthsSinceFirst;
    }

    /**
     * Calculate fundraising velocity (amount per month).
     */
    private function calculateFundraisingVelocity(int $organizationId): float
    {
        $firstDonation = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->where('donations.status', 'completed')
            ->min('donations.created_at');

        if (! $firstDonation) {
            return 0.0;
        }

        $monthsSinceFirst = (int) now()->diffInMonths($firstDonation);
        if ($monthsSinceFirst === 0) {
            $monthsSinceFirst = 1;
        }

        $totalRaised = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->where('donations.status', 'completed')
            ->sum('donations.amount');

        return $totalRaised / $monthsSinceFirst;
    }

    /**
     * Warm cache for multiple organizations to prevent N+1 cache misses.
     *
     * @param  array<int>  $organizationIds
     */
    public function warmCacheForOrganizations(array $organizationIds): void
    {
        foreach ($organizationIds as $organizationId) {
            try {
                $this->cacheService->rememberOrganizationMetrics($organizationId);
            } catch (Exception $e) {
                // Log but continue warming other organizations
                Log::warning('Failed to warm cache for organization', [
                    'organization_id' => $organizationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Invalidate cache for specific organization.
     */
    public function invalidateOrganizationCache(int $organizationId): void
    {
        $this->cacheService->invalidateOrganization($organizationId);

        // Also clear read model cache
        $this->clearCache([
            'organization_dashboard',
            'organizations',
            "org:{$organizationId}",
        ]);
    }

    /**
     * Get cache statistics for dashboard data.
     *
     * @return array<string, mixed>
     */
    public function getCacheStatistics(int $organizationId): array
    {
        $key = "dashboard:org:{$organizationId}:metrics";
        $tags = ['dashboard', 'organizations', "org:{$organizationId}"];

        return [
            'cache_key' => $key,
            'cache_tags' => $tags,
            'cached' => $this->cache->has($key),
            'ttl_remaining' => $this->getRemainingTtl($key),
        ];
    }

    /**
     * Pre-load frequently accessed organization dashboards.
     */
    public function preloadPopularDashboards(): void
    {
        // Get most active organizations (by campaign count)
        $popularOrganizations = DB::table('organizations')
            ->join('campaigns', 'organizations.id', '=', 'campaigns.organization_id')
            ->where('campaigns.status', 'active')
            ->groupBy('organizations.id')
            ->havingRaw('COUNT(campaigns.id) >= 5')
            ->orderByRaw('COUNT(campaigns.id) DESC')
            ->limit(20)
            ->pluck('organizations.id')
            ->toArray();

        $this->warmCacheForOrganizations($popularOrganizations);
    }

    /**
     * Get remaining TTL for a cache key.
     */
    private function getRemainingTtl(string $key): ?int
    {
        try {
            // This is Redis-specific, would need different implementation for other cache drivers
            if (config('cache.default') === 'redis') {
                $redis = app('redis')->connection('cache');

                return $redis->ttl($key);
            }

            return null;
        } catch (Exception) {
            return null;
        }
    }
}

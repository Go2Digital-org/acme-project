<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Log;
use Modules\Campaign\Application\ReadModel\CampaignAnalyticsReadModel;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Application\ReadModel\AbstractReadModelRepository;
use Modules\Shared\Application\ReadModel\ReadModelInterface;
use Modules\Shared\Application\Service\CacheService;

/**
 * Repository for Campaign Analytics Read Models with caching.
 */
class CampaignAnalyticsRepository extends AbstractReadModelRepository
{
    public function __construct(
        CacheRepository $cache,
        private readonly CacheService $cacheService,
    ) {
        parent::__construct($cache);
        $this->defaultCacheTtl = config('read-models.caching.ttl.campaign_analytics', 1800);
        $this->cachingEnabled = config('read-models.repositories.campaign_analytics.cache_enabled', true);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function buildReadModel(string|int $id, ?array $filters = null): ?ReadModelInterface
    {
        $campaignId = (int) $id;

        $data = $this->buildCampaignAnalyticsData($campaignId);

        if ($data === []) {
            return null;
        }

        return new CampaignAnalyticsReadModel(
            $campaignId,
            $data,
            (string) time() // Version based on current time for analytics
        );
    }

    /**
     * @param  array<int|string>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<int|string, CampaignAnalyticsReadModel>
     */
    public function buildReadModels(array $ids, ?array $filters = null): array
    {
        $campaignIds = array_map('intval', $ids);
        $results = [];

        // Bulk load campaign data with relationships
        $campaigns = Campaign::with(['organization', 'creator', 'categoryModel'])
            ->withCount(['donations', 'bookmarks'])
            ->whereIn('id', $campaignIds)
            ->get()
            ->keyBy('id');

        // Bulk load donation statistics for all campaigns
        $bulkDonationStats = $this->getBulkDonationStats($campaignIds);

        foreach ($campaignIds as $campaignId) {
            $campaign = $campaigns->get($campaignId);
            if (! $campaign) {
                continue;
            }

            $donationStats = $bulkDonationStats[$campaignId] ?? null;
            $data = $this->buildCampaignAnalyticsDataFromLoaded($campaign, $donationStats);

            if ($data !== []) {
                $results[(string) $campaignId] = new CampaignAnalyticsReadModel(
                    $campaignId,
                    $data,
                    (string) time()
                );
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<CampaignAnalyticsReadModel>
     */
    public function buildAllReadModels(?array $filters = null, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->getBaseQuery();
        $this->applyFilters($query, $filters);

        if ($limit) {
            $query->limit($limit);
        }

        if ($offset) {
            $query->offset($offset);
        }

        $campaigns = $query->get();
        $campaignIds = $campaigns->pluck('id')->toArray();

        // Bulk load donation statistics for all campaigns
        $bulkDonationStats = $this->getBulkDonationStats($campaignIds);

        $results = [];

        foreach ($campaigns as $campaign) {
            $donationStats = $bulkDonationStats[$campaign->id] ?? null;
            $data = $this->buildCampaignAnalyticsDataFromLoaded($campaign, $donationStats);

            if ($data !== []) {
                $results[] = new CampaignAnalyticsReadModel(
                    $campaign->id,
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
        return 'campaign_analytics';
    }

    /**
     * @return array<string>
     */
    protected function getDefaultCacheTags(): array
    {
        return ['campaign_analytics', 'campaigns', 'donations'];
    }

    /**
     * @return Builder<Campaign>
     */
    protected function getBaseQuery(): Builder
    {
        return Campaign::query()
            ->with(['organization', 'creator', 'categoryModel'])
            ->withCount(['donations', 'bookmarks']);
    }

    /**
     * Build comprehensive analytics data for a campaign with advanced caching.
     *
     * @return array<string, mixed>
     */
    private function buildCampaignAnalyticsData(int $campaignId): array
    {
        // Use cached campaign analytics with intelligent invalidation
        return $this->cacheService->rememberCampaignAnalytics($campaignId);
    }

    /**
     * Load comprehensive analytics data for a campaign.
     *
     * @return array<string, mixed>
     */
    public function loadCampaignAnalyticsData(int $campaignId): array
    {
        // Base campaign data
        $campaign = Campaign::with(['organization', 'creator', 'categoryModel'])
            ->withCount(['donations', 'bookmarks'])
            ->find($campaignId);

        if (! $campaign) {
            return [];
        }

        // Donation statistics
        $donationStats = DB::table('donations')
            ->where('campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total_donations,
                COUNT(DISTINCT user_id) as unique_donors,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                SUM(CASE WHEN anonymous = 1 THEN 1 ELSE 0 END) as anonymous_donations,
                SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_donations,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_donations,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_donations,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_donations,
                SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_donations,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed_amount,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                SUM(COALESCE(corporate_match_amount, 0)) as corporate_match_amount
            ')
            ->first();

        // Time-based analytics
        $now = now();
        $startDate = $campaign->start_date;
        $endDate = $campaign->end_date;

        $daysActive = max(0, $startDate?->diffInDays($now->min($endDate ?? $now)) ?? 0);
        $daysRemaining = max(0, $now->diffInDays($endDate ?? $now, false));
        $campaignDuration = $startDate?->diffInDays($endDate ?? $startDate ?? $now) ?? 0;

        // Combined payment statistics (gateway and method in separate queries due to different GROUP BY)
        $gatewayStats = DB::table('donations')
            ->where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->groupBy('payment_gateway')
            ->selectRaw('
                payment_gateway,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ')
            ->get()
            ->keyBy('payment_gateway')
            ->toArray();

        $methodStats = DB::table('donations')
            ->where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->get()
            ->keyBy('payment_method')
            ->toArray();

        // Donation trends in separate queries due to different time groupings
        $dailyDonations = DB::table('donations')
            ->where('campaign_id', $campaignId)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->orderBy('date')
            ->get()
            ->toArray();

        $weeklyDonations = DB::table('donations')
            ->where('campaign_id', $campaignId)
            ->where('created_at', '>=', $now->copy()->subWeeks(12))
            ->groupBy(DB::raw('CONCAT(YEAR(created_at), "-W", LPAD(WEEK(created_at), 2, "0"))'))
            ->selectRaw('
                CONCAT(YEAR(created_at), "-W", LPAD(WEEK(created_at), 2, "0")) as week,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->orderBy('week')
            ->get()
            ->toArray();

        // Build comprehensive analytics data
        return [
            // Campaign basic info
            'title' => $campaign->getTitle(),
            'status' => $campaign->status->value,
            'visibility' => $campaign->visibility,
            'organization_id' => $campaign->organization_id,
            'organization_name' => $campaign->organization->getName(),
            'user_id' => $campaign->user_id,
            'creator_name' => $campaign->creator->getName(),
            'category_id' => $campaign->category_id,
            'category_name' => $campaign->categoryModel?->getName(),

            // Financial metrics
            'goal_amount' => (float) $campaign->goal_amount,
            'current_amount' => (float) $campaign->current_amount,
            'progress_percentage' => $campaign->goal_amount > 0 ? min(100.0, ((float) $campaign->current_amount / (float) $campaign->goal_amount) * 100) : 0.0,
            'corporate_match_amount' => (float) ($donationStats->corporate_match_amount ?? 0),

            // Time metrics
            'start_date' => $startDate?->toISOString(),
            'end_date' => $endDate?->toISOString(),
            'days_remaining' => $daysRemaining,
            'days_active' => $daysActive,
            'campaign_duration' => $campaignDuration,
            'is_active' => $campaign->isActive(),

            // Donation metrics
            'total_donations' => (int) ($donationStats->total_donations ?? 0),
            'unique_donors' => (int) ($donationStats->unique_donors ?? 0),
            'total_amount' => (float) ($donationStats->total_amount ?? 0),
            'anonymous_donations' => (int) ($donationStats->anonymous_donations ?? 0),
            'recurring_donations' => (int) ($donationStats->recurring_donations ?? 0),
            'completed_donations' => (int) ($donationStats->completed_donations ?? 0),
            'pending_donations' => (int) ($donationStats->pending_donations ?? 0),
            'failed_donations' => (int) ($donationStats->failed_donations ?? 0),
            'refunded_donations' => (int) ($donationStats->refunded_donations ?? 0),
            'refunded_amount' => (float) ($donationStats->refunded_amount ?? 0),

            // Payment statistics
            'payment_gateway_stats' => $gatewayStats,
            'payment_method_stats' => $methodStats,

            // Time-based analytics
            'donations_by_day' => $dailyDonations,
            'donations_by_week' => $weeklyDonations,

            // Engagement metrics
            'bookmarks_count' => $campaign->bookmarks_count ?? 0,
            'shares_count' => 0, // To be implemented
            'views_count' => 0, // To be implemented

            // Timestamps
            'created_at' => $campaign->created_at?->toISOString(),
            'updated_at' => $campaign->updated_at?->toISOString(),
            'completed_at' => $campaign->completed_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<CampaignAnalyticsReadModel>
     */
    public function findAll(?array $filters = null, ?int $limit = null, ?int $offset = null): array
    {
        $results = parent::findAll($filters, $limit, $offset);

        /** @var array<CampaignAnalyticsReadModel> $typedResults */
        $typedResults = array_filter($results, fn (ReadModelInterface $item): bool => $item instanceof CampaignAnalyticsReadModel);

        return array_values($typedResults);
    }

    /**
     * Find analytics for campaigns by organization.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<CampaignAnalyticsReadModel>
     */
    public function findByOrganization(int $organizationId, ?array $filters = null): array
    {
        $filters ??= [];
        $filters['organization_id'] = $organizationId;

        return $this->findAll($filters);
    }

    /**
     * Find analytics for top performing campaigns.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<CampaignAnalyticsReadModel>
     */
    public function findTopPerforming(int $limit = 10, ?array $filters = null): array
    {
        $query = $this->getBaseQuery();
        $this->applyFilters($query, $filters);

        $campaigns = $query
            ->orderByRaw('(current_amount / goal_amount) DESC')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($campaigns as $campaign) {
            $data = $this->buildCampaignAnalyticsData($campaign->id);
            if ($data !== []) {
                $results[] = new CampaignAnalyticsReadModel(
                    $campaign->id,
                    $data,
                    (string) time()
                );
            }
        }

        return $results;
    }

    /**
     * Clear cache for campaigns by organization.
     */
    public function clearCacheForOrganization(int $organizationId): bool
    {
        return $this->clearCache([
            'campaign_analytics',
            'campaigns',
            'organization:' . $organizationId,
        ]);
    }

    /**
     * Bulk load donation statistics for multiple campaigns to prevent N+1 queries.
     *
     * @param  array<int>  $campaignIds
     * @return array<int, object>
     */
    private function getBulkDonationStats(array $campaignIds): array
    {
        if ($campaignIds === []) {
            return [];
        }

        return DB::table('donations')
            ->whereIn('campaign_id', $campaignIds)
            ->selectRaw('
                campaign_id,
                COUNT(*) as total_donations,
                COUNT(DISTINCT user_id) as unique_donors,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                SUM(CASE WHEN anonymous = 1 THEN 1 ELSE 0 END) as anonymous_donations,
                SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_donations,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_donations,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_donations,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_donations,
                SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_donations,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed_amount,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                SUM(COALESCE(corporate_match_amount, 0)) as corporate_match_amount
            ')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id')
            ->toArray();
    }

    /**
     * Build analytics data from already loaded campaign and donation stats.
     *
     * @return array<string, mixed>
     */
    private function buildCampaignAnalyticsDataFromLoaded(Campaign $campaign, ?object $donationStats): array
    {
        $now = now();
        $startDate = $campaign->start_date;
        $endDate = $campaign->end_date;

        $daysActive = max(0, $startDate?->diffInDays($now->min($endDate ?? $now)) ?? 0);
        $daysRemaining = max(0, $now->diffInDays($endDate ?? $now, false));
        $campaignDuration = $startDate?->diffInDays($endDate ?? $startDate ?? $now) ?? 0;

        return [
            // Campaign basic info
            'title' => $campaign->getTitle(),
            'status' => $campaign->status->value,
            'visibility' => $campaign->visibility,
            'organization_id' => $campaign->organization_id,
            'organization_name' => $campaign->organization->getName(),
            'user_id' => $campaign->user_id,
            'creator_name' => $campaign->creator->getName(),
            'category_id' => $campaign->category_id,
            'category_name' => $campaign->categoryModel?->getName(),

            // Financial metrics
            'goal_amount' => (float) $campaign->goal_amount,
            'current_amount' => (float) $campaign->current_amount,
            'progress_percentage' => $campaign->goal_amount > 0 ? min(100.0, ((float) $campaign->current_amount / (float) $campaign->goal_amount) * 100) : 0.0,
            'corporate_match_amount' => (float) ($donationStats->corporate_match_amount ?? 0),

            // Time metrics
            'start_date' => $startDate?->toISOString(),
            'end_date' => $endDate?->toISOString(),
            'days_remaining' => $daysRemaining,
            'days_active' => $daysActive,
            'campaign_duration' => $campaignDuration,
            'is_active' => $campaign->isActive(),

            // Donation metrics
            'total_donations' => (int) ($donationStats->total_donations ?? 0),
            'unique_donors' => (int) ($donationStats->unique_donors ?? 0),
            'total_amount' => (float) ($donationStats->total_amount ?? 0),
            'anonymous_donations' => (int) ($donationStats->anonymous_donations ?? 0),
            'recurring_donations' => (int) ($donationStats->recurring_donations ?? 0),
            'completed_donations' => (int) ($donationStats->completed_donations ?? 0),
            'pending_donations' => (int) ($donationStats->pending_donations ?? 0),
            'failed_donations' => (int) ($donationStats->failed_donations ?? 0),
            'refunded_donations' => (int) ($donationStats->refunded_donations ?? 0),
            'refunded_amount' => (float) ($donationStats->refunded_amount ?? 0),

            // Payment statistics (simplified for bulk loading)
            'payment_gateway_stats' => [],
            'payment_method_stats' => [],

            // Time-based analytics (simplified for bulk loading)
            'donations_by_day' => [],
            'donations_by_week' => [],

            // Engagement metrics
            'bookmarks_count' => $campaign->bookmarks_count ?? 0,
            'shares_count' => 0, // To be implemented
            'views_count' => 0, // To be implemented

            // Timestamps
            'created_at' => $campaign->created_at?->toISOString(),
            'updated_at' => $campaign->updated_at?->toISOString(),
            'completed_at' => $campaign->completed_at?->toISOString(),
        ];
    }

    /**
     * Warm cache for multiple campaigns to prevent N+1 cache misses.
     *
     * @param  array<int>  $campaignIds
     */
    public function warmCacheForCampaigns(array $campaignIds): void
    {
        foreach ($campaignIds as $campaignId) {
            try {
                $this->cacheService->rememberCampaignAnalytics($campaignId);
            } catch (Exception $e) {
                // Log but continue warming other campaigns
                Log::warning('Failed to warm cache for campaign analytics', [
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Warm cache for top performing campaigns.
     */
    public function warmTopPerformingCampaigns(int $limit = 50): void
    {
        $topCampaignIds = DB::table('campaigns')
            ->where('status', '!=', 'draft')
            ->orderByRaw('(current_amount / NULLIF(goal_amount, 0)) DESC')
            ->limit($limit)
            ->pluck('id')
            ->toArray();

        $this->warmCacheForCampaigns($topCampaignIds);
    }

    /**
     * Warm cache for recently active campaigns.
     */
    public function warmRecentlyActiveCampaigns(int $days = 7, int $limit = 100): void
    {
        $recentCampaignIds = DB::table('campaigns')
            ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->where('donations.created_at', '>=', now()->subDays($days))
            ->where('donations.status', 'completed')
            ->groupBy('campaigns.id')
            ->orderByRaw('COUNT(donations.id) DESC')
            ->limit($limit)
            ->pluck('campaigns.id')
            ->toArray();

        $this->warmCacheForCampaigns($recentCampaignIds);
    }

    /**
     * Invalidate cache for specific campaign.
     */
    public function invalidateCampaignCache(int $campaignId, ?int $organizationId = null): void
    {
        $this->cacheService->invalidateCampaign($campaignId, $organizationId);

        // Also clear read model cache
        $this->clearCache([
            'campaign_analytics',
            'campaigns',
            "campaign:{$campaignId}",
        ]);
    }

    /**
     * Bulk invalidate cache for multiple campaigns.
     *
     * @param  array<int>  $campaignIds
     */
    public function bulkInvalidateCampaignCache(array $campaignIds): void
    {
        foreach ($campaignIds as $campaignId) {
            $this->invalidateCampaignCache($campaignId);
        }
    }

    /**
     * Get cache statistics for campaign analytics.
     *
     * @return array<string, mixed>
     */
    public function getCacheStatistics(int $campaignId): array
    {
        $key = "analytics:campaign:{$campaignId}";
        $tags = ['campaign_analytics', 'campaigns', "campaign:{$campaignId}"];

        return [
            'cache_key' => $key,
            'cache_tags' => $tags,
            'cached' => $this->cache->has($key),
            'read_model_cached' => $this->find($campaignId) instanceof ReadModelInterface,
        ];
    }

    /**
     * Pre-load analytics for trending campaigns.
     */
    public function preloadTrendingCampaigns(): void
    {
        // Get campaigns with highest donation velocity in last 24 hours
        $trendingCampaignIds = DB::table('campaigns')
            ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->where('donations.created_at', '>=', now()->subDay())
            ->where('donations.status', 'completed')
            ->groupBy('campaigns.id')
            ->havingRaw('COUNT(donations.id) >= 3')
            ->orderByRaw('SUM(donations.amount) DESC')
            ->limit(30)
            ->pluck('campaigns.id')
            ->toArray();

        $this->warmCacheForCampaigns($trendingCampaignIds);
    }

    /**
     * Pre-load analytics for ending soon campaigns.
     */
    public function preloadEndingSoonCampaigns(): void
    {
        $endingSoonCampaignIds = DB::table('campaigns')
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->where('end_date', '<=', now()->addDays(7))
            ->orderBy('end_date')
            ->limit(50)
            ->pluck('id')
            ->toArray();

        $this->warmCacheForCampaigns($endingSoonCampaignIds);
    }

    /**
     * Enhanced bulk analytics loading with optimized queries.
     *
     * @param  array<int>  $campaignIds
     * @return array<int, array<string, mixed>>
     */
    public function getBulkAnalytics(array $campaignIds): array
    {
        if ($campaignIds === []) {
            return [];
        }

        $results = [];
        $uncachedIds = [];

        // First, try to get from cache
        foreach ($campaignIds as $campaignId) {
            $cached = $this->find($campaignId);
            if ($cached instanceof ReadModelInterface) {
                $results[$campaignId] = $cached->toArray();
            } else {
                $uncachedIds[] = $campaignId;
            }
        }

        // Load uncached data using optimized bulk queries
        if ($uncachedIds !== []) {
            $bulkData = $this->loadBulkAnalyticsData($uncachedIds);
            foreach ($bulkData as $campaignId => $data) {
                $results[$campaignId] = $data;

                // Cache the result for future use
                $this->cacheService->rememberWithTtl(
                    "analytics:campaign:{$campaignId}",
                    fn () => $data,
                    1800, // 30 minutes
                    ['campaign_analytics', 'campaigns', "campaign:{$campaignId}"]
                );
            }
        }

        return $results;
    }

    /**
     * Load analytics data for multiple campaigns using optimized queries.
     *
     * @param  array<int>  $campaignIds
     * @return array<int, array<string, mixed>>
     */
    private function loadBulkAnalyticsData(array $campaignIds): array
    {
        // Use the existing buildReadModels method which is already optimized
        $readModels = $this->buildReadModels($campaignIds);

        $results = [];
        foreach ($readModels as $readModel) {
            if ($readModel instanceof CampaignAnalyticsReadModel) {
                $results[(int) $readModel->getEntityId()] = $readModel->getData();
            }
        }

        return $results;
    }
}

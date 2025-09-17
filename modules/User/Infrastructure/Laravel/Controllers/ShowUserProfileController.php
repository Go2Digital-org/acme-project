<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Dashboard\Application\Service\UserDashboardService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

/**
 * Show User Profile Controller.
 *
 * Displays user profile with dashboard data and campaign information.
 * Uses async cache system for non-blocking dashboard data loading.
 */
final readonly class ShowUserProfileController
{
    use AuthenticatedUserTrait;
    use HasTenantAwareCache;

    public function __construct(
        private UserDashboardCacheService $dashboardCacheService,
        private UserDashboardService $dashboardService,
        private CampaignRepositoryInterface $campaignRepository,
        private BookmarkService $bookmarkService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);

        // 1. Check cache status first
        $cacheStatus = $this->dashboardCacheService->checkUserCacheStatus($user->id);
        $warmingProgress = null;

        // 2. Initialize dashboard data as null
        $statistics = null;
        $recentActivities = null;
        $impact = null;
        $leaderboard = null;

        // 3. Handle cache status and data retrieval
        if ($cacheStatus['overall_status'] === 'miss') {
            // Cache is cold - trigger async warming and return with loading state
            $this->dashboardCacheService->warmUserCache($user->id);
            $cacheStatus['overall_status'] = 'warming'; // Update status for view
        }

        if ($cacheStatus['overall_status'] === 'warming') {
            // Cache is currently warming - get progress
            $warmingProgress = $this->dashboardCacheService->getCacheWarmingProgress($user->id);
        }

        if ($cacheStatus['overall_status'] === 'hit') {
            // Cache is hot - get data from UserDashboardService
            $statistics = $this->dashboardService->getUserStatistics($user->id, true);
            $recentActivities = $this->dashboardService->getUserActivityFeed($user->id, 10, true);
            $impact = $this->dashboardService->getUserImpactMetrics($user->id, true);
            $leaderboard = $this->dashboardService->getOrganizationLeaderboard($user->organization_id ?? 1, 5, true);
        }

        if ($cacheStatus['overall_status'] === 'partial') {
            // Partial cache - get available data from cache, trigger warming for missing data
            $statistics = $this->dashboardService->getUserStatistics($user->id, true);
            $recentActivities = $this->dashboardService->getUserActivityFeed($user->id, 10, true);
            $impact = $this->dashboardService->getUserImpactMetrics($user->id, true);
            $leaderboard = $this->dashboardService->getOrganizationLeaderboard($user->organization_id ?? 1, 5, true);

            // Trigger warming for missing parts
            $this->dashboardCacheService->warmUserCache($user->id);
        }

        // Get user's bookmarked campaigns with caching (5 min TTL)
        $bookmarkedCampaigns = Cache::remember(
            self::formatCacheKey("user:{$user->id}:bookmarked_campaigns"),
            300,
            fn (): Collection => $this->bookmarkService->getUserBookmarksWithDetails($user->id)
        );

        // Get featured campaigns with caching (30 min TTL)
        // The repository already has intelligent fallback logic:
        // 1. Featured campaigns (is_featured = true)
        // 2. Near-goal campaigns (70-100% progress)
        // 3. Popular campaigns (high donation count)
        $featuredCampaigns = Cache::remember(
            self::formatCacheKey('dashboard:featured_campaigns'),
            1800,
            fn (): Collection => collect($this->campaignRepository->getFeaturedCampaigns(4))
        );

        // If user has bookmarked campaigns, prioritize those
        // Otherwise show featured campaigns
        $displayCampaigns = $bookmarkedCampaigns->isNotEmpty()
            ? $bookmarkedCampaigns->take(4)
            : $featuredCampaigns;

        $recentCampaigns = collect();

        return view('dashboard', [
            'user' => $user,
            'featuredCampaigns' => $displayCampaigns,
            'bookmarkedCampaigns' => $bookmarkedCampaigns,
            'hasBookmarks' => $bookmarkedCampaigns->isNotEmpty(),
            'recentCampaigns' => $recentCampaigns,

            // Dashboard data (may be null if cache is cold/warming)
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'impact' => $impact,
            'leaderboard' => $leaderboard,

            // Cache status and warming progress for the view
            'cacheStatus' => $cacheStatus['overall_status'],
            'warmingProgress' => $warmingProgress,
            'cacheDetails' => $cacheStatus, // Full cache details for debugging
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Dashboard\Application\Service\UserDashboardService;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class DashboardDataController
{
    public function __construct(
        private UserDashboardService $dashboardService
    ) {}

    /**
     * Get dashboard data for the authenticated user.
     * This endpoint only works with cached data and doesn't trigger warming.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized('User must be authenticated');
        }

        $userId = $user->id;

        try {
            // Get dashboard data using only cache (useCache = true)
            // This won't trigger warming as it only reads from cache
            $statistics = $this->dashboardService->getUserStatistics($userId, true);
            $activityFeed = $this->dashboardService->getUserActivityFeed($userId, 10, true);
            $impactMetrics = $this->dashboardService->getUserImpactMetrics($userId, true);
            $ranking = $this->dashboardService->getUserOrganizationRanking($userId, true);

            // Get leaderboard if user belongs to an organization
            $leaderboard = [];
            if ($user->organization_id) {
                $leaderboard = $this->dashboardService->getOrganizationLeaderboard($user->organization_id, 5, true);
            }

            $dashboardData = [
                'user_id' => $userId,
                'statistics' => $statistics,
                'activity_feed' => $activityFeed,
                'impact_metrics' => $impactMetrics,
                'ranking' => $ranking,
                'leaderboard' => $leaderboard,
                'generated_at' => now()->toISOString(),
            ];

            return ApiResponse::success(
                data: $dashboardData,
                message: 'Dashboard data retrieved successfully',
                headers: [
                    'Cache-Control' => 'private, max-age=300', // 5 minutes
                    'X-User-ID' => (string) $userId,
                    'X-Data-Source' => 'cache',
                ]
            );

        } catch (Exception) {
            // If cache data is not available, return appropriate response
            return ApiResponse::error(
                message: 'Dashboard data not available in cache. Please wait for cache warming to complete.',
                errors: ['cache_miss' => 'Dashboard data is being prepared'],
                statusCode: 202, // Accepted - processing
                headers: [
                    'Retry-After' => '30', // Suggest retry after 30 seconds
                    'X-User-ID' => (string) $userId,
                    'X-Data-Source' => 'cache',
                ]
            );
        }
    }
}

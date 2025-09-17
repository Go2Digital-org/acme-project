<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class DashboardCacheStatusController
{
    public function __construct(
        private UserDashboardCacheService $cacheService
    ) {}

    /**
     * Get cache status for the authenticated user's dashboard.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized('User must be authenticated');
        }

        $userId = $user->id;
        $cacheStatus = $this->cacheService->checkUserCacheStatus($userId);
        $progress = $this->cacheService->getCacheWarmingProgress($userId);

        // Determine the main status based on cache status
        $status = match ($cacheStatus['overall_status']) {
            'hit' => 'hit',
            'warming' => 'warming',
            default => 'miss'
        };

        // Determine if cache is ready (all data available)
        $ready = $status === 'hit' && empty($cacheStatus['miss']);

        $responseData = [
            'status' => $status,
            'ready' => $ready,
            'progress' => $progress ?? [
                'user_id' => $userId,
                'percentage' => $status === 'hit' ? 100 : 0,
                'message' => $status === 'hit' ? 'Cache ready' : 'Cache not available',
                'updated_at' => now()->toISOString(),
            ],
            'cache_details' => [
                'hit_components' => $cacheStatus['hit'],
                'miss_components' => $cacheStatus['miss'],
                'warming' => $cacheStatus['warming'],
            ],
        ];

        return ApiResponse::success(
            data: $responseData,
            message: 'Dashboard cache status retrieved successfully',
            headers: [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'X-User-ID' => (string) $userId,
            ]
        );
    }
}

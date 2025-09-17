<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Dashboard\Application\Service\UserDashboardCacheService;

final readonly class UserDashboardCacheController
{
    public function __construct(
        private UserDashboardCacheService $cacheService
    ) {}

    /**
     * Check the cache status for the authenticated user's dashboard.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->id;
        $status = $this->cacheService->checkUserCacheStatus($userId);

        return response()->json([
            'user_id' => $userId,
            'cache_status' => $status,
        ]);
    }

    /**
     * Trigger cache warming for the authenticated user.
     */
    public function warm(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->id;
        $force = $request->boolean('force', false);

        $result = $this->cacheService->warmUserCache($userId, $force);

        return response()->json([
            'user_id' => $userId,
            'result' => $result,
            'message' => $this->getResultMessage($result),
        ]);
    }

    /**
     * Get cache warming progress for the authenticated user.
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->id;
        $progress = $this->cacheService->getCacheWarmingProgress($userId);

        if ($progress === null) {
            return response()->json([
                'user_id' => $userId,
                'progress' => null,
                'message' => 'No warming process found',
            ], 404);
        }

        return response()->json([
            'user_id' => $userId,
            'progress' => $progress,
        ]);
    }

    /**
     * Invalidate cache for the authenticated user.
     */
    public function invalidate(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->id;
        $this->cacheService->invalidateUserCache($userId);

        return response()->json([
            'user_id' => $userId,
            'message' => 'Cache invalidated successfully',
        ]);
    }

    private function getResultMessage(string $result): string
    {
        return match ($result) {
            'already_warming' => 'Cache warming is already in progress',
            'cache_hit' => 'Cache is already warm',
            'warming_started' => 'Cache warming has been started',
            default => 'Unknown result',
        };
    }
}

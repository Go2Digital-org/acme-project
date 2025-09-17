<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Controller\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\CacheWarming\Application\Query\GetCacheStatusQuery;
use Modules\CacheWarming\Application\Query\GetCacheStatusQueryHandler;

final readonly class CacheStatusController
{
    public function __construct(
        private GetCacheStatusQueryHandler $queryHandler
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = new GetCacheStatusQuery(
            cacheType: $request->get('cache_type', 'all'),
            includeProgress: true,
            includeRecommendations: (bool) $request->get('include_recommendations', false)
        );

        $data = $this->queryHandler->handle($query);

        // Transform data to match expected API response format
        $dataArray = $data->toArray();
        $totalKeys = $dataArray['total_keys'] ?? 0;
        $warmedCount = $dataArray['warmed_count'] ?? 0;

        $progressPercentage = $totalKeys > 0 ? (int) round(($warmedCount / $totalKeys) * 100) : 0;
        $isComplete = $progressPercentage >= 100;

        $currentPage = match (true) {
            $isComplete => 'Cache warming complete!',
            $progressPercentage >= 80 => 'Finishing up system caches...',
            $progressPercentage >= 60 => 'Warming database queries...',
            $progressPercentage >= 40 => 'Warming page templates...',
            $progressPercentage >= 20 => 'Warming dashboard widgets...',
            default => 'Initializing cache warming...'
        };

        $status = match (true) {
            $isComplete => 'complete',
            $progressPercentage > 0 => 'warming',
            default => 'idle'
        };

        return response()->json([
            'progress' => [
                'current_page' => $currentPage,
                'progress_percentage' => $progressPercentage,
                'is_complete' => $isComplete,
            ],
            'status' => $status,
            'stats' => [
                'total_keys' => $totalKeys,
                'warmed_count' => $warmedCount,
                'cold_count' => $totalKeys - $warmedCount,
                'cache_type' => $dataArray['cache_type'] ?? 'all',
            ],
        ]);
    }
}

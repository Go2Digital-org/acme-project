<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Controller;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\CacheWarming\Application\Query\GetCacheStatusQuery;
use Modules\CacheWarming\Application\Query\GetCacheStatusQueryHandler;
use Modules\CacheWarming\Infrastructure\Laravel\Jobs\WarmCacheJob;

final readonly class CacheWarmingController
{
    public function __construct(
        private GetCacheStatusQueryHandler $queryHandler
    ) {}

    public function __invoke(Request $request): View|Factory
    {
        $returnUrl = $request->get('returnUrl', $request->headers->get('referer', '/'));

        $query = new GetCacheStatusQuery(
            cacheType: $request->get('type', 'all'),
            includeRecommendations: true
        );

        $status = $this->queryHandler->handle($query);

        // Calculate progress percentage
        $statusData = $status->toArray();
        $totalKeys = $statusData['total_keys'] ?? 0;
        $warmedCount = $statusData['warmed_count'] ?? 0;
        $progressPercentage = $totalKeys > 0 ? (int) round(($warmedCount / $totalKeys) * 100) : 0;
        $isComplete = $progressPercentage >= 100;

        // Determine if we need to start cache warming
        $needsWarming = $progressPercentage === 0 && $totalKeys > 0;

        if ($needsWarming) {
            // Dispatch background job for async cache warming
            $job = new WarmCacheJob(
                strategyType: $request->get('type', 'all'),
                specificKeys: [],
                force: false
            );
            dispatch($job);
        }

        // Determine current page description
        $currentPage = match (true) {
            $isComplete => 'Cache warming complete!',
            $progressPercentage >= 80 => 'Finishing up system caches...',
            $progressPercentage >= 60 => 'Warming database queries...',
            $progressPercentage >= 40 => 'Warming page templates...',
            $progressPercentage >= 20 => 'Warming dashboard widgets...',
            default => 'Initializing cache warming...'
        };

        // Extract progress data
        $progress = $progressPercentage;

        return view('cache-warming', [
            'returnUrl' => $returnUrl,
            'status' => $status,
            'progress' => $progress,
            'currentPage' => $currentPage,
            'isComplete' => $isComplete,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

final class WarmUserDashboardCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes

    public function __construct(
        private readonly int $userId
    ) {
        $this->onQueue('cache-warming');
    }

    public function handle(
        DashboardRepositoryInterface $dashboardRepository,
        UserDashboardCacheService $cacheService
    ): void {
        $jobId = $this->generateJobId();

        try {
            Log::info('Starting user dashboard cache warming', [
                'job_id' => $jobId,
                'user_id' => $this->userId,
            ]);

            $totalSteps = 5;
            $currentStep = 0;

            // Step 1: Warm user statistics
            $cacheService->updateWarmingProgress(
                $this->userId,
                $this->calculateProgress(++$currentStep, $totalSteps),
                'Warming user statistics...'
            );

            try {
                $statistics = $dashboardRepository->getUserStatistics($this->userId);
                $cacheService->putUserStatisticsInCache($this->userId, $statistics);
            } catch (Exception $e) {
                Log::warning('Failed to warm user statistics cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Step 2: Warm activity feed
            $cacheService->updateWarmingProgress(
                $this->userId,
                $this->calculateProgress(++$currentStep, $totalSteps),
                'Warming activity feed...'
            );

            try {
                $activityFeed = $dashboardRepository->getUserActivityFeed($this->userId, 10);
                $cacheService->putUserActivityFeedInCache($this->userId, $activityFeed);
            } catch (Exception $e) {
                Log::warning('Failed to warm activity feed cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Step 3: Warm impact metrics
            $cacheService->updateWarmingProgress(
                $this->userId,
                $this->calculateProgress(++$currentStep, $totalSteps),
                'Warming impact metrics...'
            );

            try {
                $impactMetrics = $dashboardRepository->getUserImpactMetrics($this->userId);
                $cacheService->putUserImpactMetricsInCache($this->userId, $impactMetrics);
            } catch (Exception $e) {
                Log::warning('Failed to warm impact metrics cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Step 4: Warm user ranking
            $cacheService->updateWarmingProgress(
                $this->userId,
                $this->calculateProgress(++$currentStep, $totalSteps),
                'Warming user ranking...'
            );

            try {
                $ranking = $dashboardRepository->getUserOrganizationRanking($this->userId);
                $cacheService->putUserRankingInCache($this->userId, $ranking);
            } catch (Exception $e) {
                Log::warning('Failed to warm user ranking cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Step 5: Warm leaderboard (organization-specific, using user's organization)
            $cacheService->updateWarmingProgress(
                $this->userId,
                $this->calculateProgress(++$currentStep, $totalSteps),
                'Warming leaderboard...'
            );

            try {
                // Assuming organization ID 1 for now - in real implementation,
                // you'd get the user's organization ID from the user model
                $organizationId = $this->getUserOrganizationId($this->userId);
                $leaderboard = $dashboardRepository->getTopDonatorsLeaderboard($organizationId, 10);
                $cacheService->putUserLeaderboardInCache($this->userId, $leaderboard);
            } catch (Exception $e) {
                Log::warning('Failed to warm leaderboard cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Mark as completed
            $cacheService->markWarmingCompleted($this->userId, true);

            Log::info('User dashboard cache warming completed successfully', [
                'job_id' => $jobId,
                'user_id' => $this->userId,
                'total_steps' => $totalSteps,
            ]);

        } catch (Exception $e) {
            $cacheService->markWarmingCompleted($this->userId, false);

            Log::error('User dashboard cache warming failed', [
                'job_id' => $jobId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(?Exception $exception = null): void
    {
        $jobId = $this->generateJobId();
        $errorMessage = $exception?->getMessage() ?? 'Unknown error';

        // Use the service to mark as failed
        try {
            $cacheService = app(UserDashboardCacheService::class);
            $cacheService->markWarmingCompleted($this->userId, false);
        } catch (Exception $e) {
            Log::error('Failed to mark cache warming as failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::error('User dashboard cache warming job failed permanently', [
            'job_id' => $jobId,
            'user_id' => $this->userId,
            'error' => $errorMessage,
            'attempts' => $this->attempts(),
        ]);
    }

    public static function dispatch(int $userId): self
    {
        $job = new self($userId);
        dispatch($job);

        return $job;
    }

    public function getJobId(): string
    {
        return $this->generateJobId();
    }

    private function generateJobId(): string
    {
        return (string) $this->job?->getJobId() ?: uniqid('user_cache_warming_', true);
    }

    private function calculateProgress(int $currentStep, int $totalSteps): int
    {
        return (int) round(($currentStep / $totalSteps) * 100);
    }

    private function getUserOrganizationId(int $userId): int
    {
        // In a real implementation, you'd query the user model to get the organization ID
        // For now, assuming organization ID 1
        try {
            $user = User::find($userId);

            return $user->organization_id ?? 1;
        } catch (Exception $e) {
            Log::warning('Failed to get user organization ID, defaulting to 1', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }
}

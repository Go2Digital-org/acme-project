<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignProgress;

final readonly class CampaignProgressCalculator
{
    /**
     * Calculate comprehensive progress data for a campaign.
     */
    public function calculate(Campaign $campaign): CampaignProgress
    {
        $goalAmount = (float) $campaign->goal_amount;
        $currentAmount = (float) $campaign->current_amount;
        $progressPercentage = $this->calculateProgressPercentage($goalAmount, $currentAmount);

        $totalDays = $this->calculateTotalDays($campaign);
        $daysElapsed = $this->calculateDaysElapsed($campaign);
        $daysRemaining = $this->calculateDaysRemaining($campaign);

        $expectedProgress = $this->calculateExpectedProgress($totalDays, $daysElapsed);
        $velocity = $this->calculateVelocity($currentAmount, $daysElapsed);
        $projectedFinalAmount = $this->calculateProjectedFinalAmount($velocity, $totalDays);

        return new CampaignProgress(
            campaignId: $campaign->id,
            goalAmount: $goalAmount,
            currentAmount: $currentAmount,
            progressPercentage: $progressPercentage,
            remainingAmount: max(0, $goalAmount - $currentAmount),
            totalDays: $totalDays,
            daysElapsed: $daysElapsed,
            daysRemaining: $daysRemaining,
            expectedProgress: $expectedProgress,
            velocity: $velocity,
            projectedFinalAmount: $projectedFinalAmount,
            isOnTrack: $this->isOnTrack($progressPercentage, $expectedProgress),
            isLikelyToSucceed: $this->isLikelyToSucceed($projectedFinalAmount, $goalAmount),
            hasReachedGoal: $currentAmount >= $goalAmount,
            donationsCount: $campaign->donations_count ?? 0
        );
    }

    /**
     * Calculate the progress percentage.
     */
    private function calculateProgressPercentage(float $goalAmount, float $currentAmount): float
    {
        if ($goalAmount <= 0) {
            return 0.0;
        }

        return round(($currentAmount / $goalAmount) * 100, 2);
    }

    /**
     * Calculate total campaign duration in days.
     */
    private function calculateTotalDays(Campaign $campaign): int
    {
        if ($campaign->start_date === null || $campaign->end_date === null) {
            return 1;
        }

        return max(1, (int) $campaign->start_date->diffInDays($campaign->end_date));
    }

    /**
     * Calculate days elapsed since campaign start.
     */
    private function calculateDaysElapsed(Campaign $campaign): int
    {
        $now = now();

        if ($campaign->start_date === null || $campaign->end_date === null) {
            return 0;
        }

        if ($now->lt($campaign->start_date)) {
            return 0;
        }

        if ($now->gt($campaign->end_date)) {
            return $this->calculateTotalDays($campaign);
        }

        return (int) $campaign->start_date->diffInDays($now);
    }

    /**
     * Calculate days remaining until campaign end.
     */
    private function calculateDaysRemaining(Campaign $campaign): int
    {
        $now = now();

        if ($campaign->end_date === null) {
            return 0;
        }

        if ($now->gt($campaign->end_date)) {
            return 0;
        }

        return (int) $now->diffInDays($campaign->end_date);
    }

    /**
     * Calculate expected progress based on time elapsed.
     */
    private function calculateExpectedProgress(int $totalDays, int $daysElapsed): float
    {
        if ($totalDays <= 0) {
            return 0.0;
        }

        return round(($daysElapsed / $totalDays) * 100, 2);
    }

    /**
     * Calculate fundraising velocity (amount per day).
     */
    private function calculateVelocity(float $currentAmount, int $daysElapsed): float
    {
        if ($daysElapsed <= 0) {
            return 0.0;
        }

        return round($currentAmount / $daysElapsed, 2);
    }

    /**
     * Calculate projected final amount based on current velocity.
     */
    private function calculateProjectedFinalAmount(float $velocity, int $totalDays): float
    {
        return round($velocity * $totalDays, 2);
    }

    /**
     * Determine if campaign is on track to meet its goal.
     */
    private function isOnTrack(float $progressPercentage, float $expectedProgress): bool
    {
        // Allow 10% tolerance for being "on track"
        return $progressPercentage >= ($expectedProgress * 0.9);
    }

    /**
     * Determine if campaign is likely to succeed based on current trajectory.
     */
    private function isLikelyToSucceed(float $projectedFinalAmount, float $goalAmount): bool
    {
        // Consider likely to succeed if projected to reach 90% of goal
        return $projectedFinalAmount >= ($goalAmount * 0.9);
    }

    /**
     * Calculate performance metrics for comparison.
     *
     * @return array<string, mixed>
     */
    public function calculatePerformanceMetrics(Campaign $campaign): array
    {
        $progress = $this->calculate($campaign);

        $efficiency = $this->calculateEfficiency($progress);
        $momentum = $this->calculateMomentum($progress);
        $riskScore = $this->calculateRiskScore($progress);

        return [
            'efficiency' => $efficiency,
            'momentum' => $momentum,
            'risk_score' => $riskScore,
            'performance_score' => $this->calculateOverallPerformanceScore($efficiency, $momentum, $riskScore),
        ];
    }

    /**
     * Calculate fundraising efficiency (how well resources are being converted to donations).
     */
    private function calculateEfficiency(CampaignProgress $progress): float
    {
        if ($progress->getDaysElapsed() <= 0) {
            return 0.0;
        }

        // Simple efficiency: amount raised per day elapsed
        $dailyAverage = $progress->getCurrentAmount() / $progress->getDaysElapsed();

        // Normalize to 0-100 scale (assuming €100/day is excellent)
        return min(100.0, round(($dailyAverage / 100) * 100, 2));
    }

    /**
     * Calculate momentum (trend of recent performance).
     */
    private function calculateMomentum(CampaignProgress $progress): float
    {
        // This would ideally analyze donation patterns over time
        // For now, we'll use velocity relative to what's needed
        $neededVelocity = $progress->getDaysRemaining() > 0
            ? $progress->getRemainingAmount() / $progress->getDaysRemaining()
            : 0;

        if ($neededVelocity <= 0) {
            return 100.0; // Goal already reached
        }

        $momentumRatio = $progress->getVelocity() / $neededVelocity;

        return min(100.0, round($momentumRatio * 100, 2));
    }

    /**
     * Calculate risk score (likelihood of not meeting goal).
     */
    private function calculateRiskScore(CampaignProgress $progress): float
    {
        $riskFactors = [];

        // Time risk: how much time is left
        $timeRisk = $progress->getDaysRemaining() < 7 ? 30 : 0;
        $riskFactors[] = $timeRisk;

        // Progress risk: how far behind expected progress
        if ($progress->getPercentage() < $progress->getExpectedProgress()) {
            $progressGap = $progress->getExpectedProgress() - $progress->getPercentage();
            $progressRisk = min(40, $progressGap * 2);
            $riskFactors[] = $progressRisk;
        }

        // Velocity risk: current pace vs needed pace
        if (! $progress->isLikelyToSucceed()) {
            $riskFactors[] = 30;
        }

        return min(100.0, array_sum($riskFactors));
    }

    /**
     * Calculate overall performance score.
     */
    private function calculateOverallPerformanceScore(float $efficiency, float $momentum, float $riskScore): float
    {
        // Weighted average: efficiency 40%, momentum 40%, risk 20% (inverted)
        $performanceScore = ($efficiency * 0.4) + ($momentum * 0.4) + ((100 - $riskScore) * 0.2);

        return round($performanceScore, 2);
    }

    /**
     * Get campaign health status based on performance metrics.
     */
    public function getCampaignHealthStatus(Campaign $campaign): string
    {
        $metrics = $this->calculatePerformanceMetrics($campaign);
        $performanceScore = $metrics['performance_score'];

        return match (true) {
            $performanceScore >= 80 => 'excellent',
            $performanceScore >= 60 => 'good',
            $performanceScore >= 40 => 'fair',
            $performanceScore >= 20 => 'poor',
            default => 'critical',
        };
    }

    /**
     * Generate recommendations based on campaign progress.
     *
     * @return array<int, string>
     */
    public function generateRecommendations(Campaign $campaign): array
    {
        $progress = $this->calculate($campaign);
        $recommendations = [];

        if ($progress->isBehindSchedule()) {
            $recommendations[] = 'Campaign is behind schedule. Consider increasing marketing efforts or adjusting the goal.';
        }

        if ($progress->getDaysRemaining() <= 7 && ! $progress->isLikelyToSucceed()) {
            $recommendations[] = 'Campaign is approaching deadline with low likelihood of success. Consider extending the deadline or intensive promotion.';
        }

        if ($progress->getVelocity() < 10 && $progress->getDaysElapsed() > 3) {
            $recommendations[] = 'Low donation velocity detected. Review campaign messaging and promotion strategy.';
        }

        if ($progress->getDonationsCount() === 0 && $progress->getDaysElapsed() > 2) {
            $recommendations[] = 'No donations received yet. Verify campaign visibility and share with initial supporters.';
        }

        if ($progress->getPercentage() > 90) {
            $recommendations[] = 'Campaign is performing excellently! Consider promoting success story to encourage final push.';
        }

        return $recommendations;
    }
}

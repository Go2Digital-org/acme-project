<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use InvalidArgumentException;

final readonly class CampaignProgress
{
    public function __construct(
        private int $campaignId,
        private float $goalAmount,
        private float $currentAmount,
        private float $progressPercentage,
        private float $remainingAmount,
        private int $totalDays,
        private int $daysElapsed,
        private int $daysRemaining,
        private float $expectedProgress,
        private float $velocity,
        private float $projectedFinalAmount,
        private bool $isOnTrack,
        private bool $isLikelyToSucceed,
        private bool $hasReachedGoal,
        private int $donationsCount,
    ) {
        if ($this->currentAmount < 0) {
            throw new InvalidArgumentException('Current amount cannot be negative');
        }

        if ($this->goalAmount <= 0) {
            throw new InvalidArgumentException('Goal amount must be greater than zero');
        }

        if ($this->campaignId <= 0) {
            throw new InvalidArgumentException('Campaign ID must be positive');
        }
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getCurrentAmount(): float
    {
        return $this->currentAmount;
    }

    public function getGoalAmount(): float
    {
        return $this->goalAmount;
    }

    public function getPercentage(): float
    {
        return $this->progressPercentage;
    }

    public function getPercentageRounded(): int
    {
        return (int) round($this->progressPercentage);
    }

    public function getRemainingAmount(): float
    {
        return $this->remainingAmount;
    }

    public function getTotalDays(): int
    {
        return $this->totalDays;
    }

    public function getDaysElapsed(): int
    {
        return $this->daysElapsed;
    }

    public function getDaysRemaining(): int
    {
        return $this->daysRemaining;
    }

    public function getExpectedProgress(): float
    {
        return $this->expectedProgress;
    }

    public function getVelocity(): float
    {
        return $this->velocity;
    }

    public function getProjectedFinalAmount(): float
    {
        return $this->projectedFinalAmount;
    }

    public function isOnTrack(): bool
    {
        return $this->isOnTrack;
    }

    public function isLikelyToSucceed(): bool
    {
        return $this->isLikelyToSucceed;
    }

    public function hasReachedGoal(): bool
    {
        return $this->hasReachedGoal;
    }

    public function getDonationsCount(): int
    {
        return $this->donationsCount;
    }

    public function isBehindSchedule(): bool
    {
        return $this->progressPercentage < ($this->expectedProgress * 0.9);
    }

    public function isCompleted(): bool
    {
        return $this->hasReachedGoal;
    }

    public function getProgressRatio(): float
    {
        return min(1.0, $this->currentAmount / $this->goalAmount);
    }

    /**
     * Get performance status as a string.
     */
    public function getPerformanceStatus(): string
    {
        return match (true) {
            $this->hasReachedGoal => 'completed',
            $this->isOnTrack && $this->isLikelyToSucceed => 'excellent',
            $this->isOnTrack => 'good',
            $this->isLikelyToSucceed => 'fair',
            default => 'poor',
        };
    }

    /**
     * @param  object{current_amount: numeric, goal_amount: numeric}  $campaign
     */
    public static function fromCampaign(object $campaign): self
    {
        return new self(
            campaignId: property_exists($campaign, 'id') && $campaign->id > 0 ? (int) $campaign->id : 1,
            goalAmount: (float) $campaign->goal_amount,
            currentAmount: (float) $campaign->current_amount,
            progressPercentage: min(((float) $campaign->current_amount / (float) $campaign->goal_amount) * 100, 100),
            remainingAmount: max(0, (float) $campaign->goal_amount - (float) $campaign->current_amount),
            totalDays: 30, // Default values - would need actual campaign dates
            daysElapsed: 15,
            daysRemaining: 15,
            expectedProgress: 50.0,
            velocity: 0.0,
            projectedFinalAmount: (float) $campaign->current_amount,
            isOnTrack: true,
            isLikelyToSucceed: false,
            hasReachedGoal: (float) $campaign->current_amount >= (float) $campaign->goal_amount,
            donationsCount: property_exists($campaign, 'donations_count') ? (int) ($campaign->donations_count ?? 0) : 0,
        );
    }

    /**
     * Convert the progress object to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'goal_amount' => $this->goalAmount,
            'current_amount' => $this->currentAmount,
            'progress_percentage' => $this->progressPercentage,
            'progress_percentage_rounded' => $this->getPercentageRounded(),
            'remaining_amount' => $this->remainingAmount,
            'total_days' => $this->totalDays,
            'days_elapsed' => $this->daysElapsed,
            'days_remaining' => $this->daysRemaining,
            'expected_progress' => $this->expectedProgress,
            'velocity' => $this->velocity,
            'projected_final_amount' => $this->projectedFinalAmount,
            'is_on_track' => $this->isOnTrack,
            'is_likely_to_succeed' => $this->isLikelyToSucceed,
            'has_reached_goal' => $this->hasReachedGoal,
            'is_behind_schedule' => $this->isBehindSchedule(),
            'is_completed' => $this->isCompleted(),
            'donations_count' => $this->donationsCount,
            'progress_ratio' => $this->getProgressRatio(),
            'performance_status' => $this->getPerformanceStatus(),
        ];
    }

    /**
     * Factory method for testing purposes.
     * Creates a CampaignProgress with sensible defaults for all required parameters.
     */
    public static function createForTesting(float $currentAmount, float $goalAmount): self
    {
        $progressPercentage = $goalAmount > 0 ? min(($currentAmount / $goalAmount) * 100, 100) : 0;
        $remainingAmount = max(0, $goalAmount - $currentAmount);
        $hasReachedGoal = $currentAmount >= $goalAmount;

        // Calculate sensible defaults based on the amounts
        $totalDays = 30;
        $daysElapsed = 15;
        $daysRemaining = 15;
        $expectedProgress = 50.0;
        // Since daysElapsed is hardcoded to 15 for testing, we can simplify this
        $velocity = $currentAmount / $daysElapsed;
        $projectedFinalAmount = $velocity * $totalDays;
        $isOnTrack = $progressPercentage >= ($expectedProgress * 0.9);
        $isLikelyToSucceed = $projectedFinalAmount >= ($goalAmount * 0.9);

        return new self(
            campaignId: 1,
            goalAmount: $goalAmount,
            currentAmount: $currentAmount,
            progressPercentage: $progressPercentage,
            remainingAmount: $remainingAmount,
            totalDays: $totalDays,
            daysElapsed: $daysElapsed,
            daysRemaining: $daysRemaining,
            expectedProgress: $expectedProgress,
            velocity: $velocity,
            projectedFinalAmount: $projectedFinalAmount,
            isOnTrack: $isOnTrack,
            isLikelyToSucceed: $isLikelyToSucceed,
            hasReachedGoal: $hasReachedGoal,
            donationsCount: 0,
        );
    }
}

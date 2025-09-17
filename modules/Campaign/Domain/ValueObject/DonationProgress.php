<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Shared\Domain\ValueObject\Money;
use Modules\Shared\Domain\ValueObject\ProgressData;

/**
 * Value object representing donation progress for a campaign.
 * Extends generic progress with donation-specific behaviors.
 */
class DonationProgress
{
    private readonly ProgressData $progressData;

    private readonly Money $raised;

    private readonly Money $goal;

    private readonly Money $remaining;

    public function __construct(
        Money $raised,
        Money $goal,
        private readonly int $donorCount = 0,
        private readonly int $daysRemaining = 0,
        private readonly bool $isActive = true,
        private readonly ?Money $averageDonation = null,
        private readonly ?Money $largestDonation = null,
        private readonly ?Money $recentMomentum = null,
    ) {
        if (! $raised->equals(Money::zero($raised->currency)) && ! $goal->equals($raised) && $raised->currency !== $goal->currency) {
            throw new InvalidArgumentException('Raised and goal amounts must be in the same currency');
        }

        $this->raised = $raised;
        $this->goal = $goal;
        $this->remaining = $goal->greaterThan($raised)
            ? new Money($goal->amount - $raised->amount, $goal->currency)
            : Money::zero($goal->currency);

        $this->progressData = new ProgressData(
            $raised->amount,
            $goal->amount,
            'Campaign Progress',
        );
    }

    public function getRaised(): Money
    {
        return $this->raised;
    }

    public function getGoal(): Money
    {
        return $this->goal;
    }

    public function getRemaining(): Money
    {
        return $this->remaining;
    }

    public function getDonorCount(): int
    {
        return $this->donorCount;
    }

    public function getDaysRemaining(): int
    {
        return max(0, $this->daysRemaining);
    }

    public function isActive(): bool
    {
        return $this->isActive && $this->daysRemaining > 0;
    }

    public function hasExpired(): bool
    {
        return $this->daysRemaining < 0;
    }

    public function isEndingSoon(): bool
    {
        return $this->isActive && $this->daysRemaining > 0 && $this->daysRemaining <= 7;
    }

    public function isEndingToday(): bool
    {
        return $this->isActive && $this->daysRemaining === 0;
    }

    public function getAverageDonation(): ?Money
    {
        if ($this->averageDonation instanceof Money) {
            return $this->averageDonation;
        }

        if ($this->donorCount > 0 && $this->raised->amount > 0) {
            return new Money(
                $this->raised->amount / $this->donorCount,
                $this->raised->currency,
            );
        }

        return null;
    }

    public function getLargestDonation(): ?Money
    {
        return $this->largestDonation;
    }

    public function getRecentMomentum(): ?Money
    {
        return $this->recentMomentum;
    }

    public function getProgressData(): ProgressData
    {
        return $this->progressData;
    }

    public function getPercentage(): float
    {
        return $this->progressData->getPercentage();
    }

    public function hasReachedGoal(): bool
    {
        return $this->progressData->hasReachedGoal();
    }

    public function getUrgencyLevel(): string
    {
        if (! $this->isActive) {
            return 'inactive';
        }

        if ($this->hasExpired()) {
            return 'expired';
        }

        if ($this->isEndingToday()) {
            return 'critical';
        }

        if ($this->daysRemaining <= 3) {
            return 'very-high';
        }

        if ($this->daysRemaining <= 7) {
            return 'high';
        }

        if ($this->daysRemaining <= 14) {
            return 'medium';
        }

        return 'normal';
    }

    public function getUrgencyColor(): string
    {
        return match ($this->getUrgencyLevel()) {
            'expired' => 'gray',
            'critical' => 'red',
            'very-high' => 'orange',
            'high' => 'yellow',
            'medium' => 'blue',
            default => 'green',
        };
    }

    public function getMomentumIndicator(): string
    {
        if (! $this->recentMomentum instanceof Money) {
            return 'steady';
        }

        $averageDonation = $this->getAverageDonation();

        if (! $averageDonation instanceof Money) {
            return 'steady';
        }

        $momentumRatio = $this->recentMomentum->amount / $averageDonation->amount;

        if ($momentumRatio >= 2.0) {
            return 'surging';
        }

        if ($momentumRatio >= 1.5) {
            return 'increasing';
        }

        if ($momentumRatio >= 0.8) {
            return 'steady';
        }

        return 'slowing';
    }

    public function getCompletionEstimate(): ?int
    {
        if (! $this->isActive || $this->hasReachedGoal()) {
            return null;
        }

        if (! $this->recentMomentum instanceof Money || $this->recentMomentum->isZero()) {
            return null;
        }

        // Estimate days to completion based on recent momentum
        $daysToComplete = (int) ceil($this->remaining->amount / $this->recentMomentum->amount);

        if ($daysToComplete > $this->daysRemaining) {
            return null; // Won't complete at current rate
        }

        return $daysToComplete;
    }

    public function needsBoost(): bool
    {
        if (! $this->isActive || $this->hasReachedGoal()) {
            return false;
        }

        // Needs boost if ending soon and not close to goal
        if ($this->isEndingSoon() && $this->getPercentage() < 75) {
            return true;
        }

        // Needs boost if momentum is slowing
        return $this->getMomentumIndicator() === 'slowing' && $this->getPercentage() < 90;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDisplayData(): array
    {
        return [
            'raised' => $this->raised->toArray(),
            'goal' => $this->goal->toArray(),
            'remaining' => $this->remaining->toArray(),
            'percentage' => $this->getPercentage(),
            'formatted_percentage' => $this->progressData->getFormattedPercentage(),
            'donor_count' => $this->donorCount,
            'days_remaining' => $this->getDaysRemaining(),
            'is_active' => $this->isActive(),
            'has_expired' => $this->hasExpired(),
            'is_ending_soon' => $this->isEndingSoon(),
            'is_ending_today' => $this->isEndingToday(),
            'has_reached_goal' => $this->hasReachedGoal(),
            'urgency_level' => $this->getUrgencyLevel(),
            'urgency_color' => $this->getUrgencyColor(),
            'momentum_indicator' => $this->getMomentumIndicator(),
            'needs_boost' => $this->needsBoost(),
            'average_donation' => $this->getAverageDonation()?->toArray(),
            'largest_donation' => $this->getLargestDonation()?->toArray(),
            'recent_momentum' => $this->getRecentMomentum()?->toArray(),
            'completion_estimate' => $this->getCompletionEstimate(),
            'progress_data' => $this->progressData->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getDisplayData();
    }
}

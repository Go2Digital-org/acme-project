<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

final readonly class UserCampaignStats
{
    public function __construct(
        public int $totalCampaigns,
        public int $activeCampaigns,
        public int $completedCampaigns,
        public int $draftCampaigns,
        public float $totalAmountRaised,
        public float $totalGoalAmount,
        public int $totalDonations,
        public float $averageSuccessRate,
    ) {}

    public function getProgressPercentage(): float
    {
        if ($this->totalGoalAmount === 0.0) {
            return 0.0;
        }

        return min(100.0, ($this->totalAmountRaised / $this->totalGoalAmount) * 100);
    }

    public function getFormattedTotalRaised(): string
    {
        return '€' . number_format($this->totalAmountRaised, 0, ',', '.');
    }

    public function getFormattedTotalGoal(): string
    {
        return '€' . number_format($this->totalGoalAmount, 0, ',', '.');
    }

    public function getFormattedSuccessRate(): string
    {
        return number_format($this->averageSuccessRate, 1) . '%';
    }

    public function hasActiveCampaigns(): bool
    {
        return $this->activeCampaigns > 0;
    }

    public function hasDrafts(): bool
    {
        return $this->draftCampaigns > 0;
    }

    public function getTotalPublishedCampaigns(): int
    {
        return $this->totalCampaigns - $this->draftCampaigns;
    }
}

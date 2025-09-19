<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject\Money;

final readonly class DashboardStatistics
{
    public function __construct(
        public Money $totalDonated,
        public int $campaignsSupported,
        public float $impactScore,
        public int $teamRanking,
        public int $totalTeams,
        public Money $monthlyIncrease,
        public float $monthlyGrowthPercentage,
    ) {}

    public function getFormattedImpactScore(): string
    {
        return number_format($this->impactScore, 1);
    }

    public function isTopPerformer(): bool
    {
        return $this->teamRanking <= 3;
    }

    public function getPerformanceLevel(): string
    {
        if ($this->impactScore >= 9.0) {
            return 'excellent';
        }

        if ($this->impactScore >= 7.0) {
            return 'good';
        }

        if ($this->impactScore >= 5.0) {
            return 'average';
        }

        return 'needs_improvement';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'totalDonated' => $this->totalDonated->amount,
            'totalDonatedFormatted' => $this->totalDonated->format(),
            'campaignsSupported' => $this->campaignsSupported,
            'impactScore' => $this->impactScore,
            'impactScoreFormatted' => $this->getFormattedImpactScore(),
            'teamRanking' => $this->teamRanking,
            'totalTeams' => $this->totalTeams,
            'monthlyIncrease' => $this->monthlyIncrease->amount,
            'monthlyGrowthPercentage' => $this->monthlyGrowthPercentage,
            'isTopPerformer' => $this->isTopPerformer(),
            'performanceLevel' => $this->getPerformanceLevel(),
        ];
    }
}

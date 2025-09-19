<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\ValueObject;

use Illuminate\Support\Carbon;

final readonly class ImpactMetrics
{
    public function __construct(
        public int $peopleHelped,
        public int $countriesReached,
        public int $organizationsSupported,
        /** @var array<string, mixed> */
        public array $categoryBreakdown,
        public Carbon $calculatedAt,
    ) {}

    public function getTotalImpactScore(): float
    {
        $baseScore = 0.0;

        // Each person helped contributes 0.01 to the score
        $baseScore += $this->peopleHelped * 0.01;

        // Each country reached contributes 0.5 to the score
        $baseScore += $this->countriesReached * 0.5;

        // Each organization supported contributes 0.3 to the score
        $baseScore += $this->organizationsSupported * 0.3;

        // Cap at 10.0
        return min(10.0, $baseScore);
    }

    public function getTopCategory(): ?string
    {
        if ($this->categoryBreakdown === []) {
            return null;
        }

        $key = array_key_first(
            array: array_slice(
                array: $this->categoryBreakdown,
                offset: 0,
                length: 1,
                preserve_keys: true,
            ),
        );

        return $key !== null ? (string) $key : null;
    }

    public function getCategoryPercentage(string $category): float
    {
        if (! isset($this->categoryBreakdown[$category])) {
            return 0.0;
        }

        $total = array_sum($this->categoryBreakdown);

        if ($total === 0.0) {
            return 0.0;
        }

        return ($this->categoryBreakdown[$category] / $total) * 100;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'peopleHelped' => $this->peopleHelped,
            'countriesReached' => $this->countriesReached,
            'organizationsSupported' => $this->organizationsSupported,
            'categoryBreakdown' => $this->categoryBreakdown,
            'totalImpactScore' => $this->getTotalImpactScore(),
            'topCategory' => $this->getTopCategory(),
            'calculatedAt' => $this->calculatedAt->toIso8601String(),
        ];
    }
}

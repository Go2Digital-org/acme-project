<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class CacheStatusReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 30; // 30 seconds for cache status (changes frequently)

    public function getTotalKeys(): int
    {
        return $this->get('total_keys', 0);
    }

    public function getWarmedCount(): int
    {
        return $this->get('warmed_count', 0);
    }

    public function getColdCount(): int
    {
        return $this->get('cold_count', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWarmedKeys(): array
    {
        return $this->get('warmed_keys', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getColdKeys(): array
    {
        return $this->get('cold_keys', []);
    }

    public function getCacheType(): string
    {
        return $this->get('cache_type', 'all');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRecommendations(): ?array
    {
        return $this->get('recommendations');
    }

    public function getWarmingPercentage(): float
    {
        if ($this->getTotalKeys() === 0) {
            return 0.0;
        }

        return round(($this->getWarmedCount() / $this->getTotalKeys()) * 100, 2);
    }

    public function isFullyWarmed(): bool
    {
        return $this->getColdCount() === 0 && $this->getTotalKeys() > 0;
    }

    public function isEmpty(): bool
    {
        return $this->getTotalKeys() === 0;
    }

    public function hasRecommendations(): bool
    {
        $recommendations = $this->getRecommendations();

        return $recommendations !== null && array_filter($recommendations) !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cache_type' => $this->getCacheType(),
            'total_keys' => $this->getTotalKeys(),
            'warmed_count' => $this->getWarmedCount(),
            'cold_count' => $this->getColdCount(),
            'warming_percentage' => $this->getWarmingPercentage(),
            'is_fully_warmed' => $this->isFullyWarmed(),
            'is_empty' => $this->isEmpty(),
            'warmed_keys' => $this->getWarmedKeys(),
            'cold_keys' => $this->getColdKeys(),
            'recommendations' => $this->getRecommendations(),
            'has_recommendations' => $this->hasRecommendations(),
            'version' => $this->getVersion(),
        ];
    }
}

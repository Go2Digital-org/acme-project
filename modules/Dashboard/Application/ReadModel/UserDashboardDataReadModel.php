<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\ReadModel;

use Modules\Dashboard\Domain\ValueObject\ActivityFeedItem;
use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;
use Modules\Dashboard\Domain\ValueObject\LeaderboardEntry;
use Modules\Shared\Application\ReadModel\ReadModelInterface;

final readonly class UserDashboardDataReadModel implements ReadModelInterface
{
    public function __construct(
        public int $userId,
        /** @var DashboardStatistics|array<string, mixed> */
        public DashboardStatistics|array $statistics,
        /** @var array<int, ActivityFeedItem|array<string, mixed>> */
        public array $activityFeed,
        /** @var ImpactMetrics|array<string, mixed> */
        public ImpactMetrics|array $impactMetrics,
        public int $ranking,
        /** @var array<int, LeaderboardEntry|array<string, mixed>> */
        public array $leaderboard,
        public string $generatedAt,
        public bool $fromCache = true
    ) {}

    public function getId(): int
    {
        return $this->userId;
    }

    public function getCacheKey(): string
    {
        return "dashboard:data:{$this->userId}";
    }

    public function getCacheTags(): array
    {
        return ['dashboard', 'user-data', "user:{$this->userId}"];
    }

    public function getCacheTtl(): int
    {
        return 1800; // 30 minutes
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function getVersion(): string
    {
        return $this->generatedAt;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'statistics' => $this->statistics instanceof DashboardStatistics
                ? $this->statistics->toArray()
                : $this->statistics,
            'activity_feed' => array_map(
                fn ($item) => $item instanceof ActivityFeedItem ? $item->toArray() : $item,
                $this->activityFeed
            ),
            'impact_metrics' => $this->impactMetrics instanceof ImpactMetrics
                ? $this->impactMetrics->toArray()
                : $this->impactMetrics,
            'ranking' => $this->ranking,
            'leaderboard' => array_map(
                fn ($entry) => $entry instanceof LeaderboardEntry ? $entry->toArray() : $entry,
                $this->leaderboard
            ),
            'generated_at' => $this->generatedAt,
            'from_cache' => $this->fromCache,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}

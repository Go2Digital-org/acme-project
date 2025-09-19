<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\ReadModel;

use Modules\Shared\Application\ReadModel\ReadModelInterface;

final readonly class DashboardCacheStatusReadModel implements ReadModelInterface
{
    /**
     * @param  array<string, mixed>  $progress
     * @param  array<string, mixed>  $cacheDetails
     */
    public function __construct(
        public string $status,
        public bool $ready,
        /** @var array<string, mixed> */
        public array $progress,
        /** @var array<string, mixed> */
        public array $cacheDetails
    ) {}

    public function getId(): string|int
    {
        return $this->progress['user_id'] ?? 'dashboard-cache-status';
    }

    public function getCacheKey(): string
    {
        $userId = $this->progress['user_id'] ?? 'unknown';

        return "dashboard:cache:status:{$userId}";
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        $userId = $this->progress['user_id'] ?? 'unknown';

        return ['dashboard', 'cache-status', "user:{$userId}"];
    }

    public function getCacheTtl(): int
    {
        return 60; // 1 minute for cache status
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function getVersion(): string
    {
        return $this->progress['updated_at'] ?? now()->toISOString();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'ready' => $this->ready,
            'progress' => $this->progress,
            'cache_details' => $this->cacheDetails,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}

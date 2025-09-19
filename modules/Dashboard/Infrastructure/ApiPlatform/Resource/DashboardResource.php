<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Processor\RefreshDashboardCacheProcessor;
use Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Provider\DashboardDataProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Dashboard',
    description: 'User dashboard data with cache support',
    operations: [
        new Get(
            uriTemplate: '/dashboard/data',
            normalizationContext: ['groups' => ['dashboard:read']],
            security: 'is_granted("ROLE_USER")',
            output: DashboardResource::class,
            read: false,
            provider: DashboardDataProvider::class
        ),
        new Post(
            uriTemplate: '/dashboard/refresh',
            normalizationContext: ['groups' => ['dashboard:refresh']],
            denormalizationContext: ['groups' => ['dashboard:refresh']],
            security: 'is_granted("ROLE_USER")',
            read: false,
            write: false,
            processor: RefreshDashboardCacheProcessor::class
        ),
    ],
    stateless: false,
    mercure: true,
    messenger: true
)]
class DashboardResource
{
    #[Groups(['dashboard:read'])]
    public ?int $userId = null;

    #[Groups(['dashboard:read'])]
    /**
     * @var array<string, mixed>|null
     *
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public ?array $statistics = null;

    #[Groups(['dashboard:read'])]
    /**
     * @var array<int, mixed>|null
     *
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public ?array $activityFeed = null;

    #[Groups(['dashboard:read'])]
    /**
     * @var array<string, mixed>|null
     *
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public ?array $impactMetrics = null;

    #[Groups(['dashboard:read'])]
    public ?int $ranking = null;

    #[Groups(['dashboard:read'])]
    /**
     * @var array<int, mixed>|null
     *
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public ?array $leaderboard = null;

    #[Groups(['dashboard:read'])]
    public ?string $generatedAt = null;

    #[Groups(['dashboard:read'])]
    public ?string $dataSource = null;

    #[Groups(['dashboard:refresh'])]
    public ?bool $force = null;

    #[Groups(['dashboard:refresh'])]
    public ?string $status = null;

    #[Groups(['dashboard:refresh'])]
    public ?string $message = null;

    /**
     * @param  array<string, mixed>  $statistics
     * @param  array<string, mixed>  $activityFeed
     * @param  array<string, mixed>  $impactMetrics
     * @param  array<string, mixed>  $leaderboard
     */
    public static function fromDashboardData(
        int $userId,
        array $statistics,
        array $activityFeed,
        array $impactMetrics,
        int $ranking,
        array $leaderboard
    ): self {
        $resource = new self;
        $resource->userId = $userId;
        $resource->statistics = $statistics;
        $resource->activityFeed = $activityFeed;
        $resource->impactMetrics = $impactMetrics;
        $resource->ranking = $ranking;
        $resource->leaderboard = $leaderboard;
        $resource->generatedAt = now()->toISOString();
        $resource->dataSource = 'cache';

        return $resource;
    }

    public static function refreshResponse(string $status, string $message): self
    {
        $resource = new self;
        $resource->status = $status;
        $resource->message = $message;

        return $resource;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Processor\InvalidateDashboardCacheProcessor;
use Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Processor\WarmDashboardCacheProcessor;
use Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Provider\DashboardCacheStatusProvider;

#[ApiResource(
    shortName: 'DashboardCacheStatus',
    description: 'Dashboard cache status and warming control',
    operations: [
        new GetCollection(
            uriTemplate: '/dashboard/cache-status',
            paginationEnabled: false,
            security: 'user',
            provider: DashboardCacheStatusProvider::class
        ),
        new Post(
            uriTemplate: '/dashboard/cache-warm',
            security: 'user',
            read: false,
            write: false,
            processor: WarmDashboardCacheProcessor::class
        ),
        new Delete(
            uriTemplate: '/dashboard/cache',
            security: 'user',
            read: false,
            processor: InvalidateDashboardCacheProcessor::class
        ),
    ],
    stateless: false,
    mercure: false,
    messenger: false,
    input: false,
    output: DashboardCacheStatusResource::class
)]
class DashboardCacheStatusResource
{
    public ?string $status = null;

    public ?bool $ready = null;

    /** @var array<string, mixed>|null */
    public ?array $progress = null;

    /** @var array<string, mixed>|null */
    public ?array $cacheDetails = null;

    public ?bool $force = null;

    public ?string $result = null;

    public ?string $message = null;

    public ?string $jobId = null;

    /**
     * @param  array<string, mixed>|null  $progress
     * @param  array<string, mixed>  $cacheDetails
     */
    public static function fromCacheStatus(
        string $status,
        bool $ready,
        ?array $progress,
        array $cacheDetails
    ): self {
        $resource = new self;
        $resource->status = $status;
        $resource->ready = $ready;
        $resource->progress = $progress;
        $resource->cacheDetails = $cacheDetails;

        return $resource;
    }

    public static function warmingResponse(string $result, string $message, ?string $jobId = null): self
    {
        $resource = new self;
        $resource->result = $result;
        $resource->message = $message;
        $resource->jobId = $jobId;

        return $resource;
    }

    public static function invalidateResponse(string $result, string $message): self
    {
        $resource = new self;
        $resource->result = $result;
        $resource->message = $message;

        return $resource;
    }
}

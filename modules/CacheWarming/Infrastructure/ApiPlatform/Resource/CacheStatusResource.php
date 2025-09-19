<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Modules\CacheWarming\Infrastructure\ApiPlatform\Handler\Provider\CacheStatusProvider;

#[ApiResource(
    shortName: 'CacheStatus',
    operations: [
        new GetCollection(
            uriTemplate: '/cache-status',
            // No auth required - public endpoint
            paginationEnabled: false,
            provider: CacheStatusProvider::class,
            middleware: [],
        ),
    ],
    stateless: false,
    mercure: false,
    messenger: false,
    input: false,
    output: CacheStatusResource::class,
)]
class CacheStatusResource
{
    /**
     * @param  array<string, mixed>|null  $progress
     * @param  array<string, mixed>|null  $stats
     */
    public function __construct(
        public readonly ?array $progress = null,
        public readonly ?string $status = null,
        public readonly ?array $stats = null,
    ) {}

    /**
     * Create a CacheStatusResource from cache warming data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromData(array $data): self
    {
        $totalKeys = $data['total_keys'] ?? 0;
        $warmedCount = $data['warmed_count'] ?? 0;

        // Calculate progress percentage
        $progressPercentage = $totalKeys > 0 ? (int) round(($warmedCount / $totalKeys) * 100) : 0;
        $isComplete = $progressPercentage >= 100;

        // Determine current page/status description
        $currentPage = match (true) {
            $isComplete => 'Cache warming complete!',
            $progressPercentage >= 80 => 'Finishing up system caches...',
            $progressPercentage >= 60 => 'Warming database queries...',
            $progressPercentage >= 40 => 'Warming page templates...',
            $progressPercentage >= 20 => 'Warming dashboard widgets...',
            default => 'Initializing cache warming...'
        };

        // Determine overall status
        $status = match (true) {
            $isComplete => 'complete',
            $progressPercentage > 0 => 'warming',
            default => 'idle'
        };

        return new self(
            progress: [
                'current_page' => $currentPage,
                'progress_percentage' => $progressPercentage,
                'is_complete' => $isComplete,
            ],
            status: $status,
            stats: [
                'total_keys' => $totalKeys,
                'warmed_count' => $warmedCount,
                'cold_count' => $data['cold_count'] ?? 0,
                'cache_type' => $data['cache_type'] ?? 'all',
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\ReadModel\ReadModelCacheInvalidator;

/**
 * Queued cache invalidation job for async cache management
 * Converts synchronous cache invalidation to queued processing
 */
final class QueuedCacheInvalidationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public bool $deleteWhenMissingModels = true;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30]; // Quick retries for cache operations

    public function __construct(
        private readonly string $invalidationType,
        /** @var array<string, mixed> */
        private readonly array $parameters = [],
        private readonly ?string $eventName = null,
        private readonly ?string $eventId = null
    ) {
        $this->onQueue('cache-warming'); // Use dedicated cache queue
    }

    public function handle(ReadModelCacheInvalidator $invalidator): void
    {
        Log::debug('Processing queued cache invalidation', [
            'invalidation_type' => $this->invalidationType,
            'parameters' => $this->parameters,
            'event_name' => $this->eventName,
            'event_id' => $this->eventId,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            match ($this->invalidationType) {
                'campaign' => $this->invalidateCampaignCaches($invalidator),
                'donation' => $this->invalidateDonationCaches($invalidator),
                'organization' => $this->invalidateOrganizationCaches($invalidator),
                'bulk' => $this->invalidateBulkCaches($invalidator),
                'pattern' => $this->invalidateByPattern($invalidator),
                default => throw new Exception("Unknown invalidation type: {$this->invalidationType}"),
            };

            Log::info('Cache invalidation completed successfully', [
                'invalidation_type' => $this->invalidationType,
                'parameters' => $this->parameters,
                'event_name' => $this->eventName,
            ]);

        } catch (Exception $exception) {
            Log::error('Cache invalidation failed', [
                'invalidation_type' => $this->invalidationType,
                'parameters' => $this->parameters,
                'event_name' => $this->eventName,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Cache invalidation job failed permanently', [
            'invalidation_type' => $this->invalidationType,
            'parameters' => $this->parameters,
            'event_name' => $this->eventName,
            'event_id' => $this->eventId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Cache invalidation failures are generally not critical enough to alert admins
        // but we should monitor the pattern
        $this->updateFailureMetrics();
    }

    private function invalidateCampaignCaches(ReadModelCacheInvalidator $invalidator): void
    {
        $campaignId = $this->parameters['campaign_id'] ?? null;
        $organizationId = $this->parameters['organization_id'] ?? null;

        if (! $campaignId) {
            throw new Exception('Campaign ID is required for campaign cache invalidation');
        }

        $invalidator->invalidateCampaignCaches($campaignId, $organizationId);
    }

    private function invalidateDonationCaches(ReadModelCacheInvalidator $invalidator): void
    {
        $campaignId = $this->parameters['campaign_id'] ?? null;
        $organizationId = $this->parameters['organization_id'] ?? null;

        $invalidator->invalidateDonationCaches($campaignId, $organizationId);
    }

    private function invalidateOrganizationCaches(ReadModelCacheInvalidator $invalidator): void
    {
        $organizationId = $this->parameters['organization_id'] ?? null;

        if (! $organizationId) {
            throw new Exception('Organization ID is required for organization cache invalidation');
        }

        $invalidator->invalidateOrganizationCaches($organizationId);
    }

    private function invalidateBulkCaches(ReadModelCacheInvalidator $invalidator): void
    {
        $cacheKeys = $this->parameters['cache_keys'] ?? [];
        $patterns = $this->parameters['patterns'] ?? [];

        if (empty($cacheKeys) && empty($patterns)) {
            throw new Exception('Cache keys or patterns are required for bulk invalidation');
        }

        // Invalidate specific keys
        foreach ($cacheKeys as $key) {
            $invalidator->invalidateKey($key);
        }

        // Invalidate by patterns
        foreach ($patterns as $pattern) {
            $invalidator->invalidatePattern($pattern);
        }
    }

    private function invalidateByPattern(ReadModelCacheInvalidator $invalidator): void
    {
        $pattern = $this->parameters['pattern'] ?? null;

        if (! $pattern) {
            throw new Exception('Pattern is required for pattern-based invalidation');
        }

        $invalidator->invalidatePattern($pattern);
    }

    private function updateFailureMetrics(): void
    {
        $key = 'cache_invalidation_failures:' . now()->format('Y-m-d');
        cache()->increment($key, 1);
        cache()->put($key, cache()->get($key, 0), now()->addDays(7));
    }

    /**
     * Factory methods for common invalidation patterns
     */
    public static function forCampaign(int $campaignId, ?int $organizationId = null, ?string $eventName = null): self
    {
        return new self(
            invalidationType: 'campaign',
            parameters: [
                'campaign_id' => $campaignId,
                'organization_id' => $organizationId,
            ],
            eventName: $eventName,
            eventId: uniqid('campaign_', true)
        );
    }

    public static function forDonation(?int $campaignId = null, ?int $organizationId = null, ?string $eventName = null): self
    {
        return new self(
            invalidationType: 'donation',
            parameters: [
                'campaign_id' => $campaignId,
                'organization_id' => $organizationId,
            ],
            eventName: $eventName,
            eventId: uniqid('donation_', true)
        );
    }

    public static function forOrganization(int $organizationId, ?string $eventName = null): self
    {
        return new self(
            invalidationType: 'organization',
            parameters: [
                'organization_id' => $organizationId,
            ],
            eventName: $eventName,
            eventId: uniqid('organization_', true)
        );
    }

    public static function forPattern(string $pattern, ?string $eventName = null): self
    {
        return new self(
            invalidationType: 'pattern',
            parameters: [
                'pattern' => $pattern,
            ],
            eventName: $eventName,
            eventId: uniqid('pattern_', true)
        );
    }

    /**
     * @param  array<string>  $cacheKeys
     * @param  array<string>  $patterns
     */
    public static function forBulk(array $cacheKeys = [], array $patterns = [], ?string $eventName = null): self
    {
        return new self(
            invalidationType: 'bulk',
            parameters: [
                'cache_keys' => $cacheKeys,
                'patterns' => $patterns,
            ],
            eventName: $eventName,
            eventId: uniqid('bulk_', true)
        );
    }

    /**
     * Dispatch cache invalidation with optional delay to batch operations
     */
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function dispatchCacheInvalidation(
        string $type,
        array $parameters,
        ?string $eventName = null,
        int $delaySeconds = 0
    ): void {
        $job = new self($type, $parameters, $eventName);

        if ($delaySeconds > 0) {
            $job->delay(now()->addSeconds($delaySeconds));
        }

        dispatch($job);
    }

    /**
     * Create a batch of cache invalidation jobs for related entities
     */
    /**
     * @param  array<int, array<string, mixed>>  $invalidations
     */
    public static function createBatchInvalidation(array $invalidations): void
    {
        $jobs = [];

        foreach ($invalidations as $invalidation) {
            $jobs[] = new self(
                $invalidation['type'],
                $invalidation['parameters'],
                $invalidation['event_name'] ?? null,
                $invalidation['event_id'] ?? uniqid('batch_', true)
            );
        }

        if (! empty($jobs)) {
            // Dispatch all jobs with a small delay to allow batching
            foreach ($jobs as $index => $job) {
                $job->delay(now()->addSeconds($index * 2)); // Stagger by 2 seconds
                dispatch($job);
            }

            Log::info('Batch cache invalidation dispatched', [
                'job_count' => count($jobs),
                'invalidations' => array_column($invalidations, 'type'),
            ]);
        }
    }
}

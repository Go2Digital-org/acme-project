<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Application\Event\CampaignActivatedEvent;
use Modules\Campaign\Application\Event\CampaignCompletedEvent;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Campaign\Application\Event\CampaignDeletedEvent;
use Modules\Campaign\Application\Event\CampaignUpdatedEvent;
use Modules\Donation\Application\Event\DonationCancelledEvent;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Application\Event\DonationCreatedEvent;
use Modules\Donation\Application\Event\DonationFailedEvent;
use Modules\Donation\Application\Event\DonationRefundedEvent;
use Modules\Organization\Domain\Event\OrganizationActivatedEvent;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Event\OrganizationDeactivatedEvent;
use Modules\Organization\Domain\Event\OrganizationUpdatedEvent;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;
use Modules\Shared\Infrastructure\Laravel\Jobs\QueuedCacheInvalidationJob;
use Throwable;

/**
 * Queued event listener for cache invalidation
 * Converts synchronous cache invalidation to async processing
 */
final class QueuedCacheInvalidationEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $deleteWhenMissingModels = true;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    /**
     * Register event listeners for queued cache invalidation.
     */
    /**
     * @return array<string, mixed>
     */
    public function subscribe(Dispatcher $events): array
    {
        if (! config('read-models.invalidation.enabled', true)) {
            return [];
        }

        if (! config('read-models.invalidation.queued', false)) {
            return []; // Queued invalidation is opt-in
        }

        $listeners = [];

        // Campaign events
        if (config('read-models.invalidation.listeners.campaign_events', true)) {
            $listeners = array_merge($listeners, [
                CampaignCreatedEvent::class => 'handleCampaignEvent',
                CampaignUpdatedEvent::class => 'handleCampaignEvent',
                CampaignActivatedEvent::class => 'handleCampaignEvent',
                CampaignCompletedEvent::class => 'handleCampaignEvent',
                CampaignDeletedEvent::class => 'handleCampaignEvent',
            ]);
        }

        // Donation events
        if (config('read-models.invalidation.listeners.donation_events', true)) {
            $listeners = array_merge($listeners, [
                DonationCreatedEvent::class => 'handleDonationEvent',
                DonationCompletedEvent::class => 'handleDonationEvent',
                DonationCancelledEvent::class => 'handleDonationEvent',
                DonationFailedEvent::class => 'handleDonationEvent',
                DonationRefundedEvent::class => 'handleDonationEvent',
            ]);
        }

        // Organization events
        if (config('read-models.invalidation.listeners.organization_events', true)) {
            return array_merge($listeners, [
                OrganizationCreatedEvent::class => 'handleOrganizationEvent',
                OrganizationUpdatedEvent::class => 'handleOrganizationEvent',
                OrganizationActivatedEvent::class => 'handleOrganizationEvent',
                OrganizationDeactivatedEvent::class => 'handleOrganizationEvent',
                OrganizationVerifiedEvent::class => 'handleOrganizationEvent',
            ]);
        }

        return $listeners;
    }

    /**
     * Handle campaign-related events.
     */
    public function handleCampaignEvent(
        CampaignCreatedEvent|CampaignUpdatedEvent|CampaignActivatedEvent|CampaignCompletedEvent|CampaignDeletedEvent $event
    ): void {
        Log::debug('Queuing campaign cache invalidation', [
            'event' => $event::class,
            'campaign_id' => $event->campaignId,
            'organization_id' => $event->organizationId,
        ]);

        try {
            $delay = $this->calculateInvalidationDelay($event::class);

            QueuedCacheInvalidationJob::forCampaign(
                campaignId: $event->campaignId,
                organizationId: $event->organizationId,
                eventName: $event::class
            )->delay(now()->addSeconds($delay))->dispatch();

            // For campaign completion, also invalidate related donation caches
            if ($event instanceof CampaignCompletedEvent) {
                QueuedCacheInvalidationJob::forDonation(
                    campaignId: $event->campaignId,
                    organizationId: $event->organizationId,
                    eventName: $event::class
                )->delay(now()->addSeconds($delay + 5))->dispatch();
            }

        } catch (Throwable $exception) {
            Log::error('Failed to queue campaign cache invalidation', [
                'event' => $event::class,
                'campaign_id' => $event->campaignId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle donation-related events.
     */
    public function handleDonationEvent(
        DonationCreatedEvent|DonationCompletedEvent|DonationCancelledEvent|DonationFailedEvent|DonationRefundedEvent $event
    ): void {
        $campaignId = property_exists($event, 'campaignId') ? $event->campaignId : null;
        $organizationId = property_exists($event, 'organizationId') ? $event->organizationId : null; // @phpstan-ignore-line

        Log::debug('Queuing donation cache invalidation', [
            'event' => $event::class,
            'campaign_id' => $campaignId,
            'organization_id' => $organizationId,
        ]);

        try {
            $delay = $this->calculateInvalidationDelay($event::class);

            QueuedCacheInvalidationJob::forDonation(
                campaignId: $campaignId,
                organizationId: $organizationId,
                eventName: $event::class
            )->delay(now()->addSeconds($delay))->dispatch();

            // For completed/refunded donations, also invalidate campaign caches
            if (($event instanceof DonationCompletedEvent || $event instanceof DonationRefundedEvent) && $campaignId) {
                QueuedCacheInvalidationJob::forCampaign(
                    campaignId: $campaignId,
                    organizationId: $organizationId,
                    eventName: $event::class
                )->delay(now()->addSeconds($delay + 3))->dispatch();
            }

        } catch (Throwable $exception) {
            Log::error('Failed to queue donation cache invalidation', [
                'event' => $event::class,
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle organization-related events.
     */
    public function handleOrganizationEvent(
        OrganizationCreatedEvent|OrganizationUpdatedEvent|OrganizationActivatedEvent|OrganizationDeactivatedEvent|OrganizationVerifiedEvent $event
    ): void {
        $organizationId = property_exists($event, 'organizationId') ? $event->organizationId : null;

        if (! $organizationId) {
            Log::warning('Organization event missing organization ID', [
                'event' => $event::class,
            ]);

            return;
        }

        Log::debug('Queuing organization cache invalidation', [
            'event' => $event::class,
            'organization_id' => $organizationId,
        ]);

        try {
            $delay = $this->calculateInvalidationDelay($event::class);

            QueuedCacheInvalidationJob::forOrganization(
                organizationId: $organizationId,
                eventName: $event::class
            )->delay(now()->addSeconds($delay))->dispatch();

            // For verification/activation events, also invalidate related campaign caches
            if ($event instanceof OrganizationVerifiedEvent ||
                $event instanceof OrganizationActivatedEvent) {

                QueuedCacheInvalidationJob::forPattern(
                    pattern: "campaigns:organization:{$organizationId}:*",
                    eventName: $event::class
                )->delay(now()->addSeconds($delay + 5))->dispatch();
            }

        } catch (Throwable $exception) {
            Log::error('Failed to queue organization cache invalidation', [
                'event' => $event::class,
                'organization_id' => $organizationId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle job failure
     */
    /**
     * @param  mixed  $event
     */
    public function failed($event, Throwable $exception): void
    {
        Log::error('Queued cache invalidation listener failed', [
            'event' => is_object($event) ? $event::class : 'unknown',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Determine the queue for cache invalidation jobs
     */
    public function viaQueue(): string
    {
        return 'cache-warming';
    }

    /**
     * Calculate appropriate delay for cache invalidation to allow batching
     */
    private function calculateInvalidationDelay(string $eventClass): int
    {
        // Critical events get immediate invalidation
        $criticalEvents = [
            DonationCompletedEvent::class,
            CampaignCompletedEvent::class,
            OrganizationVerifiedEvent::class,
        ];

        if (in_array($eventClass, $criticalEvents, true)) {
            return 0; // No delay for critical events
        }

        // High priority events get short delay for potential batching
        $highPriorityEvents = [
            DonationCreatedEvent::class,
            CampaignActivatedEvent::class,
            OrganizationActivatedEvent::class,
        ];

        if (in_array($eventClass, $highPriorityEvents, true)) {
            return 5; // 5 second delay
        }

        // Standard events get moderate delay for batching
        $standardEvents = [
            CampaignUpdatedEvent::class,
            OrganizationUpdatedEvent::class,
        ];

        if (in_array($eventClass, $standardEvents, true)) {
            return 15; // 15 second delay
        }

        // Low priority events get longer delay
        return 30; // 30 second delay for other events
    }

    // Method removed as it was unused - detected by PHPStan

    /**
     * Batch multiple cache invalidation operations
     *
     * @param  array<int, array<string, mixed>>  $operations
     */
    public static function batchInvalidations(array $operations): void
    {
        if ($operations === []) {
            return;
        }

        // Group operations by type for efficient processing
        $grouped = [];
        foreach ($operations as $operation) {
            $type = $operation['type'];
            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $operation;
        }

        // Dispatch batched jobs with staggered delays
        $delay = 0;
        foreach ($grouped as $typeOperations) {
            QueuedCacheInvalidationJob::createBatchInvalidation($typeOperations);
            $delay += 5; // Stagger different types by 5 seconds
        }

        Log::info('Batch cache invalidation operations queued', [
            'total_operations' => count($operations),
            'types' => array_keys($grouped),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

use Illuminate\Events\Dispatcher;
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

/**
 * Event listener for cache invalidation based on domain events.
 */
class CacheInvalidationEventListener
{
    public function __construct(
        private readonly ReadModelCacheInvalidator $invalidator
    ) {}

    /**
     * Register event listeners for cache invalidation.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        if (! config('read-models.invalidation.enabled', true)) {
            return [];
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
        $this->invalidator->invalidateCampaignCaches(
            $event->campaignId,
            $event->organizationId
        );
    }

    /**
     * Handle donation-related events.
     */
    public function handleDonationEvent(
        DonationCreatedEvent|DonationCompletedEvent|DonationCancelledEvent|DonationFailedEvent|DonationRefundedEvent $event
    ): void {
        $campaignId = property_exists($event, 'campaignId') ? $event->campaignId : null;
        $organizationId = property_exists($event, 'organizationId') ? $event->organizationId : null; // @phpstan-ignore-line

        $this->invalidator->invalidateDonationCaches($campaignId, $organizationId);
    }

    /**
     * Handle organization-related events.
     */
    public function handleOrganizationEvent(
        OrganizationCreatedEvent|OrganizationUpdatedEvent|OrganizationActivatedEvent|OrganizationDeactivatedEvent|OrganizationVerifiedEvent $event
    ): void {
        $organizationId = property_exists($event, 'organizationId') ? $event->organizationId : null;

        if ($organizationId) {
            $this->invalidator->invalidateOrganizationCaches($organizationId);
        }
    }
}

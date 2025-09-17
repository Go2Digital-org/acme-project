<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Shared\Domain\Event\DomainEventInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Handles campaign events for cross-module communication
 *
 * This listener responds to campaign lifecycle events and coordinates
 * actions across other bounded contexts.
 */
class CampaignEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle campaign created events
     */
    public function handleCampaignCreated(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->info('Handling campaign created event', [
            'campaign_id' => $eventData['campaign_id'] ?? null,
            'organization_id' => $eventData['organization_id'] ?? null,
        ]);

        // Notify organization about successful campaign creation
        if (isset($eventData['organization_id'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $eventData['organization_id'],
                title: 'Campaign Created Successfully',
                message: "Your campaign '{$eventData['title']}' has been created and is ready for activation.",
                type: 'campaign_created',
                priority: 'normal',
                metadata: [
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'organization_id' => $eventData['organization_id'],
                    'event_type' => 'campaign.created',
                    'recipient_type' => 'organization',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Setup analytics tracking
        // - Create default social media posts
        // - Schedule reminder notifications
    }

    /**
     * Handle campaign activated events
     */
    public function handleCampaignActivated(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->info('Handling campaign activated event', [
            'campaign_id' => $eventData['campaign_id'] ?? null,
        ]);

        // Notify stakeholders about campaign going live
        if (isset($eventData['organization_id'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $eventData['organization_id'],
                title: 'Campaign is Now Live!',
                message: "Your campaign '{$eventData['title']}' is now active and accepting donations.",
                type: 'campaign_activated',
                priority: 'high',
                metadata: [
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'organization_id' => $eventData['organization_id'],
                    'event_type' => 'campaign.activated',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Send email announcements
        // - Update external integrations
        // - Start social media automation
    }

    /**
     * Handle campaign completed events
     */
    public function handleCampaignCompleted(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->info('Handling campaign completed event', [
            'campaign_id' => $eventData['campaign_id'] ?? null,
        ]);

        // Congratulate organization on completion
        if (isset($eventData['organization_id'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $eventData['organization_id'],
                title: 'Campaign Successfully Completed!',
                message: "Congratulations! Your campaign '{$eventData['title']}' has reached its target goal.",
                type: 'campaign_completed',
                priority: 'high',
                metadata: [
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'organization_id' => $eventData['organization_id'],
                    'final_amount' => $eventData['final_amount'] ?? null,
                    'target_amount' => $eventData['target_amount'] ?? null,
                    'event_type' => 'campaign.completed',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Generate final reports
        // - Process final payments
        // - Archive campaign data
        // - Send thank you messages to donors
    }

    /**
     * Generic event handler
     */
    public function handle(DomainEventInterface $event): void
    {
        $this->logger->debug('Campaign event received', [
            'event_name' => $event->getEventName(),
            'event_context' => $event->getContext(),
            'aggregate_id' => $event->getAggregateId(),
        ]);

        // Route to specific handlers based on event type
        match ($event->getEventName()) {
            'campaign.created' => $this->handleCampaignCreated($event),
            'campaign.activated' => $this->handleCampaignActivated($event),
            'campaign.completed' => $this->handleCampaignCompleted($event),
            default => $this->logger->info('Unhandled campaign event', [
                'event_name' => $event->getEventName(),
            ])
        };
    }

    /**
     * Determine the queue to use
     */
    public function viaQueue(): string
    {
        return 'campaign-events';
    }

    /**
     * Handle a job failure
     */
    public function failed(DomainEventInterface $event, Throwable $exception): void
    {
        $this->logger->error('Campaign event listener failed', [
            'event_name' => $event->getEventName(),
            'aggregate_id' => $event->getAggregateId(),
            'error' => $exception->getMessage(),
        ]);
    }
}

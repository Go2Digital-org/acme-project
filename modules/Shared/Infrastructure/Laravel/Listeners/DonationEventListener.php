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
 * Handles donation events for cross-module communication
 *
 * This listener responds to donation events and coordinates notifications
 * and other cross-cutting concerns.
 */
class DonationEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle donation completed events
     */
    public function handleDonationCompleted(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->info('Handling donation completed event', [
            'donation_id' => $eventData['donation_id'] ?? null,
            'campaign_id' => $eventData['campaign_id'] ?? null,
            'amount' => $eventData['amount'] ?? null,
        ]);

        // Thank the donor
        if (isset($eventData['donor_email'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: $eventData['donor_email'],
                title: 'Thank You for Your Donation!',
                message: "Thank you for your generous donation of {$eventData['amount']} to support this campaign.",
                type: 'donation_thank_you',
                priority: 'normal',
                metadata: [
                    'donation_id' => $eventData['donation_id'] ?? null,
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'amount' => $eventData['amount'] ?? null,
                    'event_type' => 'donation.completed',
                ],
                scheduledFor: null,
            ));
        }

        // Notify organization about new donation
        if (isset($eventData['organization_id'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $eventData['organization_id'],
                title: 'New Donation Received!',
                message: "Your campaign has received a new donation of {$eventData['amount']}.",
                type: 'donation_received',
                priority: 'normal',
                metadata: [
                    'donation_id' => $eventData['donation_id'] ?? null,
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'amount' => $eventData['amount'] ?? null,
                    'event_type' => 'donation.completed',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Update campaign progress
        // - Send social media updates
        // - Update analytics
        // - Generate tax receipts
    }

    /**
     * Handle donation failed events
     */
    public function handleDonationFailed(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->warning('Handling donation failed event', [
            'donation_id' => $eventData['donation_id'] ?? null,
            'campaign_id' => $eventData['campaign_id'] ?? null,
            'reason' => $eventData['failure_reason'] ?? 'Unknown',
        ]);

        // Notify donor about the failure
        if (isset($eventData['donor_email'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: $eventData['donor_email'],
                title: 'Donation Processing Failed',
                message: 'We encountered an issue processing your donation. Please try again or contact support.',
                type: 'donation_failed',
                priority: 'high',
                metadata: [
                    'donation_id' => $eventData['donation_id'] ?? null,
                    'campaign_id' => $eventData['campaign_id'] ?? null,
                    'failure_reason' => $eventData['failure_reason'] ?? 'Unknown',
                    'event_type' => 'donation.failed',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Log for fraud detection
        // - Update failure metrics
        // - Retry mechanisms
    }

    /**
     * Handle donation refunded events
     */
    public function handleDonationRefunded(DomainEventInterface $event): void
    {
        $eventData = $event->getEventData();

        $this->logger->info('Handling donation refunded event', [
            'donation_id' => $eventData['donation_id'] ?? null,
            'refund_amount' => $eventData['refund_amount'] ?? null,
        ]);

        // Notify donor about refund
        if (isset($eventData['donor_email'])) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: $eventData['donor_email'],
                title: 'Donation Refunded',
                message: "Your donation has been refunded as requested. The refund amount of {$eventData['refund_amount']} will appear in your account within 3-5 business days.",
                type: 'donation_refunded',
                priority: 'normal',
                metadata: [
                    'donation_id' => $eventData['donation_id'] ?? null,
                    'refund_amount' => $eventData['refund_amount'] ?? null,
                    'event_type' => 'donation.refunded',
                ],
                scheduledFor: null,
            ));
        }

        // Could trigger:
        // - Update campaign totals
        // - Update financial reports
        // - Log refund metrics
    }

    /**
     * Generic event handler
     */
    public function handle(DomainEventInterface $event): void
    {
        $this->logger->debug('Donation event received', [
            'event_name' => $event->getEventName(),
            'event_context' => $event->getContext(),
            'aggregate_id' => $event->getAggregateId(),
        ]);

        // Route to specific handlers based on event type
        match ($event->getEventName()) {
            'donation.completed' => $this->handleDonationCompleted($event),
            'donation.failed' => $this->handleDonationFailed($event),
            'donation.refunded' => $this->handleDonationRefunded($event),
            default => $this->logger->info('Unhandled donation event', [
                'event_name' => $event->getEventName(),
            ])
        };
    }

    /**
     * Determine the queue to use
     */
    public function viaQueue(): string
    {
        return 'donation-events';
    }

    /**
     * Handle a job failure
     */
    public function failed(DomainEventInterface $event, Throwable $exception): void
    {
        $this->logger->error('Donation event listener failed', [
            'event_name' => $event->getEventName(),
            'aggregate_id' => $event->getAggregateId(),
            'error' => $exception->getMessage(),
        ]);
    }
}

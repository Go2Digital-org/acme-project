<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Event\OrganizationDeactivatedEvent;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Handles organization events for cross-module communication
 *
 * This listener demonstrates how to handle organization events and trigger
 * appropriate actions in other bounded contexts like Notification.
 */
class OrganizationEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle organization created events
     */
    public function handleOrganizationCreated(OrganizationCreatedEvent $event): void
    {
        $this->logger->info('Handling organization created event', [
            'organization_id' => $event->organizationId,
            'organization_name' => $event->name,
        ]);

        // Send welcome notification to organization admin
        $this->createWelcomeNotification($event);

        // Could trigger other actions like:
        // - Setting up default campaigns
        // - Creating default organization settings
        // - Registering with external services
    }

    /**
     * Handle organization verified events
     */
    public function handleOrganizationVerified(OrganizationVerifiedEvent $event): void
    {
        $this->logger->info('Handling organization verified event', [
            'organization_id' => $event->organizationId,
        ]);

        // Notify about verification success
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: (string) $event->organizationId,
            title: 'Organization Verified Successfully',
            message: 'Your organization has been verified and can now create campaigns.',
            type: 'organization_verified',
            priority: 'high',
            metadata: [
                'organization_id' => $event->organizationId,
                'event_type' => 'organization.verified',
            ],
            scheduledFor: null,
        ));

        // Could trigger:
        // - Enable advanced features
        // - Update organization capabilities
        // - Send verification emails to stakeholders
    }

    /**
     * Handle organization deactivated events
     */
    public function handleOrganizationDeactivated(OrganizationDeactivatedEvent $event): void
    {
        $this->logger->info('Handling organization deactivated event', [
            'organization_id' => $event->organizationId,
        ]);

        // Notify about deactivation
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: (string) $event->organizationId,
            title: 'Organization Deactivated',
            message: 'Your organization has been deactivated. Contact support if you believe this is an error.',
            type: 'organization_deactivated',
            priority: 'urgent',
            metadata: [
                'organization_id' => $event->organizationId,
                'event_type' => 'organization.deactivated',
                'reason' => $event->reason ?? 'Not specified',
            ],
            scheduledFor: null,
        ));

        // Could trigger:
        // - Pause all active campaigns
        // - Disable API access
        // - Archive organization data
    }

    /**
     * Generic event handler for debugging
     */
    public function handle(DomainEventInterface $event): void
    {
        $this->logger->debug('Organization event received', [
            'event_name' => $event->getEventName(),
            'event_context' => $event->getContext(),
            'aggregate_id' => $event->getAggregateId(),
            'event_data' => $event->getEventData(),
        ]);

        // Route to specific handlers based on event type
        match ($event->getEventName()) {
            'organization.created' => $event instanceof OrganizationCreatedEvent ? $this->handleOrganizationCreated($event) : null,
            'organization.verified' => $event instanceof OrganizationVerifiedEvent ? $this->handleOrganizationVerified($event) : null,
            'organization.deactivated' => $event instanceof OrganizationDeactivatedEvent ? $this->handleOrganizationDeactivated($event) : null,
            default => $this->logger->info('Unhandled organization event', [
                'event_name' => $event->getEventName(),
            ])
        };
    }

    /**
     * Create welcome notification for new organization
     */
    private function createWelcomeNotification(OrganizationCreatedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: (string) $event->organizationId,
            title: 'Welcome to ACME CSR Platform',
            message: "Welcome {$event->name}! Your organization has been successfully registered. Start creating your first campaign to make a positive impact.",
            type: 'organization_welcome',
            priority: 'normal',
            metadata: [
                'organization_id' => $event->organizationId,
                'organization_name' => $event->name,
                'event_type' => 'organization.created',
                'onboarding_step' => 'welcome',
            ],
            scheduledFor: null,
        ));
    }

    /**
     * Determine the queue to use
     */
    public function viaQueue(): string
    {
        return 'organization-events';
    }

    /**
     * Handle a job failure
     */
    public function failed(DomainEventInterface $event, Throwable $exception): void
    {
        $this->logger->error('Organization event listener failed', [
            'event_name' => $event->getEventName(),
            'aggregate_id' => $event->getAggregateId(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

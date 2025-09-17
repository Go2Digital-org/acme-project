<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when organizations are verified.
 */
final class OrganizationVerifiedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(OrganizationVerifiedEvent $event): void
    {
        try {
            // Notify organization administrators
            $this->notifyOrganizationAdmins($event);

            // Notify platform administrators
            $this->notifyPlatformAdmins($event);

            $this->logger->info('Organization verification notifications processed', [
                'organization_id' => $event->organizationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process organization verification notifications', [
                'organization_id' => $event->organizationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(OrganizationVerifiedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process organization verification notifications', [
            'organization_id' => $event->organizationId ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }

    private function notifyOrganizationAdmins(OrganizationVerifiedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: 'organization:' . $event->organizationId,
            title: 'Organization Verification Complete! ✅',
            message: 'Congratulations! Your organization has been successfully verified and can now create campaigns.', // Will be resolved to organization admins
            type: NotificationType::ORGANIZATION_VERIFIED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'organization_id' => $event->organizationId,
                'verified_at' => now()->toISOString(),
                'can_create_campaigns' => true,
                'actions' => [
                    [
                        'label' => 'Create Your First Campaign',
                        'url' => '/campaigns/create',
                        'primary' => true,
                    ],
                    [
                        'label' => 'View Organization Profile',
                        'url' => "/organizations/{$event->organizationId}",
                        'primary' => false,
                    ],
                    [
                        'label' => 'Setup Guide',
                        'url' => '/help/organization-setup',
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'organization_id' => $event->organizationId,
                'verified_at' => now()->toISOString(),
                'can_create_campaigns' => true,
            ],
        ));

        // Note: Would resolve organization admins in real implementation
        // $this->notificationHandler->handle($command);
    }

    private function notifyPlatformAdmins(OrganizationVerifiedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: 'admin:platform',
            title: 'Organization Verified',
            message: 'An organization has been successfully verified and can now create campaigns.', // Will be resolved to platform admins
            type: NotificationType::ADMIN_ALERT->value,
            channel: NotificationChannel::IN_APP->value,
            priority: NotificationPriority::LOW->value,
            senderId: null,
            data: [
                'organization_id' => $event->organizationId,
                'verified_at' => now()->toISOString(),
                'event_type' => 'organization_verified',
                'actions' => [
                    [
                        'label' => 'View Organization',
                        'url' => "/admin/organizations/{$event->organizationId}",
                        'primary' => true,
                    ],
                ],
            ],
            metadata: [
                'organization_id' => $event->organizationId,
                'verified_at' => now()->toISOString(),
                'event_type' => 'organization_verified',
            ],
        ));

        // Note: Would resolve platform admins in real implementation
        // $this->notificationHandler->handle($command);
    }
}

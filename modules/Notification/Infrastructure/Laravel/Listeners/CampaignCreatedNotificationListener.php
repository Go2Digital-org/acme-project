<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when campaigns are created.
 *
 * This listener handles the business logic of notifying relevant users
 * about new campaign creation events within the platform.
 */
final class CampaignCreatedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the queued listener may run.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Handle the campaign created event.
     */
    public function handle(CampaignCreatedEvent $event): void
    {
        try {
            // Notify campaign creator
            $this->notifyCreator($event);

            // Notify organization administrators
            $this->notifyOrganizationAdmins($event);

            // Notify platform administrators for review
            $this->notifyPlatformAdmins($event);

            // Notify employees who have shown interest in similar campaigns
            $this->notifyInterestedEmployees($event);

            $this->logger->info('Campaign creation notifications processed', [
                'campaign_id' => $event->campaignId,
                'creator_id' => $event->userId,
                'organization_id' => $event->organizationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process campaign creation notifications', [
                'campaign_id' => $event->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(CampaignCreatedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process campaign creation notifications', [
            'campaign_id' => $event->campaignId ?? 'unknown',
            'creator_id' => $event->userId ?? 'unknown',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Notify the campaign creator about successful creation.
     */
    private function notifyCreator(CampaignCreatedEvent $event): void
    {
        $command = new CreateNotificationCommand(
            recipientId: (string) $event->userId,
            title: 'Campaign Created Successfully',
            message: "Your campaign '{$event->title}' has been created successfully and is pending review.",
            type: NotificationType::CAMPAIGN_CREATED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Edit Campaign',
                        'url' => "/campaigns/{$event->campaignId}/edit",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'campaign_title' => $event->title,
                'goal_amount' => $event->goalAmount,
                'organization_id' => $event->organizationId,
                'status' => 'pending_review',
            ],
            scheduledFor: null,
        );

        $this->notificationHandler->handle($command);
    }

    /**
     * Notify organization administrators about the new campaign.
     */
    private function notifyOrganizationAdmins(CampaignCreatedEvent $event): void
    {
        // Get the campaign to check its status
        $campaign = $this->campaignRepository->findById($event->campaignId);

        if (! $campaign instanceof Campaign) {
            $this->logger->warning('Campaign not found for organization admin notification', [
                'campaign_id' => $event->campaignId,
            ]);

            return;
        }

        // Only notify when campaign status is pending_approval or draft
        if (! in_array($campaign->status->value, ['pending_approval', 'draft'])) {
            return;
        }

        // Get organization admin users (users with 'admin' or 'organization_admin' role in the same organization)
        $orgAdmins = User::role(['admin', 'organization_admin'])
            ->where('organization_id', $event->organizationId)
            ->get();

        if ($orgAdmins->isEmpty()) {
            $this->logger->info('No organization admin users found for campaign notification', [
                'campaign_id' => $event->campaignId,
                'organization_id' => $event->organizationId,
            ]);

            return;
        }

        // Send notification to each organization admin
        foreach ($orgAdmins as $admin) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $admin->id,
                title: 'New Campaign Created in Your Organization',
                message: "A new campaign '{$event->title}' has been created in your organization and may require review.",
                type: NotificationType::CAMPAIGN_PENDING_REVIEW->value,
                channel: NotificationChannel::EMAIL->value,
                priority: NotificationPriority::MEDIUM->value,
                senderId: null,
                data: [
                    'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
                    'actions' => [
                        [
                            'label' => 'Review Campaign',
                            'url' => "/admin/campaigns/{$event->campaignId}",
                            'primary' => true,
                        ],
                        [
                            'label' => 'View Details',
                            'url' => "/campaigns/{$event->campaignId}",
                            'primary' => false,
                        ],
                    ],
                ],
                metadata: [
                    'campaign_id' => $event->campaignId,
                    'campaign_title' => $event->title,
                    'creator_id' => $event->userId,
                    'organization_id' => $event->organizationId,
                    'goal_amount' => $event->goalAmount,
                    'campaign_status' => $campaign->status->value,
                    'requires_action' => $campaign->status->value === 'pending_approval',
                ],
                scheduledFor: null,
            ));
        }

        $this->logger->info('Organization admin notifications sent for campaign', [
            'campaign_id' => $event->campaignId,
            'organization_id' => $event->organizationId,
            'admin_count' => $orgAdmins->count(),
        ]);
    }

    /**
     * Notify platform administrators for campaign oversight.
     */
    private function notifyPlatformAdmins(CampaignCreatedEvent $event): void
    {
        // Get the campaign to check its status
        $campaign = $this->campaignRepository->findById($event->campaignId);

        if (! $campaign instanceof Campaign) {
            $this->logger->warning('Campaign not found for platform admin notification', [
                'campaign_id' => $event->campaignId,
            ]);

            return;
        }

        // Only notify when campaign status is pending_approval
        if ($campaign->status->value !== 'pending_approval') {
            return;
        }

        // Get all super_admin users
        $superAdmins = User::role('super_admin')->get();

        if ($superAdmins->isEmpty()) {
            $this->logger->warning('No super_admin users found for campaign approval notification', [
                'campaign_id' => $event->campaignId,
            ]);

            return;
        }

        // Send notification to each super_admin user
        foreach ($superAdmins as $admin) {
            $this->notificationHandler->handle(new CreateNotificationCommand(
                recipientId: (string) $admin->id,
                title: 'New Campaign Requires Approval',
                message: "Campaign '{$event->title}' has been submitted for approval and requires review.",
                type: NotificationType::ADMIN_ALERT->value,
                channel: NotificationChannel::EMAIL->value,
                priority: NotificationPriority::HIGH->value,
                senderId: null,
                data: [
                    'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
                    'actions' => [
                        [
                            'label' => 'Review Campaign',
                            'url' => "/admin/campaigns/{$event->campaignId}",
                            'primary' => true,
                        ],
                        [
                            'label' => 'View Details',
                            'url' => "/campaigns/{$event->campaignId}",
                            'primary' => false,
                        ],
                    ],
                ],
                metadata: [
                    'campaign_id' => $event->campaignId,
                    'campaign_title' => $event->title,
                    'creator_id' => $event->userId,
                    'organization_id' => $event->organizationId,
                    'goal_amount' => $event->goalAmount,
                    'campaign_status' => $campaign->status->value,
                    'event_type' => 'campaign_pending_approval',
                    'requires_action' => true,
                ],
                scheduledFor: null,
            ));
        }

        $this->logger->info('Platform admin notifications sent for campaign approval', [
            'campaign_id' => $event->campaignId,
            'admin_count' => $superAdmins->count(),
        ]);
    }

    /**
     * Notify employees who might be interested in this campaign.
     */
    private function notifyInterestedEmployees(CampaignCreatedEvent $event): void
    {
        // This would typically:
        // 1. Query employees who have donated to similar campaigns
        // 2. Check user preferences for campaign notifications
        // 3. Respect notification frequency settings
        // 4. Use ML/AI to determine interest levels

        $this->logger->debug('Interested employee notifications would be processed', [
            'campaign_id' => $event->campaignId,
            'organization_id' => $event->organizationId,
        ]);

        // Example notification for interested employees
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '1', // Placeholder - will be resolved to interested employees
            title: 'New Campaign You Might Like',
            message: "A new campaign '{$event->title}' has been created that matches your interests.",
            type: NotificationType::CAMPAIGN_SUGGESTION->value,
            channel: NotificationChannel::PUSH->value,
            priority: NotificationPriority::LOW->value,
            senderId: null,
            data: [
                'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Dismiss',
                        'action' => 'dismiss',
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'campaign_title' => $event->title,
                'organization_id' => $event->organizationId,
                'goal_amount' => $event->goalAmount,
                'reason' => 'similar_interests',
            ],
            scheduledFor: now()->addHours(2), // Delay to avoid spam
        ));

        // Note: In a real implementation, this would iterate through interested employees
        // $this->notificationHandler->handle($command);
    }
}

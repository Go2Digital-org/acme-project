<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Campaign\Application\Event\CampaignCompletedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when campaigns are completed.
 */
final class CampaignCompletedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(CampaignCompletedEvent $event): void
    {
        try {
            // Notify campaign creator
            $this->notifyCreator($event);

            // Notify all campaign supporters
            $this->notifySupporters($event);

            // Notify organization administrators
            $this->notifyOrganizationAdmins($event);

            $this->logger->info('Campaign completion notifications processed', [
                'campaign_id' => $event->campaignId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process campaign completion notifications', [
                'campaign_id' => $event->campaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(CampaignCompletedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process campaign completion notifications', [
            'campaign_id' => $event->campaignId ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }

    private function notifyCreator(CampaignCompletedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to campaign creator
            title: 'Congratulations! Your Campaign Reached Its Goal! 🎉',
            message: 'Amazing news! Your campaign has successfully reached its funding goal.',
            type: NotificationType::CAMPAIGN_COMPLETED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'campaign_id' => $event->campaignId,
                'achievement' => 'goal_reached',
                'actions' => [
                    [
                        'label' => 'View Campaign Results',
                        'url' => "/campaigns/{$event->campaignId}/results",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Thank Supporters',
                        'url' => "/campaigns/{$event->campaignId}/thank-you",
                        'primary' => false,
                    ],
                    [
                        'label' => 'Share Success',
                        'url' => "/campaigns/{$event->campaignId}/share?completed=true",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'completion_date' => now()->toISOString(),
                'achievement' => 'goal_reached',
            ],
        ));
    }

    private function notifySupporters(CampaignCompletedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to all campaign supporters
            title: 'Campaign Success! Goal Achieved 🎉',
            message: 'Great news! A campaign you supported has reached its funding goal.',
            type: NotificationType::CAMPAIGN_SUCCESS->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'campaign_id' => $event->campaignId,
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Share Success',
                        'url' => "/campaigns/{$event->campaignId}/share?supporter=true",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'user_contribution' => null, // Would be calculated per user
                'total_raised' => null, // Would be fetched
                'total_supporters' => null, // Would be calculated
            ],
        ));
    }

    private function notifyOrganizationAdmins(CampaignCompletedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to organization admins
            title: 'Campaign Successfully Completed',
            message: 'One of your organization\'s campaigns has reached its funding goal.',
            type: NotificationType::CAMPAIGN_COMPLETED->value,
            channel: NotificationChannel::IN_APP->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'campaign_id' => $event->campaignId,
                'next_steps_required' => true,
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/admin/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Process Funds',
                        'url' => "/admin/campaigns/{$event->campaignId}/process-funds",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'completion_date' => now()->toISOString(),
                'next_steps_required' => true,
            ],
        ));
    }
}

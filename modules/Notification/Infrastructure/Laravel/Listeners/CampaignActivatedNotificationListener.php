<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Campaign\Application\Event\CampaignActivatedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when campaigns are activated.
 */
final class CampaignActivatedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(CampaignActivatedEvent $event): void
    {
        try {
            // Notify campaign creator
            $this->notifyCreator($event);

            // Notify interested employees
            $this->notifyInterestedEmployees($event);

            $this->logger->info('Campaign activation notifications processed', [
                'campaign_id' => $event->campaignId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process campaign activation notifications', [
                'campaign_id' => $event->campaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(CampaignActivatedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process campaign activation notifications', [
            'campaign_id' => $event->campaignId ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }

    private function notifyCreator(CampaignActivatedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to campaign creator
            title: 'Your Campaign is Now Live! 🎉',
            message: 'Great news! Your campaign has been approved and is now live for donations.',
            type: NotificationType::CAMPAIGN_ACTIVATED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
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
                        'label' => 'Share Campaign',
                        'url' => "/campaigns/{$event->campaignId}/share",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'activation_date' => now()->toISOString(),
            ],
        ));

        // Note: Would resolve campaign creator in real implementation
        // $this->notificationHandler->handle($command);
    }

    private function notifyInterestedEmployees(CampaignActivatedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to interested employees
            title: 'New Campaign is Live',
            message: 'A campaign you might be interested in is now accepting donations.',
            type: NotificationType::CAMPAIGN_AVAILABLE->value,
            channel: NotificationChannel::PUSH->value,
            priority: NotificationPriority::LOW->value,
            senderId: null,
            data: [
                'campaign_id' => $event->campaignId,
                'reason' => 'matching_interests',
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'reason' => 'matching_interests',
            ],
            scheduledFor: now()->addHours(1),
        ));

        // Note: Would resolve interested employees in real implementation
        // $this->notificationHandler->handle($command);
    }
}

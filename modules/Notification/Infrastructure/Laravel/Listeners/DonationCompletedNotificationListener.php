<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when donations are completed.
 */
final class DonationCompletedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DonationCompletedEvent $event): void
    {
        try {
            // Send final confirmation to donor
            $this->sendFinalConfirmation($event);

            // Update campaign creator about completed payment
            $this->notifyCampaignCreator($event);

            $this->logger->info('Donation completion notifications processed', [
                'donation_id' => $event->donationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process donation completion notifications', [
                'donation_id' => $event->donationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(DonationCompletedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process donation completion notifications', [
            'donation_id' => $event->donationId ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }

    private function sendFinalConfirmation(DonationCompletedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to donor
            title: 'Donation Processing Complete',
            message: 'Your donation has been successfully processed and funds have been allocated.',
            type: NotificationType::DONATION_PROCESSED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'processing_complete' => true,
                'funds_allocated' => true,
                'final_confirmation' => true,
                'actions' => [
                    [
                        'label' => 'View Impact Report',
                        'url' => "/donations/{$event->donationId}/impact",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Download Tax Receipt',
                        'url' => "/donations/{$event->donationId}/receipt",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'processing_complete' => true,
                'funds_allocated' => true,
                'final_confirmation' => true,
            ],
        ));

        // Note: Would resolve donor in real implementation
        // $this->notificationHandler->handle($command);
    }

    private function notifyCampaignCreator(DonationCompletedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to campaign creator
            title: 'Donation Funds Available',
            message: 'A donation has been processed and funds are now available for your campaign.',
            type: NotificationType::DONATION_PROCESSED->value,
            channel: NotificationChannel::IN_APP->value,
            priority: NotificationPriority::LOW->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'funds_available' => true,
                'actions' => [
                    [
                        'label' => 'View Campaign Funds',
                        'url' => "/campaigns/{$event->campaignId}/funds",
                        'primary' => true,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'funds_available' => true,
            ],
        ));

        // Note: Would resolve campaign creator in real implementation
        // $this->notificationHandler->handle($command);
    }
}

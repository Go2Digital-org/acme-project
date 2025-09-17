<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Donation\Application\Event\DonationFailedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when donations fail.
 */
final class DonationFailedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DonationFailedEvent $event): void
    {
        try {
            // Notify donor about payment failure
            $this->notifyDonor($event);

            // Alert admins about payment issues if recurring
            $this->alertAdminsIfNeeded($event);

            $this->logger->info('Donation failure notifications processed', [
                'donation_id' => $event->donationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process donation failure notifications', [
                'donation_id' => $event->donationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(DonationFailedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process donation failure notifications', [
            'donation_id' => $event->donationId ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }

    private function notifyDonor(DonationFailedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to donor
            title: 'Donation Payment Failed',
            message: 'We were unable to process your donation payment. Please try again or contact support.',
            type: NotificationType::DONATION_FAILED->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'payment_failed' => true,
                'retry_available' => true,
                'support_contact' => 'support@acme-corp.com',
                'actions' => [
                    [
                        'label' => 'Retry Payment',
                        'url' => "/donations/{$event->donationId}/retry",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Update Payment Method',
                        'url' => "/donations/{$event->donationId}/payment-method",
                        'primary' => false,
                    ],
                    [
                        'label' => 'Contact Support',
                        'url' => '/support/contact',
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'payment_failed' => true,
                'retry_available' => true,
                'support_contact' => 'support@acme-corp.com',
            ],
        ));

        // Note: Would resolve donor in real implementation
        // $this->notificationHandler->handle($command);
    }

    private function alertAdminsIfNeeded(DonationFailedEvent $event): void
    {
        // Only alert admins for high-value donations or recurring failures
        $shouldAlertAdmins = $this->shouldAlertAdmins($event);

        if (! $shouldAlertAdmins) {
            return;
        }

        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to platform admins
            title: 'High-Value Donation Failed',
            message: 'A high-value donation payment has failed and may require attention.',
            type: NotificationType::ADMIN_ALERT->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'alert_type' => 'payment_failure',
                'requires_attention' => true,
                'actions' => [
                    [
                        'label' => 'Review Donation',
                        'url' => "/admin/donations/{$event->donationId}",
                        'primary' => true,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'alert_type' => 'payment_failure',
                'requires_attention' => true,
            ],
        ));

        // Note: Would resolve admins in real implementation
        // $this->notificationHandler->handle($command);
    }

    /**
     * Determine if admin should be alerted based on donation failure conditions.
     */
    private function shouldAlertAdmins(DonationFailedEvent $event): bool
    {
        // Alert admins for high-value donations (over $1000)
        // In real implementation, would check failure count or pattern
        // Amount in cents
        return $event->amount > 100000;
    }
}

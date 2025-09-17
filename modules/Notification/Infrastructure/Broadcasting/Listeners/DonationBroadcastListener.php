<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Listeners;

use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Donation\Application\Event\DonationProcessedEvent;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that broadcasts donation-related notifications.
 *
 * This listener handles domain events related to donations and broadcasts
 * appropriate notifications to admin dashboards and relevant channels.
 */
class DonationBroadcastListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationBroadcaster $broadcaster,
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle donation processed events.
     */
    public function handleDonationProcessed(DonationProcessedEvent $event): void
    {
        try {
            $donation = $this->donationRepository->findById($event->donationId);

            if (! $donation instanceof Donation) {
                $this->logger->warning('Cannot broadcast donation processed: donation not found', [
                    'donation_id' => $event->donationId,
                ]);

                return;
            }

            $this->broadcaster->broadcastDonationNotification(
                $donation,
                'donation.processed',
                [
                    'processed_at' => $event->occurredAt->format('c'),
                    'amount' => $event->amount,
                    'status' => 'processed', // Use a literal status value
                ],
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast donation processed notification', [
                'donation_id' => $event->donationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * The queue to push the job onto.
     */
    public function viaQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, Throwable $exception): void
    {
        $eventType = class_basename($event);

        $this->logger->error('Donation broadcast permanently failed', [
            'event_type' => $eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}

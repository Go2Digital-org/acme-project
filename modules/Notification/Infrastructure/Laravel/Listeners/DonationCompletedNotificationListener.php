<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Notification\Application\Command\SendDonationNotificationCommand;
use Modules\Notification\Application\Command\SendDonationNotificationCommandHandler;
use Modules\User\Infrastructure\Laravel\Models\User;
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
        private readonly SendDonationNotificationCommandHandler $notificationHandler,
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DonationCompletedEvent $event): void
    {
        try {
            // Get donation and campaign details
            $donation = $this->donationRepository->findById($event->donationId);
            $campaign = $this->campaignRepository->findById($event->campaignId);

            if (! $donation || ! $campaign) {
                $this->logger->warning('Donation or campaign not found for notification', [
                    'donation_id' => $event->donationId,
                    'campaign_id' => $event->campaignId,
                ]);

                return;
            }

            // Get donor name
            $donorName = null;
            if ($event->userId) {
                $user = User::find($event->userId);
                $donorName = $user ? $user->name : null;
            }

            // Send notifications using the proper command
            $command = new SendDonationNotificationCommand(
                donationId: $event->donationId,
                userId: $event->userId ?? '',
                campaignId: $event->campaignId,
                amount: $event->amount,
                currency: $event->currency,
                campaignTitle: $campaign->title ?? 'Campaign',
                donorName: $donorName,
            );

            $this->notificationHandler->handle($command);

            $this->logger->info('Donation completion notifications processed', [
                'donation_id' => $event->donationId,
                'user_id' => $event->userId,
                'campaign_id' => $event->campaignId,
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
}

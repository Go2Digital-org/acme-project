<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Exception;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\LoggerInterface;

final readonly class SendDonationNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private LoggerInterface $logger,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof SendDonationNotificationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Send notification to donor
        $this->sendDonorNotification($command);

        // Send notification to campaign creator
        $this->sendCampaignCreatorNotification($command);

        return null;
    }

    private function sendDonorNotification(SendDonationNotificationCommand $command): void
    {
        try {
            $user = User::find($command->userId);

            if (! $user) {
                $this->logger->warning('User not found for donation notification', [
                    'user_id' => $command->userId,
                    'donation_id' => $command->donationId,
                ]);

                return;
            }

            $notificationData = $this->buildDonorNotificationData($command);

            $notification = new DatabaseNotification;
            $notification->id = Str::uuid()->toString();
            $notification->type = 'App\Notifications\Donation' . ucfirst($command->status);
            $notification->notifiable_type = $user::class;
            $notification->notifiable_id = (string) $user->id;
            $notification->data = $notificationData;
            $notification->created_at = now();
            $notification->save();

            $this->logger->info('Donor notification sent', [
                'user_id' => $command->userId,
                'donation_id' => $command->donationId,
                'status' => $command->status,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send donor notification', [
                'user_id' => $command->userId,
                'donation_id' => $command->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDonorNotificationData(SendDonationNotificationCommand $command): array
    {
        $baseData = [
            'donation_id' => $command->donationId,
            'amount' => $command->amount,
            'currency' => $command->currency,
            'campaign_title' => $command->campaignTitle,
            'status' => $command->status,
        ];

        return match ($command->status) {
            SendDonationNotificationCommand::STATUS_PROCESSING => array_merge($baseData, [
                'title' => 'Donation Processing',
                'message' => sprintf(
                    'Your donation of %s %.2f to "%s" is being processed.',
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle
                ),
                'type' => 'info',
                'action_url' => "/donations/{$command->donationId}",
            ]),
            SendDonationNotificationCommand::STATUS_COMPLETED => array_merge($baseData, [
                'title' => 'Donation Successfully Processed',
                'message' => sprintf(
                    'Your donation of %s %.2f to "%s" has been successfully processed. Thank you for your contribution!',
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle
                ),
                'type' => 'success',
                'action_url' => "/donations/{$command->donationId}",
            ]),
            SendDonationNotificationCommand::STATUS_FAILED => array_merge($baseData, [
                'title' => 'Donation Failed',
                'message' => sprintf(
                    'Your donation of %s %.2f to "%s" could not be processed. %s',
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle,
                    $command->failureReason ?? 'Please try again or contact support.'
                ),
                'type' => 'error',
                'action_url' => "/campaigns/{$command->campaignId}",
                'failure_reason' => $command->failureReason,
            ]),
            SendDonationNotificationCommand::STATUS_REFUNDED => array_merge($baseData, [
                'title' => 'Donation Refunded',
                'message' => sprintf(
                    'Your donation of %s %.2f to "%s" has been refunded. %s',
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle,
                    $command->refundReason ?? ''
                ),
                'type' => 'warning',
                'action_url' => "/donations/{$command->donationId}",
                'refund_reason' => $command->refundReason,
            ]),
            SendDonationNotificationCommand::STATUS_CANCELLED => array_merge($baseData, [
                'title' => 'Donation Cancelled',
                'message' => sprintf(
                    'Your donation of %s %.2f to "%s" has been cancelled.',
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle
                ),
                'type' => 'warning',
                'action_url' => "/campaigns/{$command->campaignId}",
            ]),
            default => array_merge($baseData, [
                'title' => 'Donation Update',
                'message' => sprintf('Donation status: %s', $command->status),
                'type' => 'info',
                'action_url' => "/donations/{$command->donationId}",
            ]),
        };
    }

    private function sendCampaignCreatorNotification(SendDonationNotificationCommand $command): void
    {
        try {
            $campaign = $this->campaignRepository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                $this->logger->warning('Campaign not found for donation notification', [
                    'campaign_id' => $command->campaignId,
                    'donation_id' => $command->donationId,
                ]);

                return;
            }

            $creator = User::find($campaign->user_id);

            if (! $creator) {
                $this->logger->warning('Campaign creator not found for donation notification', [
                    'campaign_id' => $command->campaignId,
                    'user_id' => $campaign->user_id,
                ]);

                return;
            }

            $donorDisplay = $command->donorName ?? 'Anonymous';

            $notification = new DatabaseNotification;
            $notification->id = Str::uuid()->toString();
            $notification->type = 'App\Notifications\DonationReceived';
            $notification->notifiable_type = $creator::class;
            $notification->notifiable_id = (string) $creator->id;
            $notification->data = [
                'title' => 'New Donation Received!',
                'message' => sprintf(
                    '%s has donated %s %.2f to your campaign "%s"',
                    $donorDisplay,
                    $command->currency,
                    $command->amount,
                    $command->campaignTitle
                ),
                'type' => 'info',
                'action_url' => "/campaigns/{$command->campaignId}/donations",
                'donation_id' => $command->donationId,
                'amount' => $command->amount,
                'currency' => $command->currency,
                'campaign_id' => $command->campaignId,
                'campaign_title' => $command->campaignTitle,
                'donor_name' => $donorDisplay,
            ];
            $notification->created_at = now();
            $notification->save();

            $this->logger->info('Campaign creator notification sent', [
                'creator_id' => $creator->id,
                'campaign_id' => $command->campaignId,
                'donation_id' => $command->donationId,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to send campaign creator notification', [
                'campaign_id' => $command->campaignId,
                'donation_id' => $command->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

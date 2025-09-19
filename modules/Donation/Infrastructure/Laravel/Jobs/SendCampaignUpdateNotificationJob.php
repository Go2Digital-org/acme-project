<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Domain\Model\Donation;

final class SendCampaignUpdateNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly int $campaignId,
        private readonly string $updateType,
        /** @var array<string, mixed> */
        private readonly array $notificationData = [],
        /** @var array<int, array<string, mixed>> */
        private readonly array $recipients = [], // Optional specific recipients
    ) {
        $this->onQueue('notifications');
    }

    public function handle(CampaignRepositoryInterface $campaignRepository): void
    {
        Log::info('Sending campaign update notification', [
            'campaign_id' => $this->campaignId,
            'update_type' => $this->updateType,
            'job_id' => $this->job?->getJobId(),
        ]);

        $campaign = $campaignRepository->findById($this->campaignId);

        if (! $campaign instanceof Campaign) {
            Log::error('Campaign not found for notification', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        if (! $this->shouldSendNotification($campaign)) {
            Log::info('Campaign notification disabled or not eligible', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
            ]);

            return;
        }

        try {
            $recipients = $this->getNotificationRecipients($campaign);

            if ($recipients === []) {
                Log::info('No recipients found for campaign notification', [
                    'campaign_id' => $this->campaignId,
                    'update_type' => $this->updateType,
                ]);

                return;
            }

            // Use chunked processing for large recipient lists
            if (count($recipients) > 50) {
                Log::info('Using chunked processing for large recipient list', [
                    'campaign_id' => $this->campaignId,
                    'update_type' => $this->updateType,
                    'recipient_count' => count($recipients),
                ]);

                SendChunkedCampaignUpdateNotificationJob::createBatch(
                    $this->campaignId,
                    $this->updateType,
                    $this->notificationData,
                    $recipients,
                    50 // chunk size
                );

                return;
            }

            // Process small lists directly
            $this->sendNotificationsByType($campaign, $recipients);

            Log::info('Campaign update notifications dispatched successfully', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
                'recipient_count' => count($recipients),
                'chunked' => false, // Small list processed directly
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send campaign update notification', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Campaign update notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'update_type' => $this->updateType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    private function shouldSendNotification(Campaign $campaign): bool
    {
        // Don't send notifications for inactive campaigns
        if (! $campaign->isActive() && $this->updateType !== 'campaign_completed') {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNotificationRecipients(Campaign $campaign): array
    {
        // If specific recipients provided, use those
        if ($this->recipients !== []) {
            return $this->recipients;
        }

        $recipients = [];

        // Always include campaign organizer (employee is required per domain model)
        $employee = $campaign->employee;
        if ($employee !== null) {
            $recipients[] = [
                'email' => $employee->getEmail(),
                'name' => $employee->getName(),
                'locale' => $employee->locale ?? 'en',
                'role' => 'organizer',
            ];
        }
        // Add other recipients based on update type
        switch ($this->updateType) {
            case 'new_donation':
                // Notify campaign organizer only
                break;

            case 'status_changed':
                // Notify all previous donors
                $donors = $this->getDonorContacts($campaign);
                $recipients = array_merge($recipients, $donors);
                break;

            case 'campaign_updated':
                // Notify followers and major donors
                $followers = $this->getFollowerContacts();
                $majorDonors = $this->getMajorDonorContacts($campaign);
                $recipients = array_merge($recipients, $followers, $majorDonors);
                break;

            case 'campaign_completed':
                // Notify all stakeholders
                $allDonors = $this->getDonorContacts($campaign);
                $recipients = array_merge($recipients, $allDonors);
                break;
        }

        // Remove duplicates based on email
        $uniqueRecipients = [];

        foreach ($recipients as $recipient) {
            $uniqueRecipients[$recipient['email']] = $recipient;
        }

        return array_values($uniqueRecipients);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDonorContacts(Campaign $campaign): array
    {
        // Get all donors for this campaign (excluding anonymous)
        $donors = $campaign->donations()
            ->where('status', 'completed')
            ->where('anonymous', false)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->map(fn ($user): array => [
                'email' => $user->email,
                'name' => $user->name,
                'locale' => $user->locale ?? 'en',
                'role' => 'donor',
            ])
            ->toArray();

        return $donors;
    }

    /**
     * @return array<string, mixed>
     */
    private function getFollowerContacts(): array
    {
        // TODO: Implement follower system
        // For now, return empty array
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMajorDonorContacts(Campaign $campaign): array
    {
        $majorDonationThreshold = config('donation.notifications.major_donor_threshold', 100);

        return $campaign->donations()
            ->where('status', 'completed')
            ->where('amount', '>=', $majorDonationThreshold)
            ->where('anonymous', false)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->map(fn ($user): array => [
                'email' => $user->email,
                'name' => $user->name,
                'locale' => $user->locale ?? 'en',
                'role' => 'major_donor',
            ])
            ->toArray();
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendNotificationsByType(Campaign $campaign, array $recipients): void
    {
        match ($this->updateType) {
            'new_donation' => $this->sendNewDonationNotifications($campaign, $recipients),
            'status_changed' => $this->sendStatusChangeNotifications($campaign, $recipients),
            'campaign_updated' => $this->sendCampaignUpdateNotifications($campaign, $recipients),
            'campaign_completed' => $this->sendCampaignCompletedNotifications($campaign, $recipients),
            default => Log::warning('Unknown campaign notification type', [
                'campaign_id' => $this->campaignId,
                'update_type' => $this->updateType,
            ]),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendNewDonationNotifications(Campaign $campaign, array $recipients): void
    {
        $donation = $this->notificationData['donation'] ?? null;

        if (! $donation instanceof Donation) {
            Log::warning('No donation data provided for new donation notification');

            return;
        }

        foreach ($recipients as $recipient) {
            if ($recipient['role'] !== 'organizer') {
                continue; // Only notify organizer for new donations
            }

            try {
                // TODO: Implement actual email sending
                // Mail::to($recipient['email'])
                //     ->locale($recipient['locale'])
                //     ->send(new NewDonationNotificationMail($campaign, $donation, $recipient));

                Log::info('New donation notification sent', [
                    'campaign_id' => $campaign->id,
                    'donation_id' => $donation->id,
                    'recipient_email' => $recipient['email'],
                    'locale' => $recipient['locale'],
                ]);
            } catch (Exception $exception) {
                Log::error('Failed to send new donation notification', [
                    'campaign_id' => $campaign->id,
                    'recipient_email' => $recipient['email'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendStatusChangeNotifications(Campaign $campaign, array $recipients): void
    {
        $oldStatus = $this->notificationData['old_status'] ?? null;
        $newStatus = $this->notificationData['new_status'] ?? $campaign->status->value;

        foreach ($recipients as $recipient) {
            try {
                // TODO: Implement actual email sending
                // Mail::to($recipient['email'])
                //     ->locale($recipient['locale'])
                //     ->send(new CampaignStatusChangeNotificationMail($campaign, $oldStatus, $newStatus, $recipient));

                Log::info('Status change notification sent', [
                    'campaign_id' => $campaign->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'recipient_email' => $recipient['email'],
                    'locale' => $recipient['locale'],
                ]);
            } catch (Exception $exception) {
                Log::error('Failed to send status change notification', [
                    'campaign_id' => $campaign->id,
                    'recipient_email' => $recipient['email'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendCampaignUpdateNotifications(Campaign $campaign, array $recipients): void
    {
        $updateDetails = $this->notificationData['update_details'] ?? '';

        foreach ($recipients as $recipient) {
            try {
                // TODO: Implement actual email sending
                // Mail::to($recipient['email'])
                //     ->locale($recipient['locale'])
                //     ->send(new CampaignUpdateNotificationMail($campaign, $updateDetails, $recipient));

                Log::info('Campaign update notification sent', [
                    'campaign_id' => $campaign->id,
                    'recipient_email' => $recipient['email'],
                    'locale' => $recipient['locale'],
                    'update_details' => $updateDetails,
                ]);
            } catch (Exception $exception) {
                Log::error('Failed to send campaign update notification', [
                    'campaign_id' => $campaign->id,
                    'recipient_email' => $recipient['email'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendCampaignCompletedNotifications(Campaign $campaign, array $recipients): void
    {
        $finalAmount = $campaign->current_amount;
        $goalReached = $campaign->hasReachedGoal();

        foreach ($recipients as $recipient) {
            try {
                // TODO: Implement actual email sending
                // Mail::to($recipient['email'])
                //     ->locale($recipient['locale'])
                //     ->send(new CampaignCompletedNotificationMail($campaign, $finalAmount, $goalReached, $recipient));

                Log::info('Campaign completion notification sent', [
                    'campaign_id' => $campaign->id,
                    'final_amount' => $finalAmount,
                    'goal_reached' => $goalReached,
                    'recipient_email' => $recipient['email'],
                    'locale' => $recipient['locale'],
                ]);
            } catch (Exception $exception) {
                Log::error('Failed to send campaign completion notification', [
                    'campaign_id' => $campaign->id,
                    'recipient_email' => $recipient['email'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}

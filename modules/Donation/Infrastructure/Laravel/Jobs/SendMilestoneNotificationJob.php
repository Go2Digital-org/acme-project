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

final class SendMilestoneNotificationJob implements ShouldQueue
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
        private readonly int $milestonePercentage,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(CampaignRepositoryInterface $campaignRepository): void
    {
        Log::info('Sending milestone notification', [
            'campaign_id' => $this->campaignId,
            'milestone_percentage' => $this->milestonePercentage,
            'job_id' => $this->job?->getJobId(),
        ]);

        $campaign = $campaignRepository->findById($this->campaignId);

        if (! $campaign instanceof Campaign) {
            Log::error('Campaign not found for milestone notification', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        if (! $this->isValidMilestone()) {
            Log::warning('Invalid milestone percentage', [
                'campaign_id' => $this->campaignId,
                'milestone_percentage' => $this->milestonePercentage,
            ]);

            return;
        }

        if (! $this->hasCampaignReachedMilestone($campaign)) {
            Log::info('Campaign has not actually reached milestone', [
                'campaign_id' => $this->campaignId,
                'milestone_percentage' => $this->milestonePercentage,
                'current_percentage' => $campaign->getProgressPercentage(),
            ]);

            return;
        }

        if ($this->hasNotificationAlreadyBeenSent($campaign)) {
            Log::info('Milestone notification already sent', [
                'campaign_id' => $this->campaignId,
                'milestone_percentage' => $this->milestonePercentage,
            ]);

            return;
        }

        if (! $this->areMilestoneNotificationsEnabled($campaign)) {
            Log::info('Milestone notifications disabled for campaign', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        try {
            $recipients = $this->getMilestoneNotificationRecipients($campaign);

            if ($recipients === []) {
                Log::info('No recipients found for milestone notification', [
                    'campaign_id' => $this->campaignId,
                    'milestone_percentage' => $this->milestonePercentage,
                ]);

                return;
            }

            $this->sendMilestoneNotifications($campaign, $recipients);
            $this->markMilestoneNotificationAsSent($campaign, $campaignRepository);

            Log::info('Milestone notifications sent successfully', [
                'campaign_id' => $this->campaignId,
                'milestone_percentage' => $this->milestonePercentage,
                'recipient_count' => count($recipients),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send milestone notification', [
                'campaign_id' => $this->campaignId,
                'milestone_percentage' => $this->milestonePercentage,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Milestone notification job failed permanently', [
            'campaign_id' => $this->campaignId,
            'milestone_percentage' => $this->milestonePercentage,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Don't mark as sent if job failed
        // This allows retry in future if conditions are met again
    }

    private function isValidMilestone(): bool
    {
        $validMilestones = [25, 50, 75, 100];

        return in_array($this->milestonePercentage, $validMilestones, true);
    }

    private function hasCampaignReachedMilestone(Campaign $campaign): bool
    {
        $currentPercentage = $campaign->getProgressPercentage();

        return $currentPercentage >= $this->milestonePercentage;
    }

    private function hasNotificationAlreadyBeenSent(Campaign $campaign): bool
    {
        $milestoneKey = $this->getMilestoneKey();
        $sentNotifications = $campaign->metadata['milestone_notifications_sent'] ?? [];

        return ! empty($sentNotifications[$milestoneKey]);
    }

    private function areMilestoneNotificationsEnabled(Campaign $campaign): bool
    {
        return $campaign->metadata['milestone_notifications_enabled'] ?? true;
    }

    private function getMilestoneKey(): string
    {
        return $this->milestonePercentage . '_percent';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMilestoneNotificationRecipients(Campaign $campaign): array
    {
        $recipients = [];

        // Always include campaign organizer
        if ($campaign->employee !== null) {
            $recipients[] = [
                'email' => $campaign->employee->getEmail(),
                'name' => $campaign->employee->getName(),
                'locale' => $campaign->employee->locale ?? 'en',
                'role' => 'organizer',
            ];
        }

        // Include major donors for significant milestones
        if (in_array($this->milestonePercentage, [50, 75, 100], true)) {
            $majorDonors = $this->getMajorDonors($campaign);
            $recipients = array_merge($recipients, $majorDonors);
        }

        // Include all donors for completion milestone
        if ($this->milestonePercentage === 100) {
            $allDonors = $this->getAllDonors($campaign);
            $recipients = array_merge($recipients, $allDonors);
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
    private function getMajorDonors(Campaign $campaign): array
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
     * @return array<int, array<string, string>>
     */
    private function getAllDonors(Campaign $campaign): array
    {
        // Use chunked query processing for memory efficiency with large donor lists
        $donors = [];

        $campaign->donations()
            ->where('status', 'completed')
            ->where('anonymous', false)
            ->with('user')
            ->chunkById(500, function ($donations) use (&$donors): void {
                foreach ($donations as $donation) {
                    if ($donation->user) {
                        $userId = $donation->user->id;

                        // Avoid duplicates
                        if (! isset($donors[$userId])) {
                            $donors[$userId] = [
                                'email' => $donation->user->email,
                                'name' => $donation->user->name,
                                'locale' => $donation->user->locale ?? 'en',
                                'role' => 'donor',
                            ];
                        }
                    }
                }
            });

        return array_values($donors);
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function sendMilestoneNotifications(Campaign $campaign, array $recipients): void
    {
        $this->prepareMilestoneData($campaign);

        foreach ($recipients as $recipient) {
            try {
                // TODO: Implement actual email sending
                // Mail::to($recipient['email'])
                //     ->locale($recipient['locale'])
                //     ->send(new MilestoneAchievedNotificationMail($campaign, $this->milestonePercentage, $milestoneData, $recipient));

                Log::info('Milestone notification sent to recipient', [
                    'campaign_id' => $campaign->id,
                    'milestone_percentage' => $this->milestonePercentage,
                    'recipient_email' => $recipient['email'],
                    'recipient_role' => $recipient['role'],
                    'locale' => $recipient['locale'],
                ]);
            } catch (Exception $exception) {
                Log::error('Failed to send milestone notification to recipient', [
                    'campaign_id' => $campaign->id,
                    'milestone_percentage' => $this->milestonePercentage,
                    'recipient_email' => $recipient['email'],
                    'error' => $exception->getMessage(),
                ]);
                // Continue sending to other recipients
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareMilestoneData(Campaign $campaign): array
    {
        $progressPercentage = $campaign->getProgressPercentage();
        $targetAmount = $campaign->goal_amount * ($this->milestonePercentage / 100);

        return [
            'milestone_percentage' => $this->milestonePercentage,
            'target_amount' => $targetAmount,
            'current_amount' => $campaign->current_amount,
            'goal_amount' => $campaign->goal_amount,
            'progress_percentage' => $progressPercentage,
            'remaining_amount' => $campaign->getRemainingAmount(),
            'days_remaining' => $campaign->getDaysRemaining(),
            'total_donors' => $this->getTotalDonorCount($campaign),
            'is_completion' => $this->milestonePercentage === 100,
            'achievement_graphics' => $this->getAchievementGraphics(),
        ];
    }

    private function getTotalDonorCount(Campaign $campaign): int
    {
        return $campaign->donations()
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function getAchievementGraphics(): array
    {
        $graphics = [
            25 => [
                'badge' => 'quarter-milestone.svg',
                'emoji' => '',
                'color' => '#FFA500',
            ],
            50 => [
                'badge' => 'halfway-milestone.svg',
                'emoji' => '🏃‍♂️',
                'color' => '#FF6B35',
            ],
            75 => [
                'badge' => 'three-quarters-milestone.svg',
                'emoji' => '',
                'color' => '#FF4136',
            ],
            100 => [
                'badge' => 'completed-campaign.svg',
                'emoji' => '',
                'color' => '#2ECC40',
            ],
        ];

        return $graphics[$this->milestonePercentage] ?? [
            'badge' => 'default-milestone.svg',
            'emoji' => '⭐',
            'color' => '#0074D9',
        ];
    }

    private function markMilestoneNotificationAsSent(
        Campaign $campaign,
        CampaignRepositoryInterface $campaignRepository,
    ): void {
        $metadata = $campaign->metadata ?? [];
        $sentNotifications = $metadata['milestone_notifications_sent'] ?? [];
        $milestoneKey = $this->getMilestoneKey();

        $sentNotifications[$milestoneKey] = true;
        $sentNotifications[$milestoneKey . '_sent_at'] = now()->toISOString();

        $metadata['milestone_notifications_sent'] = $sentNotifications;

        $campaignRepository->updateById($campaign->id, ['metadata' => $metadata]);

        Log::info('Milestone notification marked as sent', [
            'campaign_id' => $campaign->id,
            'milestone_key' => $milestoneKey,
        ]);
    }
}

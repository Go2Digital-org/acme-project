<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Service\CampaignProgressCalculator;
use Modules\Campaign\Domain\ValueObject\CampaignProgress;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

final readonly class CampaignProgressService
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private CampaignProgressCalculator $progressCalculator,
    ) {}

    /**
     * Calculate detailed progress for a campaign.
     */
    public function calculateCampaignProgress(int $campaignId): CampaignProgress
    {
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            throw new InvalidArgumentException("Campaign with ID {$campaignId} not found");
        }

        return $this->progressCalculator->calculate($campaign);
    }

    /**
     * Get progress data for multiple campaigns.
     *
     * @param  array<int>  $campaignIds
     * @return array<int, CampaignProgress>
     */
    public function calculateMultipleCampaignProgress(array $campaignIds): array
    {
        $campaigns = $this->campaignRepository->findByIds($campaignIds);
        $progressData = [];

        foreach ($campaigns as $campaign) {
            $progressData[$campaign->id] = $this->progressCalculator->calculate($campaign);
        }

        return $progressData;
    }

    /**
     * Get progress summary for an organization.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationProgressSummary(int $organizationId): array
    {
        $campaigns = $this->campaignRepository->findByOrganizationId($organizationId);

        $totalCampaigns = count($campaigns);
        $activeCampaigns = 0;
        $completedCampaigns = 0;
        $totalGoalAmount = 0.0;
        $totalRaisedAmount = 0.0;

        $progressData = [];

        foreach ($campaigns as $campaign) {
            $progress = $this->progressCalculator->calculate($campaign);
            $progressData[] = $progress;

            if ($campaign->status === CampaignStatus::ACTIVE) {
                $activeCampaigns++;
            }

            if ($campaign->status === CampaignStatus::COMPLETED) {
                $completedCampaigns++;
            }

            $totalGoalAmount += (float) $campaign->goal_amount;
            $totalRaisedAmount += (float) $campaign->current_amount;
        }

        return [
            'organization_id' => $organizationId,
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'total_goal_amount' => $totalGoalAmount,
            'total_raised_amount' => $totalRaisedAmount,
            'overall_progress_percentage' => $totalGoalAmount > 0 ? ($totalRaisedAmount / $totalGoalAmount) * 100 : 0.0,
            'campaigns_progress' => $progressData,
        ];
    }

    /**
     * Get campaigns that are behind schedule.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsBehindSchedule(): array
    {
        $activeCampaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE);
        $behindSchedule = [];

        foreach ($activeCampaigns as $campaign) {
            $progress = $this->progressCalculator->calculate($campaign);

            if ($progress->isBehindSchedule()) {
                $behindSchedule[] = [
                    'campaign_id' => $campaign->id,
                    'title' => $campaign->title,
                    'current_progress' => $progress->getPercentage(),
                    'expected_progress' => $progress->getExpectedProgress(),
                    'progress_deficit' => $progress->getExpectedProgress() - $progress->getPercentage(),
                    'days_remaining' => $campaign->getDaysRemaining(),
                    'status' => $campaign->status->value,
                ];
            }
        }

        return $behindSchedule;
    }

    /**
     * Get campaigns approaching their end date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsApproachingDeadline(int $daysThreshold = 7): array
    {
        $activeCampaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE);
        $approachingDeadline = [];

        foreach ($activeCampaigns as $campaign) {
            $daysRemaining = $campaign->getDaysRemaining();

            if ($daysRemaining <= $daysThreshold && $daysRemaining > 0) {
                $progress = $this->progressCalculator->calculate($campaign);

                $approachingDeadline[] = [
                    'campaign_id' => $campaign->id,
                    'title' => $campaign->title,
                    'days_remaining' => $daysRemaining,
                    'current_progress' => $progress->getPercentage(),
                    'goal_amount' => (float) $campaign->goal_amount,
                    'current_amount' => (float) $campaign->current_amount,
                    'remaining_amount' => $progress->getRemainingAmount(),
                    'is_likely_to_succeed' => $progress->isLikelyToSucceed(),
                ];
            }
        }

        // Sort by days remaining (ascending)
        usort($approachingDeadline, fn (array $a, array $b): int => $a['days_remaining'] <=> $b['days_remaining']);

        return $approachingDeadline;
    }

    /**
     * Get top performing campaigns by progress percentage.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopPerformingCampaigns(int $limit = 10): array
    {
        $activeCampaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE);
        $performanceData = [];

        foreach ($activeCampaigns as $campaign) {
            $progress = $this->progressCalculator->calculate($campaign);

            $performanceData[] = [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'progress_percentage' => $progress->getPercentage(),
                'goal_amount' => (float) $campaign->goal_amount,
                'current_amount' => (float) $campaign->current_amount,
                'donations_count' => $campaign->donations_count ?? 0,
                'days_remaining' => $campaign->getDaysRemaining(),
                'velocity' => $progress->getVelocity(),
                'projected_final_amount' => $progress->getProjectedFinalAmount(),
            ];
        }

        // Sort by progress percentage (descending)
        usort($performanceData, fn (array $a, array $b): int => $b['progress_percentage'] <=> $a['progress_percentage']);

        return array_slice($performanceData, 0, $limit);
    }

    /**
     * Update campaign progress and check for milestones.
     */
    public function updateCampaignProgress(int $campaignId): CampaignProgress
    {
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            throw new InvalidArgumentException("Campaign with ID {$campaignId} not found");
        }

        $progress = $this->progressCalculator->calculate($campaign);

        // Check if campaign should be automatically completed
        if ($progress->hasReachedGoal() && $campaign->status === CampaignStatus::ACTIVE) {
            $this->campaignRepository->updateStatus($campaignId, CampaignStatus::COMPLETED);
        }

        // Check if campaign should be marked as expired
        if ($campaign->getDaysRemaining() < 0 && $campaign->status === CampaignStatus::ACTIVE) {
            $this->campaignRepository->updateStatus($campaignId, CampaignStatus::EXPIRED);
        }

        return $progress;
    }

    /**
     * Get progress trend data for a campaign.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignProgressTrend(int $campaignId): array
    {
        // This would typically query donation history to show progress over time
        // For now, we'll return the current progress as a single data point
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            throw new InvalidArgumentException("Campaign with ID {$campaignId} not found");
        }

        $progress = $this->progressCalculator->calculate($campaign);

        return [
            [
                'date' => now()->toDateString(),
                'amount' => (float) $campaign->current_amount,
                'percentage' => $progress->getPercentage(),
                'donations_count' => $campaign->donations_count ?? 0,
            ],
        ];
    }

    /**
     * Get campaigns that need attention based on various criteria.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getCampaignsNeedingAttention(): array
    {
        return [
            'behind_schedule' => $this->getCampaignsBehindSchedule(),
            'approaching_deadline' => $this->getCampaignsApproachingDeadline(),
            'low_engagement' => $this->getLowEngagementCampaigns(),
            'stalled_campaigns' => $this->getStalledCampaigns(),
        ];
    }

    /**
     * Get campaigns with low engagement (few donations relative to time active).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getLowEngagementCampaigns(): array
    {
        $activeCampaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE);
        $lowEngagement = [];

        foreach ($activeCampaigns as $campaign) {
            $daysActive = $campaign->getDaysActive();
            $donationsCount = $campaign->donations_count ?? 0;

            // Consider low engagement if less than 1 donation per 3 days active
            $expectedDonations = max(1, $daysActive / 3);

            if ($daysActive > 3 && $donationsCount < $expectedDonations) {
                $progress = $this->progressCalculator->calculate($campaign);

                $lowEngagement[] = [
                    'campaign_id' => $campaign->id,
                    'title' => $campaign->title,
                    'days_active' => $daysActive,
                    'donations_count' => $donationsCount,
                    'expected_donations' => (int) round($expectedDonations),
                    'engagement_ratio' => $expectedDonations > 0 ? $donationsCount / $expectedDonations : 0,
                    'current_progress' => $progress->getPercentage(),
                ];
            }
        }

        return $lowEngagement;
    }

    /**
     * Get campaigns that have stalled (no recent donations).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getStalledCampaigns(): array
    {
        // This would typically check for campaigns with no donations in the last X days
        // For now, we'll return campaigns with very low progress relative to time elapsed
        $activeCampaigns = $this->campaignRepository->findByStatus(CampaignStatus::ACTIVE);
        $stalledCampaigns = [];

        foreach ($activeCampaigns as $campaign) {
            $progress = $this->progressCalculator->calculate($campaign);
            $daysActive = $campaign->getDaysActive();

            // Consider stalled if active for more than 7 days but less than 10% progress
            if ($daysActive > 7 && $progress->getPercentage() < 10) {
                $stalledCampaigns[] = [
                    'campaign_id' => $campaign->id,
                    'title' => $campaign->title,
                    'days_active' => $daysActive,
                    'current_progress' => $progress->getPercentage(),
                    'current_amount' => (float) $campaign->current_amount,
                    'days_remaining' => $campaign->getDaysRemaining(),
                ];
            }
        }

        return $stalledCampaigns;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Domain\ValueObject\UserCampaignStats;
use Modules\User\Infrastructure\Laravel\Models\User;

class UserCampaignManagementService
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Check if a user can manage a specific campaign.
     */
    public function canManageCampaign(int $userId, Campaign $campaign): bool
    {
        $user = User::find($userId);

        if (! $user) {
            return false;
        }
        // Allow if user is super admin OR owns the campaign
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $campaign->user_id === $userId;
    }

    /**
     * Get comprehensive statistics for a user's campaigns.
     */
    public function getUserCampaignStats(int $userId): UserCampaignStats
    {
        $campaigns = $this->campaignRepository->findByUserId($userId);

        $totalCampaigns = count($campaigns);
        $activeCampaigns = count(array_filter($campaigns, fn (Campaign $c): bool => $c->status === CampaignStatus::ACTIVE));
        $completedCampaigns = count(array_filter($campaigns, fn (Campaign $c): bool => $c->status === CampaignStatus::COMPLETED));
        $draftCampaigns = count(array_filter($campaigns, fn (Campaign $c): bool => $c->status === CampaignStatus::DRAFT));

        $totalRaised = array_reduce($campaigns, fn ($sum, $c): float|int => $sum + $c->current_amount, 0);
        $totalGoal = array_reduce($campaigns, fn ($sum, $c): float|int => $sum + $c->goal_amount, 0);

        $totalDonations = array_reduce($campaigns, fn ($sum, $c): int => $sum + $c->donations_count, 0);

        // Calculate average success rate
        $completedWithGoal = array_filter($campaigns, fn (Campaign $c): bool => $c->status === CampaignStatus::COMPLETED && $c->current_amount >= $c->goal_amount);
        $successRate = $completedCampaigns > 0 ? (count($completedWithGoal) / $completedCampaigns) * 100 : 0;

        return new UserCampaignStats(
            totalCampaigns: $totalCampaigns,
            activeCampaigns: $activeCampaigns,
            completedCampaigns: $completedCampaigns,
            draftCampaigns: $draftCampaigns,
            totalAmountRaised: $totalRaised,
            totalGoalAmount: $totalGoal,
            totalDonations: $totalDonations,
            averageSuccessRate: $successRate,
        );
    }

    /**
     * Validate if a campaign status change is allowed.
     * Uses the CampaignStatus enum's built-in transition rules.
     */
    public function canChangeStatus(Campaign $campaign, CampaignStatus $newStatus): bool
    {
        // Use the transition rules defined in the CampaignStatus enum
        return $campaign->status->canTransitionTo($newStatus);
    }

    /**
     * Get campaigns that need attention from the user.
     *
     * @return array<array{campaign: Campaign, reasons: array<string>}>
     */
    public function getCampaignsNeedingAttention(int $userId): array
    {
        $campaigns = $this->campaignRepository->findByUserId($userId);
        $needingAttention = [];

        foreach ($campaigns as $campaign) {
            $reasons = [];

            // Check if campaign is ending soon (7 days)
            if ($campaign->status === CampaignStatus::ACTIVE && $campaign->getDaysRemaining() <= 7 && $campaign->getDaysRemaining() > 0) {
                $reasons[] = 'Ending soon';
            }

            // Check if campaign has been active for a while with no donations
            if ($campaign->status === CampaignStatus::ACTIVE && $campaign->donations_count === 0 && $campaign->created_at?->diffInDays(now()) > 7) {
                $reasons[] = 'No donations yet';
            }

            // Check if campaign goal is almost reached
            if ($campaign->status === CampaignStatus::ACTIVE && $campaign->getProgressPercentage() >= 90) {
                $reasons[] = 'Goal almost reached';
            }

            // Check if campaign has expired but not marked as completed
            if ($campaign->status === CampaignStatus::ACTIVE && $campaign->getDaysRemaining() < 0) {
                $reasons[] = 'Expired - needs review';
            }

            if ($reasons !== []) {
                $needingAttention[] = [
                    'campaign' => $campaign,
                    'reasons' => $reasons,
                ];
            }
        }

        return $needingAttention;
    }
}

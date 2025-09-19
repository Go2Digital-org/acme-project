<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Campaign analytics read model optimized for reporting and dashboard queries.
 */
class CampaignAnalyticsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $campaignId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($campaignId, $data, $version);
        $this->setCacheTtl(1800); // 30 minutes for analytics data
    }

    /**
     * @return array<string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign_analytics',
            'campaign:' . $this->id,
            'organization:' . $this->getOrganizationId(),
        ]);
    }

    // Campaign Basic Info
    public function getCampaignId(): int
    {
        return (int) $this->id;
    }

    public function getTitle(): string
    {
        return $this->get('title', 'Untitled Campaign');
    }

    public function getStatus(): string
    {
        return $this->get('status', 'draft');
    }

    public function getVisibility(): string
    {
        return $this->get('visibility', 'public');
    }

    public function getOrganizationId(): int
    {
        return (int) $this->get('organization_id', 0);
    }

    public function getOrganizationName(): string
    {
        return $this->get('organization_name', '');
    }

    public function getUserId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function getCreatorName(): string
    {
        return $this->get('creator_name', '');
    }

    public function getCategoryId(): ?int
    {
        $categoryId = $this->get('category_id');

        return $categoryId ? (int) $categoryId : null;
    }

    public function getCategoryName(): ?string
    {
        return $this->get('category_name');
    }

    // Financial Metrics
    public function getGoalAmount(): float
    {
        return (float) $this->get('goal_amount', 0);
    }

    public function getCurrentAmount(): float
    {
        return (float) $this->get('current_amount', 0);
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->getGoalAmount() - $this->getCurrentAmount());
    }

    public function getProgressPercentage(): float
    {
        $goal = $this->getGoalAmount();
        if ($goal <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->getCurrentAmount() / $goal) * 100);
    }

    public function hasReachedGoal(): bool
    {
        return $this->getCurrentAmount() >= $this->getGoalAmount();
    }

    public function getCorporateMatchAmount(): float
    {
        return (float) $this->get('corporate_match_amount', 0);
    }

    public function getTotalRaised(): float
    {
        return $this->getCurrentAmount() + $this->getCorporateMatchAmount();
    }

    // Time Metrics
    public function getStartDate(): ?string
    {
        return $this->get('start_date');
    }

    public function getEndDate(): ?string
    {
        return $this->get('end_date');
    }

    public function getDaysRemaining(): int
    {
        return (int) $this->get('days_remaining', 0);
    }

    public function getDaysActive(): int
    {
        return (int) $this->get('days_active', 0);
    }

    public function getCampaignDuration(): int
    {
        return (int) $this->get('campaign_duration', 0);
    }

    public function isActive(): bool
    {
        return $this->get('is_active', false);
    }

    // Donation Metrics
    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getUniqueDonors(): int
    {
        return (int) $this->get('unique_donors', 0);
    }

    public function getAverageDonationAmount(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return $this->getCurrentAmount() / $total;
    }

    public function getAnonymousDonations(): int
    {
        return (int) $this->get('anonymous_donations', 0);
    }

    public function getRecurringDonations(): int
    {
        return (int) $this->get('recurring_donations', 0);
    }

    public function getCompletedDonations(): int
    {
        return (int) $this->get('completed_donations', 0);
    }

    public function getPendingDonations(): int
    {
        return (int) $this->get('pending_donations', 0);
    }

    public function getFailedDonations(): int
    {
        return (int) $this->get('failed_donations', 0);
    }

    public function getRefundedDonations(): int
    {
        return (int) $this->get('refunded_donations', 0);
    }

    public function getRefundedAmount(): float
    {
        return (float) $this->get('refunded_amount', 0);
    }

    // Payment Gateway Statistics
    /**
     * @return array<string, mixed>
     */
    public function getPaymentGatewayStats(): array
    {
        return $this->get('payment_gateway_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodStats(): array
    {
        return $this->get('payment_method_stats', []);
    }

    // Time-based Analytics
    /**
     * @return array<string, mixed>
     */
    public function getDonationsByDay(): array
    {
        return $this->get('donations_by_day', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsByWeek(): array
    {
        return $this->get('donations_by_week', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsByMonth(): array
    {
        return $this->get('donations_by_month', []);
    }

    // Performance Metrics
    public function getDonationVelocity(): float
    {
        $daysActive = $this->getDaysActive();
        if ($daysActive <= 0) {
            return 0.0;
        }

        return $this->getCurrentAmount() / $daysActive;
    }

    public function getAverageDonationsPerDay(): float
    {
        $daysActive = $this->getDaysActive();
        if ($daysActive <= 0) {
            return 0.0;
        }

        return (float) $this->getTotalDonations() / $daysActive;
    }

    public function getFundraisingEfficiency(): float
    {
        $goal = $this->getGoalAmount();
        $duration = $this->getCampaignDuration();

        if ($goal <= 0 || $duration <= 0) {
            return 0.0;
        }

        return ($this->getCurrentAmount() / $goal) / $duration * 100;
    }

    // Engagement Metrics
    public function getBookmarksCount(): int
    {
        return (int) $this->get('bookmarks_count', 0);
    }

    public function getSharesCount(): int
    {
        return (int) $this->get('shares_count', 0);
    }

    public function getViewsCount(): int
    {
        return (int) $this->get('views_count', 0);
    }

    // Timestamps
    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    public function getCompletedAt(): ?string
    {
        return $this->get('completed_at');
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsArray(): array
    {
        return [
            'campaign' => [
                'id' => $this->getCampaignId(),
                'title' => $this->getTitle(),
                'status' => $this->getStatus(),
                'visibility' => $this->getVisibility(),
                'is_active' => $this->isActive(),
                'organization_id' => $this->getOrganizationId(),
                'organization_name' => $this->getOrganizationName(),
                'creator_name' => $this->getCreatorName(),
                'category_name' => $this->getCategoryName(),
            ],
            'financial' => [
                'goal_amount' => $this->getGoalAmount(),
                'current_amount' => $this->getCurrentAmount(),
                'remaining_amount' => $this->getRemainingAmount(),
                'progress_percentage' => $this->getProgressPercentage(),
                'corporate_match_amount' => $this->getCorporateMatchAmount(),
                'total_raised' => $this->getTotalRaised(),
                'refunded_amount' => $this->getRefundedAmount(),
                'has_reached_goal' => $this->hasReachedGoal(),
            ],
            'time' => [
                'start_date' => $this->getStartDate(),
                'end_date' => $this->getEndDate(),
                'days_remaining' => $this->getDaysRemaining(),
                'days_active' => $this->getDaysActive(),
                'campaign_duration' => $this->getCampaignDuration(),
            ],
            'donations' => [
                'total_donations' => $this->getTotalDonations(),
                'unique_donors' => $this->getUniqueDonors(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'anonymous_donations' => $this->getAnonymousDonations(),
                'recurring_donations' => $this->getRecurringDonations(),
                'completed_donations' => $this->getCompletedDonations(),
                'pending_donations' => $this->getPendingDonations(),
                'failed_donations' => $this->getFailedDonations(),
                'refunded_donations' => $this->getRefundedDonations(),
            ],
            'performance' => [
                'donation_velocity' => $this->getDonationVelocity(),
                'average_donations_per_day' => $this->getAverageDonationsPerDay(),
                'fundraising_efficiency' => $this->getFundraisingEfficiency(),
            ],
            'engagement' => [
                'bookmarks_count' => $this->getBookmarksCount(),
                'shares_count' => $this->getSharesCount(),
                'views_count' => $this->getViewsCount(),
            ],
            'timestamps' => [
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
                'completed_at' => $this->getCompletedAt(),
            ],
        ];
    }
}

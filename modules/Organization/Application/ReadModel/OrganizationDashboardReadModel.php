<?php

declare(strict_types=1);

namespace Modules\Organization\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Organization dashboard read model for comprehensive organization overview.
 */
class OrganizationDashboardReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $organizationId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($organizationId, $data, $version);
        $this->setCacheTtl(3600); // 1 hour for dashboard data
    }

    /**
     * @return array<string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'organization_dashboard',
            'organization:' . $this->id,
        ]);
    }

    // Organization Basic Info
    public function getOrganizationId(): int
    {
        return (int) $this->id;
    }

    public function getName(): string
    {
        return $this->get('name', '');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getWebsite(): ?string
    {
        return $this->get('website');
    }

    public function getLogoUrl(): ?string
    {
        return $this->get('logo_url');
    }

    public function getStatus(): string
    {
        return $this->get('status', 'active');
    }

    public function getType(): string
    {
        return $this->get('type', 'nonprofit');
    }

    public function getCountry(): ?string
    {
        return $this->get('country');
    }

    public function getCity(): ?string
    {
        return $this->get('city');
    }

    public function isVerified(): bool
    {
        return $this->get('is_verified', false);
    }

    public function getVerifiedAt(): ?string
    {
        return $this->get('verified_at');
    }

    // Employee Statistics
    public function getTotalEmployees(): int
    {
        return (int) $this->get('total_employees', 0);
    }

    public function getActiveEmployees(): int
    {
        return (int) $this->get('active_employees', 0);
    }

    public function getAdminEmployees(): int
    {
        return (int) $this->get('admin_employees', 0);
    }

    public function getNewEmployeesThisMonth(): int
    {
        return (int) $this->get('new_employees_this_month', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEmployeesByDepartment(): array
    {
        return $this->get('employees_by_department', []);
    }

    // Campaign Statistics
    public function getTotalCampaigns(): int
    {
        return (int) $this->get('total_campaigns', 0);
    }

    public function getActiveCampaigns(): int
    {
        return (int) $this->get('active_campaigns', 0);
    }

    public function getCompletedCampaigns(): int
    {
        return (int) $this->get('completed_campaigns', 0);
    }

    public function getDraftCampaigns(): int
    {
        return (int) $this->get('draft_campaigns', 0);
    }

    public function getPendingApprovalCampaigns(): int
    {
        return (int) $this->get('pending_approval_campaigns', 0);
    }

    public function getRejectedCampaigns(): int
    {
        return (int) $this->get('rejected_campaigns', 0);
    }

    public function getCampaignsCreatedThisMonth(): int
    {
        return (int) $this->get('campaigns_created_this_month', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCampaignsByCategory(): array
    {
        return $this->get('campaigns_by_category', []);
    }

    public function getAverageCampaignDuration(): float
    {
        return (float) $this->get('average_campaign_duration', 0);
    }

    // Financial Overview
    public function getTotalFundraisingGoal(): float
    {
        return (float) $this->get('total_fundraising_goal', 0);
    }

    public function getTotalAmountRaised(): float
    {
        return (float) $this->get('total_amount_raised', 0);
    }

    public function getTotalAmountRaisedThisMonth(): float
    {
        return (float) $this->get('total_amount_raised_this_month', 0);
    }

    public function getTotalAmountRaisedThisYear(): float
    {
        return (float) $this->get('total_amount_raised_this_year', 0);
    }

    public function getOverallProgressPercentage(): float
    {
        $goal = $this->getTotalFundraisingGoal();
        if ($goal <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->getTotalAmountRaised() / $goal) * 100);
    }

    public function getTotalCorporateMatchAmount(): float
    {
        return (float) $this->get('total_corporate_match_amount', 0);
    }

    public function getTotalWithMatching(): float
    {
        return $this->getTotalAmountRaised() + $this->getTotalCorporateMatchAmount();
    }

    public function getAverageAmountPerCampaign(): float
    {
        $campaigns = $this->getCompletedCampaigns();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $campaigns;
    }

    // Donation Statistics
    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getTotalUniqueDonors(): int
    {
        return (int) $this->get('total_unique_donors', 0);
    }

    public function getDonationsThisMonth(): int
    {
        return (int) $this->get('donations_this_month', 0);
    }

    public function getNewDonorsThisMonth(): int
    {
        return (int) $this->get('new_donors_this_month', 0);
    }

    public function getAverageDonationAmount(): float
    {
        $donations = $this->getTotalDonations();
        if ($donations <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $donations;
    }

    public function getRecurringDonations(): int
    {
        return (int) $this->get('recurring_donations', 0);
    }

    public function getRecurringDonationAmount(): float
    {
        return (float) $this->get('recurring_donation_amount', 0);
    }

    public function getDonorRetentionRate(): float
    {
        return (float) $this->get('donor_retention_rate', 0);
    }

    // Performance Metrics
    public function getSuccessfulCampaignsRate(): float
    {
        $total = $this->getTotalCampaigns();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getCompletedCampaigns() / $total) * 100;
    }

    public function getAverageCampaignSuccessRate(): float
    {
        return (float) $this->get('average_campaign_success_rate', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopPerformingCampaigns(): array
    {
        return $this->get('top_performing_campaigns', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMostProductiveEmployees(): array
    {
        return $this->get('most_productive_employees', []);
    }

    // Time-based Analytics
    /**
     * @return array<string, mixed>
     */
    public function getMonthlyFundraisingTrend(): array
    {
        return $this->get('monthly_fundraising_trend', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMonthlyCampaignCreationTrend(): array
    {
        return $this->get('monthly_campaign_creation_trend', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMonthlyDonationTrend(): array
    {
        return $this->get('monthly_donation_trend', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuarterlyPerformance(): array
    {
        return $this->get('quarterly_performance', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getYearOverYearGrowth(): array
    {
        return $this->get('year_over_year_growth', []);
    }

    // Engagement Metrics
    public function getTotalCampaignViews(): int
    {
        return (int) $this->get('total_campaign_views', 0);
    }

    public function getTotalCampaignShares(): int
    {
        return (int) $this->get('total_campaign_shares', 0);
    }

    public function getTotalCampaignBookmarks(): int
    {
        return (int) $this->get('total_campaign_bookmarks', 0);
    }

    public function getAverageViewsPerCampaign(): float
    {
        $campaigns = $this->getTotalCampaigns();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return (float) $this->getTotalCampaignViews() / $campaigns;
    }

    // Recent Activity
    /**
     * @return array<string, mixed>
     */
    public function getRecentCampaigns(): array
    {
        return $this->get('recent_campaigns', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecentDonations(): array
    {
        return $this->get('recent_donations', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpcomingDeadlines(): array
    {
        return $this->get('upcoming_deadlines', []);
    }

    // Compliance & Settings
    public function hasActiveSubscription(): bool
    {
        return $this->get('has_active_subscription', false);
    }

    public function getSubscriptionTier(): ?string
    {
        return $this->get('subscription_tier');
    }

    public function getSubscriptionExpiresAt(): ?string
    {
        return $this->get('subscription_expires_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function getApiUsage(): array
    {
        return $this->get('api_usage', []);
    }

    public function getStorageUsed(): float
    {
        return (float) $this->get('storage_used', 0);
    }

    public function getStorageLimit(): float
    {
        return (float) $this->get('storage_limit', 0);
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

    public function getLastLoginAt(): ?string
    {
        return $this->get('last_login_at');
    }

    public function getLastActivityAt(): ?string
    {
        return $this->get('last_activity_at');
    }

    // Calculated Properties
    public function getEmployeeGrowthRate(): float
    {
        $total = $this->getTotalEmployees();
        $newThisMonth = $this->getNewEmployeesThisMonth();

        if ($total <= 0) {
            return 0.0;
        }

        return ($newThisMonth / $total) * 100;
    }

    public function getCampaignCreationVelocity(): float
    {
        return (float) $this->get('campaign_creation_velocity', 0);
    }

    public function getFundraisingVelocity(): float
    {
        return (float) $this->get('fundraising_velocity', 0);
    }

    // Status Checks
    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->getStatus() === 'suspended';
    }

    public function needsAttention(): bool
    {
        if ($this->getPendingApprovalCampaigns() > 0) {
            return true;
        }
        if (! $this->hasActiveSubscription()) {
            return true;
        }

        return $this->getStorageUsed() / $this->getStorageLimit() > 0.9;
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toDashboardArray(): array
    {
        return [
            'organization' => [
                'id' => $this->getOrganizationId(),
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'website' => $this->getWebsite(),
                'logo_url' => $this->getLogoUrl(),
                'status' => $this->getStatus(),
                'type' => $this->getType(),
                'country' => $this->getCountry(),
                'city' => $this->getCity(),
                'is_verified' => $this->isVerified(),
                'verified_at' => $this->getVerifiedAt(),
            ],
            'employees' => [
                'total' => $this->getTotalEmployees(),
                'active' => $this->getActiveEmployees(),
                'admins' => $this->getAdminEmployees(),
                'new_this_month' => $this->getNewEmployeesThisMonth(),
                'by_department' => $this->getEmployeesByDepartment(),
                'growth_rate' => $this->getEmployeeGrowthRate(),
            ],
            'campaigns' => [
                'total' => $this->getTotalCampaigns(),
                'active' => $this->getActiveCampaigns(),
                'completed' => $this->getCompletedCampaigns(),
                'draft' => $this->getDraftCampaigns(),
                'pending_approval' => $this->getPendingApprovalCampaigns(),
                'rejected' => $this->getRejectedCampaigns(),
                'created_this_month' => $this->getCampaignsCreatedThisMonth(),
                'by_category' => $this->getCampaignsByCategory(),
                'average_duration' => $this->getAverageCampaignDuration(),
                'success_rate' => $this->getSuccessfulCampaignsRate(),
                'creation_velocity' => $this->getCampaignCreationVelocity(),
            ],
            'financial' => [
                'total_goal' => $this->getTotalFundraisingGoal(),
                'total_raised' => $this->getTotalAmountRaised(),
                'raised_this_month' => $this->getTotalAmountRaisedThisMonth(),
                'raised_this_year' => $this->getTotalAmountRaisedThisYear(),
                'progress_percentage' => $this->getOverallProgressPercentage(),
                'corporate_match' => $this->getTotalCorporateMatchAmount(),
                'total_with_matching' => $this->getTotalWithMatching(),
                'average_per_campaign' => $this->getAverageAmountPerCampaign(),
                'fundraising_velocity' => $this->getFundraisingVelocity(),
            ],
            'donations' => [
                'total' => $this->getTotalDonations(),
                'unique_donors' => $this->getTotalUniqueDonors(),
                'this_month' => $this->getDonationsThisMonth(),
                'new_donors_this_month' => $this->getNewDonorsThisMonth(),
                'average_amount' => $this->getAverageDonationAmount(),
                'recurring_count' => $this->getRecurringDonations(),
                'recurring_amount' => $this->getRecurringDonationAmount(),
                'retention_rate' => $this->getDonorRetentionRate(),
            ],
            'performance' => [
                'top_campaigns' => $this->getTopPerformingCampaigns(),
                'productive_employees' => $this->getMostProductiveEmployees(),
                'average_campaign_success_rate' => $this->getAverageCampaignSuccessRate(),
            ],
            'engagement' => [
                'total_views' => $this->getTotalCampaignViews(),
                'total_shares' => $this->getTotalCampaignShares(),
                'total_bookmarks' => $this->getTotalCampaignBookmarks(),
                'average_views_per_campaign' => $this->getAverageViewsPerCampaign(),
            ],
            'trends' => [
                'monthly_fundraising' => $this->getMonthlyFundraisingTrend(),
                'monthly_campaigns' => $this->getMonthlyCampaignCreationTrend(),
                'monthly_donations' => $this->getMonthlyDonationTrend(),
                'quarterly_performance' => $this->getQuarterlyPerformance(),
                'year_over_year_growth' => $this->getYearOverYearGrowth(),
            ],
            'recent_activity' => [
                'campaigns' => $this->getRecentCampaigns(),
                'donations' => $this->getRecentDonations(),
                'upcoming_deadlines' => $this->getUpcomingDeadlines(),
            ],
            'subscription' => [
                'has_active' => $this->hasActiveSubscription(),
                'tier' => $this->getSubscriptionTier(),
                'expires_at' => $this->getSubscriptionExpiresAt(),
                'api_usage' => $this->getApiUsage(),
                'storage_used' => $this->getStorageUsed(),
                'storage_limit' => $this->getStorageLimit(),
            ],
            'status' => [
                'is_active' => $this->isActive(),
                'needs_attention' => $this->needsAttention(),
                'last_login' => $this->getLastLoginAt(),
                'last_activity' => $this->getLastActivityAt(),
            ],
        ];
    }
}

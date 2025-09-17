<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Service;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\User\Domain\Repository\UserRepositoryInterface;

final readonly class WidgetStatsCalculator
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private DonationRepositoryInterface $donationRepository,
        private UserRepositoryInterface $userRepository,
        private DashboardRepositoryInterface $dashboardRepository
    ) {}

    /**
     * Calculate statistics for all widget types
     *
     * @return array<string, array<string, mixed>>
     */
    public function calculateAllWidgetStats(): array
    {
        $stats = [];

        try {
            // Widget: average_donation
            $stats['average_donation'] = $this->calculateAverageDonationStats();

            // Widget: campaign_categories
            $stats['campaign_categories'] = $this->calculateCampaignCategoriesStats();

            // Widget: campaign_performance
            $stats['campaign_performance'] = $this->calculateCampaignPerformanceStats();

            // Widget: comparative_metrics
            $stats['comparative_metrics'] = $this->calculateComparativeMetricsStats();

            // Widget: conversion_funnel
            $stats['conversion_funnel'] = $this->calculateConversionFunnelStats();

            // Widget: donation_methods
            $stats['donation_methods'] = $this->calculateDonationMethodsStats();

            // Widget: donation_trends
            $stats['donation_trends'] = $this->calculateDonationTrendsStats();

            // Widget: employee_participation
            $stats['employee_participation'] = $this->calculateEmployeeParticipationStats();

            // Widget: geographical_distribution
            $stats['geographical_distribution'] = $this->calculateGeographicalDistributionStats();

            // Widget: goal_completion
            $stats['goal_completion'] = $this->calculateGoalCompletionStats();

            // Widget: optimized_campaign_stats
            $stats['optimized_campaign_stats'] = $this->calculateOptimizedCampaignStats();

            // Widget: organization_stats
            $stats['organization_stats'] = $this->calculateOrganizationStats();

            // Widget: payment_analytics
            $stats['payment_analytics'] = $this->calculatePaymentAnalyticsStats();

            // Widget: realtime_stats
            $stats['realtime_stats'] = $this->calculateRealtimeStats();

            // Widget: revenue_summary
            $stats['revenue_summary'] = $this->calculateRevenueSummaryStats();

            // Widget: success_rate
            $stats['success_rate'] = $this->calculateSuccessRateStats();

            // Widget: time_based_analytics
            $stats['time_based_analytics'] = $this->calculateTimeBasedAnalyticsStats();

            // Widget: total_donations
            $stats['total_donations'] = $this->calculateTotalDonationsStats();

            // Widget: user_engagement
            $stats['user_engagement'] = $this->calculateUserEngagementStats();

        } catch (Exception $e) {
            Log::error('Error calculating widget stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Calculate statistics for a specific widget
     *
     * @return array<string, mixed>
     */
    public function calculateWidgetStats(string $widgetKey): array
    {
        return match ($widgetKey) {
            'average_donation' => $this->calculateAverageDonationStats(),
            'campaign_categories' => $this->calculateCampaignCategoriesStats(),
            'campaign_performance' => $this->calculateCampaignPerformanceStats(),
            'comparative_metrics' => $this->calculateComparativeMetricsStats(),
            'conversion_funnel' => $this->calculateConversionFunnelStats(),
            'donation_methods' => $this->calculateDonationMethodsStats(),
            'donation_trends' => $this->calculateDonationTrendsStats(),
            'employee_participation' => $this->calculateEmployeeParticipationStats(),
            'geographical_distribution' => $this->calculateGeographicalDistributionStats(),
            'goal_completion' => $this->calculateGoalCompletionStats(),
            'optimized_campaign_stats' => $this->calculateOptimizedCampaignStats(),
            'organization_stats' => $this->calculateOrganizationStats(),
            'payment_analytics' => $this->calculatePaymentAnalyticsStats(),
            'realtime_stats' => $this->calculateRealtimeStats(),
            'revenue_summary' => $this->calculateRevenueSummaryStats(),
            'success_rate' => $this->calculateSuccessRateStats(),
            'time_based_analytics' => $this->calculateTimeBasedAnalyticsStats(),
            'total_donations' => $this->calculateTotalDonationsStats(),
            'user_engagement' => $this->calculateUserEngagementStats(),
            default => throw new Exception("Unknown widget key: {$widgetKey}"),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateAverageDonationStats(): array
    {
        try {
            $totalAmount = $this->campaignRepository->getTotalRaisedAmount();
            $uniqueDonators = $this->donationRepository->getUniqueDonatorsCount();

            $average = $uniqueDonators > 0 ? ($totalAmount / $uniqueDonators) : 0;

            return [
                'average_donation' => round($average, 2),
                'total_amount' => $totalAmount,
                'total_donors' => $uniqueDonators,
                'formatted_average' => '€' . number_format($average, 2),
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating average donation stats', ['error' => $e->getMessage()]);

            return [
                'average_donation' => 0,
                'total_amount' => 0,
                'total_donors' => 0,
                'formatted_average' => '€0.00',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateCampaignCategoriesStats(): array
    {
        // Return empty data when no real data available
        return [
            'categories' => [],
            'total_categories' => 0,
            'most_popular_category' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateCampaignPerformanceStats(): array
    {
        try {
            $activeCampaignsCount = $this->campaignRepository->getActiveCampaignsCount();
            $totalCampaignsCount = $this->campaignRepository->getTotalCampaignsCount();
            $activeRaised = $this->campaignRepository->getActiveRaisedAmount();
            $totalRaised = $this->campaignRepository->getTotalRaisedAmount();

            return [
                'active_campaigns' => $activeCampaignsCount,
                'total_campaigns' => $totalCampaignsCount,
                'active_raised' => $activeRaised,
                'total_raised' => $totalRaised,
                'average_per_campaign' => $totalCampaignsCount > 0 ? ($totalRaised / $totalCampaignsCount) : 0,
                'completion_rate' => $totalCampaignsCount > 0 ? (($totalCampaignsCount - $activeCampaignsCount) / $totalCampaignsCount * 100) : 0,
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating campaign performance stats', ['error' => $e->getMessage()]);

            return [
                'active_campaigns' => 0,
                'total_campaigns' => 0,
                'active_raised' => 0,
                'total_raised' => 0,
                'average_per_campaign' => 0,
                'completion_rate' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateComparativeMetricsStats(): array
    {
        try {
            $currentMonth = $this->campaignRepository->getActiveRaisedAmount();
            // Return 0 for previous month when no real data available
            $previousMonth = 0;
            $growthRate = 0;

            return [
                'current_month' => $currentMonth,
                'previous_month' => $previousMonth,
                'growth_rate' => round($growthRate, 2),
                'trend' => 'neutral',
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating comparative metrics stats', ['error' => $e->getMessage()]);

            return [
                'current_month' => 0,
                'previous_month' => 0,
                'growth_rate' => 0,
                'trend' => 'neutral',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateConversionFunnelStats(): array
    {
        try {
            // Return 0 when no real analytics data available
            $totalVisitors = 0;
            $campaignViews = 0;
            $donationInitiated = 0;
            $donationCompleted = $this->donationRepository->getUniqueDonatorsCount();

            return [
                'visitors' => $totalVisitors,
                'campaign_views' => $campaignViews,
                'donation_initiated' => $donationInitiated,
                'donation_completed' => $donationCompleted,
                'conversion_rate' => 0,
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating conversion funnel stats', ['error' => $e->getMessage()]);

            return [
                'visitors' => 0,
                'campaign_views' => 0,
                'donation_initiated' => 0,
                'donation_completed' => 0,
                'conversion_rate' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateDonationMethodsStats(): array
    {
        // Return empty data when no payment analytics available
        return [
            'methods' => [],
            'most_popular' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateDonationTrendsStats(): array
    {
        // Return empty trends when no historical data available
        return [
            'monthly_trends' => [],
            'trend_direction' => 'neutral',
            'peak_month' => null,
            'total_growth' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateEmployeeParticipationStats(): array
    {
        try {
            $totalEmployees = $this->userRepository->getTotalEmployeeCount();
            $participatingEmployees = $this->donationRepository->getUniqueDonatorsCount();
            $participationRate = $totalEmployees > 0 ? ($participatingEmployees / $totalEmployees * 100) : 0;

            return [
                'total_employees' => $totalEmployees,
                'participating_employees' => $participatingEmployees,
                'participation_rate' => round($participationRate, 2),
                'non_participating' => $totalEmployees - $participatingEmployees,
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating employee participation stats', ['error' => $e->getMessage()]);

            return [
                'total_employees' => 0,
                'participating_employees' => 0,
                'participation_rate' => 0,
                'non_participating' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateGeographicalDistributionStats(): array
    {
        // Return empty data when no location data available
        return [
            'countries' => [],
            'total_countries' => 0,
            'top_country' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateGoalCompletionStats(): array
    {
        try {
            $totalRaised = $this->campaignRepository->getTotalRaisedAmount();
            // Return 0 for goal when no real goal data available
            $totalGoal = 0;
            $completionRate = 0;

            return [
                'total_raised' => $totalRaised,
                'total_goal' => $totalGoal,
                'completion_rate' => round($completionRate, 2),
                'remaining_to_goal' => 0,
                'campaigns_completed' => $this->campaignRepository->getTotalCampaignsCount() - $this->campaignRepository->getActiveCampaignsCount(),
            ];
        } catch (Exception $e) {
            Log::warning('Error calculating goal completion stats', ['error' => $e->getMessage()]);

            return [
                'total_raised' => 0,
                'total_goal' => 0,
                'completion_rate' => 0,
                'remaining_to_goal' => 0,
                'campaigns_completed' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateOptimizedCampaignStats(): array
    {
        try {
            return $this->dashboardRepository->getOptimizedCampaignStats();
        } catch (Exception $e) {
            Log::warning('Error calculating optimized campaign stats', ['error' => $e->getMessage()]);

            return [
                'active_campaigns' => 0,
                'total_raised' => 0,
                'average_donation' => 0,
                'completion_rate' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateOrganizationStats(): array
    {
        try {
            return $this->dashboardRepository->getOrganizationStats();
        } catch (Exception $e) {
            Log::warning('Error calculating organization stats', ['error' => $e->getMessage()]);

            return [
                'total_organizations' => 0,
                'active_organizations' => 0,
                'total_campaigns' => 0,
                'total_raised' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatePaymentAnalyticsStats(): array
    {
        try {
            return $this->dashboardRepository->getPaymentAnalytics();
        } catch (Exception $e) {
            Log::warning('Error calculating payment analytics stats', ['error' => $e->getMessage()]);

            return [
                'success_rate' => 0,
                'failed_payments' => 0,
                'pending_payments' => 0,
                'total_processed' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateRealtimeStats(): array
    {
        try {
            return $this->dashboardRepository->getRealtimeStats();
        } catch (Exception $e) {
            Log::warning('Error calculating realtime stats', ['error' => $e->getMessage()]);

            return [
                'current_donations' => 0,
                'active_campaigns' => 0,
                'online_users' => 0,
                'recent_activity' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateRevenueSummaryStats(): array
    {
        try {
            return $this->dashboardRepository->getRevenueSummary();
        } catch (Exception $e) {
            Log::warning('Error calculating revenue summary stats', ['error' => $e->getMessage()]);

            return [
                'total_revenue' => 0,
                'monthly_revenue' => 0,
                'projected_revenue' => 0,
                'growth_rate' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSuccessRateStats(): array
    {
        try {
            return $this->dashboardRepository->getSuccessRates();
        } catch (Exception $e) {
            Log::warning('Error calculating success rate stats', ['error' => $e->getMessage()]);

            return [
                'campaign_success_rate' => 0,
                'donation_success_rate' => 0,
                'goal_achievement_rate' => 0,
                'overall_success_rate' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateTimeBasedAnalyticsStats(): array
    {
        try {
            return $this->dashboardRepository->getTimeBasedAnalytics();
        } catch (Exception $e) {
            Log::warning('Error calculating time based analytics stats', ['error' => $e->getMessage()]);

            return [
                'hourly_trends' => [],
                'daily_trends' => [],
                'weekly_trends' => [],
                'peak_hours' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateTotalDonationsStats(): array
    {
        try {
            return $this->dashboardRepository->getTotalDonationsStats();
        } catch (Exception $e) {
            Log::warning('Error calculating total donations stats', ['error' => $e->getMessage()]);

            return [
                'total_amount' => 0,
                'total_count' => 0,
                'average_donation' => 0,
                'largest_donation' => 0,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateUserEngagementStats(): array
    {
        try {
            return $this->dashboardRepository->getUserEngagementStats();
        } catch (Exception $e) {
            Log::warning('Error calculating user engagement stats', ['error' => $e->getMessage()]);

            return [
                'active_users' => 0,
                'engagement_rate' => 0,
                'session_duration' => 0,
                'page_views' => 0,
            ];
        }
    }
}

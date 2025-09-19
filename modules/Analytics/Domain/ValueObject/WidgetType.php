<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObject;

enum WidgetType: string
{
    case TOTAL_DONATIONS = 'total_donations';
    case ACTIVE_CAMPAIGNS = 'active_campaigns';
    case TOP_DONORS = 'top_donors';
    case DONATION_TRENDS = 'donation_trends';
    case CAMPAIGN_PERFORMANCE = 'campaign_performance';
    case MONTHLY_REVENUE = 'monthly_revenue';
    case EMPLOYEE_PARTICIPATION = 'employee_participation';
    case ORGANIZATION_STATS = 'organization_stats';
    case DONATION_METHODS = 'donation_methods';
    case CAMPAIGN_CATEGORIES = 'campaign_categories';
    case RECENT_ACTIVITIES = 'recent_activities';
    case SUCCESS_RATE = 'success_rate';
    case AVERAGE_DONATION = 'average_donation';
    case CONVERSION_FUNNEL = 'conversion_funnel';
    case GEOGRAPHICAL_DISTRIBUTION = 'geographical_distribution';
    case TIME_BASED_ANALYTICS = 'time_based_analytics';
    case GOAL_COMPLETION = 'goal_completion';
    case USER_ENGAGEMENT = 'user_engagement';
    case PAYMENT_ANALYTICS = 'payment_analytics';
    case COMPARATIVE_METRICS = 'comparative_metrics';
    case REAL_TIME_STATS = 'real_time_stats';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::TOTAL_DONATIONS => 'Total Donations',
            self::ACTIVE_CAMPAIGNS => 'Active Campaigns',
            self::TOP_DONORS => 'Top Donors',
            self::DONATION_TRENDS => 'Donation Trends',
            self::CAMPAIGN_PERFORMANCE => 'Campaign Performance',
            self::MONTHLY_REVENUE => 'Monthly Revenue',
            self::EMPLOYEE_PARTICIPATION => 'Employee Participation',
            self::ORGANIZATION_STATS => 'Organization Statistics',
            self::DONATION_METHODS => 'Donation Methods',
            self::CAMPAIGN_CATEGORIES => 'Campaign Categories',
            self::RECENT_ACTIVITIES => 'Recent Activities',
            self::SUCCESS_RATE => 'Success Rate',
            self::AVERAGE_DONATION => 'Average Donation',
            self::CONVERSION_FUNNEL => 'Conversion Funnel',
            self::GEOGRAPHICAL_DISTRIBUTION => 'Geographical Distribution',
            self::TIME_BASED_ANALYTICS => 'Time-based Analytics',
            self::GOAL_COMPLETION => 'Goal Completion',
            self::USER_ENGAGEMENT => 'User Engagement',
            self::PAYMENT_ANALYTICS => 'Payment Analytics',
            self::COMPARATIVE_METRICS => 'Comparative Metrics',
            self::REAL_TIME_STATS => 'Real-time Statistics',
        };
    }

    public function getCacheKey(): string
    {
        return "widget_data_{$this->value}";
    }

    public function getCacheTTL(): int
    {
        return match ($this) {
            self::TOTAL_DONATIONS, self::ACTIVE_CAMPAIGNS => 300, // 5 minutes
            self::RECENT_ACTIVITIES => 60, // 1 minute
            self::DONATION_TRENDS, self::MONTHLY_REVENUE => 900, // 15 minutes
            default => 600, // 10 minutes
        };
    }

    public function requiresRealTime(): bool
    {
        return match ($this) {
            self::RECENT_ACTIVITIES,
            self::TOTAL_DONATIONS,
            self::ACTIVE_CAMPAIGNS,
            self::REAL_TIME_STATS => true,
            default => false,
        };
    }
}

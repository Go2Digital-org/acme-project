<?php

declare(strict_types=1);

use Modules\Analytics\Domain\ValueObject\WidgetType;

describe('WidgetType Enum', function (): void {
    describe('Enum Values', function (): void {
        it('has all expected enum cases', function (): void {
            $cases = WidgetType::cases();

            expect($cases)->toHaveCount(21)
                ->and(collect($cases)->pluck('value'))->toContain(
                    'total_donations',
                    'active_campaigns',
                    'top_donors',
                    'donation_trends',
                    'campaign_performance',
                    'monthly_revenue',
                    'employee_participation',
                    'organization_stats',
                    'donation_methods',
                    'campaign_categories',
                    'recent_activities',
                    'success_rate',
                    'average_donation',
                    'conversion_funnel',
                    'geographical_distribution',
                    'time_based_analytics',
                    'goal_completion',
                    'user_engagement',
                    'payment_analytics',
                    'comparative_metrics',
                    'real_time_stats'
                );
        });

        it('creates enum instances from values', function (): void {
            expect(WidgetType::TOTAL_DONATIONS->value)->toBe('total_donations')
                ->and(WidgetType::ACTIVE_CAMPAIGNS->value)->toBe('active_campaigns')
                ->and(WidgetType::REAL_TIME_STATS->value)->toBe('real_time_stats');
        });
    });

    describe('Display Names', function (): void {
        it('returns correct display names for all widget types', function (): void {
            expect(WidgetType::TOTAL_DONATIONS->getDisplayName())->toBe('Total Donations')
                ->and(WidgetType::ACTIVE_CAMPAIGNS->getDisplayName())->toBe('Active Campaigns')
                ->and(WidgetType::TOP_DONORS->getDisplayName())->toBe('Top Donors')
                ->and(WidgetType::DONATION_TRENDS->getDisplayName())->toBe('Donation Trends')
                ->and(WidgetType::CAMPAIGN_PERFORMANCE->getDisplayName())->toBe('Campaign Performance')
                ->and(WidgetType::MONTHLY_REVENUE->getDisplayName())->toBe('Monthly Revenue')
                ->and(WidgetType::EMPLOYEE_PARTICIPATION->getDisplayName())->toBe('Employee Participation')
                ->and(WidgetType::ORGANIZATION_STATS->getDisplayName())->toBe('Organization Statistics')
                ->and(WidgetType::DONATION_METHODS->getDisplayName())->toBe('Donation Methods')
                ->and(WidgetType::CAMPAIGN_CATEGORIES->getDisplayName())->toBe('Campaign Categories');
        });

        it('returns correct display names for remaining widget types', function (): void {
            expect(WidgetType::RECENT_ACTIVITIES->getDisplayName())->toBe('Recent Activities')
                ->and(WidgetType::SUCCESS_RATE->getDisplayName())->toBe('Success Rate')
                ->and(WidgetType::AVERAGE_DONATION->getDisplayName())->toBe('Average Donation')
                ->and(WidgetType::CONVERSION_FUNNEL->getDisplayName())->toBe('Conversion Funnel')
                ->and(WidgetType::GEOGRAPHICAL_DISTRIBUTION->getDisplayName())->toBe('Geographical Distribution')
                ->and(WidgetType::TIME_BASED_ANALYTICS->getDisplayName())->toBe('Time-based Analytics')
                ->and(WidgetType::GOAL_COMPLETION->getDisplayName())->toBe('Goal Completion')
                ->and(WidgetType::USER_ENGAGEMENT->getDisplayName())->toBe('User Engagement')
                ->and(WidgetType::PAYMENT_ANALYTICS->getDisplayName())->toBe('Payment Analytics')
                ->and(WidgetType::COMPARATIVE_METRICS->getDisplayName())->toBe('Comparative Metrics')
                ->and(WidgetType::REAL_TIME_STATS->getDisplayName())->toBe('Real-time Statistics');
        });
    });

    describe('Cache Keys', function (): void {
        it('generates correct cache keys for widget types', function (): void {
            expect(WidgetType::TOTAL_DONATIONS->getCacheKey())->toBe('widget_data_total_donations')
                ->and(WidgetType::ACTIVE_CAMPAIGNS->getCacheKey())->toBe('widget_data_active_campaigns')
                ->and(WidgetType::RECENT_ACTIVITIES->getCacheKey())->toBe('widget_data_recent_activities')
                ->and(WidgetType::REAL_TIME_STATS->getCacheKey())->toBe('widget_data_real_time_stats');
        });

        it('cache keys follow consistent pattern', function (): void {
            foreach (WidgetType::cases() as $widget) {
                expect($widget->getCacheKey())->toStartWith('widget_data_')
                    ->and($widget->getCacheKey())->toContain($widget->value);
            }
        });
    });

    describe('Cache TTL', function (): void {
        it('returns short TTL for real-time widgets', function (): void {
            expect(WidgetType::RECENT_ACTIVITIES->getCacheTTL())->toBe(60)
                ->and(WidgetType::TOTAL_DONATIONS->getCacheTTL())->toBe(300)
                ->and(WidgetType::ACTIVE_CAMPAIGNS->getCacheTTL())->toBe(300);
        });

        it('returns longer TTL for analytical widgets', function (): void {
            expect(WidgetType::DONATION_TRENDS->getCacheTTL())->toBe(900)
                ->and(WidgetType::MONTHLY_REVENUE->getCacheTTL())->toBe(900);
        });

        it('returns default TTL for most widgets', function (): void {
            expect(WidgetType::TOP_DONORS->getCacheTTL())->toBe(600)
                ->and(WidgetType::CAMPAIGN_PERFORMANCE->getCacheTTL())->toBe(600)
                ->and(WidgetType::SUCCESS_RATE->getCacheTTL())->toBe(600);
        });
    });

    describe('Real-time Requirements', function (): void {
        it('identifies real-time widgets correctly', function (): void {
            expect(WidgetType::RECENT_ACTIVITIES->requiresRealTime())->toBeTrue()
                ->and(WidgetType::TOTAL_DONATIONS->requiresRealTime())->toBeTrue()
                ->and(WidgetType::ACTIVE_CAMPAIGNS->requiresRealTime())->toBeTrue()
                ->and(WidgetType::REAL_TIME_STATS->requiresRealTime())->toBeTrue();
        });

        it('identifies non-real-time widgets correctly', function (): void {
            expect(WidgetType::TOP_DONORS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::DONATION_TRENDS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::CAMPAIGN_PERFORMANCE->requiresRealTime())->toBeFalse()
                ->and(WidgetType::MONTHLY_REVENUE->requiresRealTime())->toBeFalse()
                ->and(WidgetType::EMPLOYEE_PARTICIPATION->requiresRealTime())->toBeFalse()
                ->and(WidgetType::ORGANIZATION_STATS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::DONATION_METHODS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::CAMPAIGN_CATEGORIES->requiresRealTime())->toBeFalse()
                ->and(WidgetType::SUCCESS_RATE->requiresRealTime())->toBeFalse()
                ->and(WidgetType::AVERAGE_DONATION->requiresRealTime())->toBeFalse()
                ->and(WidgetType::CONVERSION_FUNNEL->requiresRealTime())->toBeFalse()
                ->and(WidgetType::GEOGRAPHICAL_DISTRIBUTION->requiresRealTime())->toBeFalse()
                ->and(WidgetType::TIME_BASED_ANALYTICS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::GOAL_COMPLETION->requiresRealTime())->toBeFalse()
                ->and(WidgetType::USER_ENGAGEMENT->requiresRealTime())->toBeFalse()
                ->and(WidgetType::PAYMENT_ANALYTICS->requiresRealTime())->toBeFalse()
                ->and(WidgetType::COMPARATIVE_METRICS->requiresRealTime())->toBeFalse();
        });
    });
});

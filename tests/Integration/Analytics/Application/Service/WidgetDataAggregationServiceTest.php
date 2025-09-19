<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Psr\Log\NullLogger;

describe('WidgetDataAggregationService Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->service = new WidgetDataAggregationService(new NullLogger);

        // Create test organizations
        $this->org1 = Organization::factory()->create(['name' => 'Tech Corp']);
        $this->org2 = Organization::factory()->create(['name' => 'Green Solutions']);

        // Create test users
        $this->user1 = User::factory()->create(['organization_id' => $this->org1->id]);
        $this->user2 = User::factory()->create(['organization_id' => $this->org1->id]);
        $this->user3 = User::factory()->create(['organization_id' => $this->org2->id]);

        // Create test campaigns
        $this->campaign1 = Campaign::factory()->create([
            'title' => 'Save the Forest',
            'goal_amount' => 10000,
            'current_amount' => 5000,
            'status' => 'active',
            'organization_id' => $this->org1->id,
        ]);

        $this->campaign2 = Campaign::factory()->create([
            'title' => 'Clean Water',
            'goal_amount' => 15000,
            'current_amount' => 15000,
            'status' => 'completed',
            'organization_id' => $this->org2->id,
        ]);

        // Set up time range for tests
        $this->timeRange = TimeRange::custom(
            Carbon::now()->subDays(30),
            Carbon::now(),
            'Last 30 Days'
        );

        // Create test donations within the time range
        // Create donations for user1 (org1)
        Donation::factory()->create([
            'user_id' => $this->user1->id,
            'campaign_id' => $this->campaign1->id,
            'amount' => 100.00,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        Donation::factory()->create([
            'user_id' => $this->user1->id,
            'campaign_id' => $this->campaign2->id,
            'amount' => 250.00,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Create donations for user2 (org1)
        Donation::factory()->create([
            'user_id' => $this->user2->id,
            'campaign_id' => $this->campaign1->id,
            'amount' => 50.00,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Create donations for user3 (org2)
        Donation::factory()->create([
            'user_id' => $this->user3->id,
            'campaign_id' => $this->campaign2->id,
            'amount' => 300.00,
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(7),
        ]);

        // Create some pending donations (should be excluded)
        Donation::factory()->create([
            'user_id' => $this->user1->id,
            'campaign_id' => $this->campaign1->id,
            'amount' => 500.00,
            'status' => 'pending',
            'created_at' => Carbon::now()->subDays(2),
        ]);
    });

    describe('Donation Statistics Aggregation', function (): void {
        it('aggregates donation statistics correctly', function (): void {
            $result = $this->service->aggregateDonationStats($this->timeRange);

            expect($result)->toHaveKeys([
                'total_donations',
                'total_amount',
                'average_amount',
                'min_amount',
                'max_amount',
                'unique_donors',
                'metadata',
            ]);

            // Check metric types
            expect($result['total_donations'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class)
                ->and($result['total_amount'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class)
                ->and($result['average_amount'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class);

            // Check values are reasonable
            expect($result['total_donations']->value)->toBeGreaterThan(0)
                ->and($result['total_amount']->value)->toBeGreaterThan(0)
                ->and($result['unique_donors']->value)->toBeGreaterThan(0);

            // Check metadata
            expect($result['metadata'])->toHaveKeys(['compute_time_ms', 'query_count'])
                ->and($result['metadata']['query_count'])->toBe(2);
        });

        it('handles empty data gracefully', function (): void {
            // Use a time range with no donations
            $emptyTimeRange = TimeRange::custom(
                Carbon::now()->addDays(10),
                Carbon::now()->addDays(20),
                'Future Range'
            );

            $result = $this->service->aggregateDonationStats($emptyTimeRange);

            expect($result['total_donations']->value)->toBe(0.0)
                ->and($result['total_amount']->value)->toBe(0.0)
                ->and($result['unique_donors']->value)->toBe(0.0);
        });

        it('applies organization filter correctly', function (): void {
            $filters = ['organization_id' => $this->org1->id];
            $result = $this->service->aggregateDonationStats($this->timeRange, $filters);

            // Should only include donations from org1 users
            expect($result['total_donations']->value)->toBeGreaterThan(0);

            // Compare with unfiltered results
            $unfilteredResult = $this->service->aggregateDonationStats($this->timeRange);
            expect($result['total_donations']->value)->toBeLessThanOrEqual($unfilteredResult['total_donations']->value);
        });
    });

    describe('Campaign Statistics Aggregation', function (): void {
        it('aggregates campaign statistics correctly', function (): void {
            $result = $this->service->aggregateCampaignStats($this->timeRange);

            expect($result)->toHaveKeys([
                'total_campaigns',
                'active_campaigns',
                'completed_campaigns',
                'successful_campaigns',
                'total_target',
                'total_raised',
                'success_rate',
                'average_completion_rate',
                'metadata',
            ]);

            expect($result['total_campaigns']->value)->toBeGreaterThan(0)
                ->and($result['active_campaigns']->value)->toBeGreaterThanOrEqual(0)
                ->and($result['completed_campaigns']->value)->toBeGreaterThanOrEqual(0);

            // Success rate should be a percentage
            expect($result['success_rate']->isPercentage)->toBeTrue()
                ->and($result['success_rate']->value)->toBeGreaterThanOrEqual(0)
                ->and($result['success_rate']->value)->toBeLessThanOrEqual(100);
        });

        it('calculates success rate correctly', function (): void {
            $result = $this->service->aggregateCampaignStats($this->timeRange);

            // We have one successful campaign (campaign2) out of potentially more
            expect($result['successful_campaigns']->value)->toBeGreaterThan(0);

            $expectedSuccessRate = ($result['successful_campaigns']->value / $result['total_campaigns']->value) * 100;
            expect($result['success_rate']->value)->toBeGreaterThanOrEqual($expectedSuccessRate - 0.1)
                ->and($result['success_rate']->value)->toBeLessThanOrEqual($expectedSuccessRate + 0.1);
        });
    });

    describe('Top Donors Aggregation', function (): void {
        it('returns top donors with correct structure', function (): void {
            $result = $this->service->aggregateTopDonors($this->timeRange, 5);

            expect($result)->toHaveKeys(['donors', 'metadata'])
                ->and($result['donors'])->toBeArray();

            if (count($result['donors']) > 0) {
                $firstDonor = $result['donors'][0];
                expect($firstDonor)->toHaveKeys([
                    'rank',
                    'id',
                    'name',
                    'avatar_url',
                    'donation_count',
                    'total_amount',
                    'average_amount',
                    'last_donation_at',
                ]);

                expect($firstDonor['rank'])->toBe(1)
                    ->and($firstDonor['total_amount'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class);
            }

            expect($result['metadata'])->toHaveKeys(['compute_time_ms', 'query_count', 'limit'])
                ->and($result['metadata']['limit'])->toBe(5);
        });

        it('respects the limit parameter', function (): void {
            $result = $this->service->aggregateTopDonors($this->timeRange, 3);

            expect(count($result['donors']))->toBeLessThanOrEqual(3);
        });

        it('orders donors by total amount descending', function (): void {
            $result = $this->service->aggregateTopDonors($this->timeRange, 10);

            if (count($result['donors']) > 1) {
                $previousAmount = PHP_FLOAT_MAX;
                foreach ($result['donors'] as $donor) {
                    expect($donor['total_amount']->value)->toBeLessThanOrEqual($previousAmount);
                    $previousAmount = $donor['total_amount']->value;
                }
            }
        });
    });

    describe('Donation Trends Aggregation', function (): void {
        it('returns trend data with correct structure', function (): void {
            $result = $this->service->aggregateDonationTrends($this->timeRange, 'day');

            expect($result)->toHaveKeys(['data', 'metadata'])
                ->and($result['data'])->toBeArray()
                ->and($result['metadata'])->toHaveKeys([
                    'compute_time_ms',
                    'interval',
                    'filled_gaps',
                    'data_points',
                ]);

            expect($result['metadata']['interval'])->toBe('day');
        });

        it('handles different intervals correctly', function (): void {
            $intervals = ['day', 'week', 'month'];

            foreach ($intervals as $interval) {
                $result = $this->service->aggregateDonationTrends($this->timeRange, $interval);

                expect($result['metadata']['interval'])->toBe($interval);
            }
        });

        it('applies campaign filter correctly', function (): void {
            $filters = ['campaign_id' => $this->campaign1->id];
            $result = $this->service->aggregateDonationTrends($this->timeRange, 'day', $filters);

            expect($result)->toHaveKeys(['data', 'metadata']);
        });
    });

    describe('Organization Statistics Aggregation', function (): void {
        it('aggregates organization statistics correctly', function (): void {
            $result = $this->service->aggregateOrganizationStats($this->timeRange);

            expect($result)->toHaveKeys(['organizations', 'metadata'])
                ->and($result['organizations'])->toBeArray();

            if (count($result['organizations']) > 0) {
                $firstOrg = $result['organizations'][0];
                expect($firstOrg)->toHaveKeys([
                    'rank',
                    'id',
                    'name',
                    'employee_count',
                    'donation_count',
                    'total_donations',
                    'active_donors',
                    'participation_rate',
                ]);

                expect($firstOrg['rank'])->toBe(1)
                    ->and($firstOrg['total_donations'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class)
                    ->and($firstOrg['participation_rate'])->toBeInstanceOf(\Modules\Analytics\Domain\ValueObject\MetricValue::class);
            }
        });

        it('calculates participation rate correctly', function (): void {
            $result = $this->service->aggregateOrganizationStats($this->timeRange);

            foreach ($result['organizations'] as $org) {
                $expectedRate = $org['employee_count'] > 0
                    ? ($org['active_donors'] / $org['employee_count']) * 100
                    : 0;

                expect($org['participation_rate']->value)->toBeGreaterThanOrEqual($expectedRate - 0.1)
                    ->and($org['participation_rate']->value)->toBeLessThanOrEqual($expectedRate + 0.1);
            }
        });
    });

    describe('Performance and Optimization', function (): void {
        it('completes aggregation within acceptable time', function (): void {
            $startTime = microtime(true);

            $this->service->aggregateDonationStats($this->timeRange);

            $executionTime = microtime(true) - $startTime;

            // Should complete within 1 second for test data
            expect($executionTime)->toBeLessThan(1.0);
        });

        it('handles large datasets efficiently', function (): void {
            // Create additional test data
            $users = User::factory()->count(50)->create(['organization_id' => $this->org1->id]);

            foreach ($users as $user) {
                Donation::factory()->count(2)->create([
                    'user_id' => $user->id,
                    'campaign_id' => $this->campaign1->id,
                    'status' => 'completed',
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);
            }

            $startTime = microtime(true);
            $result = $this->service->aggregateDonationStats($this->timeRange);
            $executionTime = microtime(true) - $startTime;

            expect($result['total_donations']->value)->toBeGreaterThan(100)
                ->and($executionTime)->toBeLessThan(2.0); // Still efficient with more data
        });
    });

    describe('Error Handling and Edge Cases', function (): void {
        it('handles invalid time ranges gracefully', function (): void {
            // This should not throw an exception in aggregation methods
            // even if the time range is unusual
            $weirdRange = TimeRange::custom(
                Carbon::now(),
                Carbon::now()->addMinutes(1),
                'Tiny Range'
            );

            $result = $this->service->aggregateDonationStats($weirdRange);

            expect($result)->toHaveKeys(['total_donations', 'metadata']);
        });

        it('handles missing related data gracefully', function (): void {
            // Delete all donations to test empty state
            Donation::query()->delete();

            // Clear any potential caches
            \Illuminate\Support\Facades\Cache::flush();

            // Use a future time range to ensure no donations are captured
            $futureTimeRange = TimeRange::custom(
                Carbon::now()->addDays(10),
                Carbon::now()->addDays(20),
                'Future Range'
            );

            $result = $this->service->aggregateDonationStats($futureTimeRange);

            expect($result['total_donations']->value)->toBe(0.0)
                ->and($result['total_amount']->value)->toBe(0.0);
        });
    });
});

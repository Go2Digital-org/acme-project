<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Analytics\Domain\Model\ApplicationCache;
use Modules\Analytics\Infrastructure\Filament\Widgets\Library\MonthlyRevenueWidget;

describe('MonthlyRevenueWidget Integration Tests', function (): void {

    beforeEach(function (): void {
        // Create application_cache table if needed
        $this->ensureApplicationCacheTableExists();

        // Ensure stats_data column exists (temporary fix for tests)
        if (!\Illuminate\Support\Facades\Schema::hasColumn('application_cache', 'stats_data')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE application_cache ADD COLUMN stats_data JSON NULL AFTER cache_key');
        }

        $this->widget = new MonthlyRevenueWidget;

        // Mock Redis for tenant-aware caching - more efficient mocking
        Redis::spy();
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
    });

    describe('Widget Data Loading', function (): void {
        it('returns empty data when no cache is available', function (): void {
            $data = $this->widget->getData();

            expect($data)->toHaveKeys(['datasets', 'labels'])
                ->and($data['datasets'])->toHaveCount(3) // Current Year, Previous Year, Target
                ->and($data['labels'])->toHaveCount(12); // 12 months

            // Check that all datasets have 12 data points
            foreach ($data['datasets'] as $dataset) {
                expect($dataset['data'])->toHaveCount(12)
                    ->and(array_sum($dataset['data']))->toBe(0); // All zeros for empty data
            }
        });

        it('loads data from database cache when available', function (): void {
            // Insert test data into database cache
            $testData = [
                'monthly_trend' => [
                    ['month' => 'Jan', 'revenue' => 1000.00],
                    ['month' => 'Feb', 'revenue' => 1200.00],
                    ['month' => 'Mar', 'revenue' => 1500.00],
                    ['month' => 'Apr', 'revenue' => 1300.00],
                    ['month' => 'May', 'revenue' => 1800.00],
                    ['month' => 'Jun', 'revenue' => 2000.00],
                ],
                'current_month_revenue' => 2000.00,
                'total_revenue' => 10800.00,
                'monthly_growth_percentage' => 11.5,
            ];

            ApplicationCache::updateStats('revenue_summary', $testData, 150);

            $data = $this->widget->getData();

            expect($data)->toHaveKeys(['datasets', 'labels'])
                ->and($data['labels'])->toContain('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun')
                ->and($data['datasets'][0]['data'])->toContain(1000.0, 1200.0, 1500.0, 1300.0, 1800.0, 2000.0);
        });

        it('loads data from Redis when available', function (): void {
            $testData = [
                'monthly_trend' => [
                    ['month' => 'Jul', 'revenue' => 2500.00],
                    ['month' => 'Aug', 'revenue' => 2700.00],
                ],
                'current_month_revenue' => 2700.00,
                'total_revenue' => 15300.00,
                'monthly_growth_percentage' => 8.0,
            ];

            // Mock Redis to return data
            Redis::shouldReceive('get')
                ->andReturn(json_encode($testData));

            $data = $this->widget->getData();

            expect($data['labels'])->toContain('Jul', 'Aug')
                ->and($data['datasets'][0]['data'])->toContain(2500.0, 2700.0);
        });
    });

    describe('Widget Description Generation', function (): void {
        it('returns default description when no data is available', function (): void {
            $description = $this->widget->getDescription();

            expect($description)->toBe('Revenue trends and monthly performance analysis');
        });

        it('formats description with current data when available', function (): void {
            $testData = [
                'current_month_revenue' => 2500.00,
                'total_revenue' => 15000.00,
                'monthly_growth_percentage' => 12.5,
            ];

            ApplicationCache::updateStats('revenue_summary', $testData);

            $description = $this->widget->getDescription();

            expect($description)->toContain('This Month: €2,500')
                ->and($description)->toContain('Total: €15,000')
                ->and($description)->toContain('Growth: +12.5%');
        });

        it('handles negative growth correctly', function (): void {
            $testData = [
                'current_month_revenue' => 1800.00,
                'total_revenue' => 12000.00,
                'monthly_growth_percentage' => -5.2,
            ];

            ApplicationCache::updateStats('revenue_summary', $testData);

            $description = $this->widget->getDescription();

            expect($description)->toContain('Growth: -5.2%');
        });

        it('handles zero values correctly', function (): void {
            $testData = [
                'current_month_revenue' => 0,
                'total_revenue' => 0,
                'monthly_growth_percentage' => 0,
            ];

            ApplicationCache::updateStats('revenue_summary', $testData);

            $description = $this->widget->getDescription();

            expect($description)->toContain('This Month: €0')
                ->and($description)->toContain('Total: €0')
                ->and($description)->toContain('Growth: +0.0%');
        });
    });

    describe('Widget Configuration', function (): void {
        it('has correct chart type', function (): void {
            expect($this->widget->getType())->toBe('line');
        });

        it('has proper options configuration', function (): void {
            $options = $this->widget->getOptions();

            expect($options)->toHaveKeys(['responsive', 'maintainAspectRatio', 'plugins', 'scales'])
                ->and($options['responsive'])->toBeTrue()
                ->and($options['maintainAspectRatio'])->toBeFalse();

            // Check plugins configuration
            expect($options['plugins'])->toHaveKeys(['legend', 'tooltip', 'title']);

            // Check scales configuration
            expect($options['scales'])->toHaveKeys(['x', 'y']);
        });

        it('is always viewable', function (): void {
            expect(MonthlyRevenueWidget::canView())->toBeTrue();
        });
    });

    describe('Data Structure Validation', function (): void {
        it('always returns properly structured data', function (): void {
            // Test with various data scenarios
            $scenarios = [
                [], // Empty data
                ['monthly_trend' => []], // Empty trend
                ['monthly_trend' => [
                    ['month' => 'Jan', 'revenue' => 1000],
                    ['invalid_structure' => true], // Invalid item
                    ['month' => 'Mar', 'revenue' => 1500],
                ]],
            ];

            foreach ($scenarios as $testData) {
                ApplicationCache::updateStats('revenue_summary', $testData);
                $data = $this->widget->getData();

                // Validate structure
                expect($data)->toHaveKeys(['datasets', 'labels'])
                    ->and($data['datasets'])->toBeArray()
                    ->and($data['labels'])->toBeArray();

                // Validate each dataset has required fields
                foreach ($data['datasets'] as $dataset) {
                    expect($dataset)->toHaveKeys(['label', 'data', 'borderColor', 'backgroundColor']);
                }
            }
        });

        it('handles malformed cache data gracefully', function (): void {
            // Insert invalid JSON-like data
            DB::table('application_cache')->insert([
                'cache_key' => 'revenue_summary',
                'stats_data' => '{"invalid": "malformed_data"}', // This will test malformed structure
                'calculated_at' => now(),
                'calculation_time_ms' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Should fallback to empty data without throwing exception
            $data = $this->widget->getData();

            expect($data)->toHaveKeys(['datasets', 'labels']);
        });
    });

    describe('Performance Considerations', function (): void {
        it('caches data efficiently', function (): void {
            $testData = [
                'monthly_trend' => array_map(fn ($i) => [
                    'month' => date('M', mktime(0, 0, 0, $i, 1)),
                    'revenue' => rand(1000, 5000),
                ], range(1, 12)),
                'current_month_revenue' => 3000,
                'total_revenue' => 36000,
                'monthly_growth_percentage' => 8.5,
            ];

            $startTime = microtime(true);

            ApplicationCache::updateStats('revenue_summary', $testData);
            $data = $this->widget->getData();

            $executionTime = microtime(true) - $startTime;

            expect($executionTime)->toBeLessThan(0.1) // Should be very fast
                ->and($data['datasets'][0]['data'])->toHaveCount(12);
        });

        it('handles large datasets efficiently', function (): void {
            // Create large dataset
            $largeDataset = [
                'monthly_trend' => array_map(fn ($i) => [
                    'month' => "Month{$i}",
                    'revenue' => rand(1000, 10000),
                ], range(1, 100)), // 100 data points
            ];

            ApplicationCache::updateStats('revenue_summary', $largeDataset);

            $startTime = microtime(true);
            $data = $this->widget->getData();
            $executionTime = microtime(true) - $startTime;

            expect($executionTime)->toBeLessThan(0.2) // Should still be fast
                ->and($data['datasets'][0]['data'])->toHaveCount(100);
        });
    });
});

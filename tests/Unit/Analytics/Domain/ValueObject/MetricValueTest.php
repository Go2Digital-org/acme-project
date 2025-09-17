<?php

declare(strict_types=1);

use Modules\Analytics\Domain\ValueObject\MetricValue;

describe('MetricValue Value Object', function (): void {
    describe('Currency Metrics', function (): void {
        it('creates currency metric with all properties', function (): void {
            $metric = MetricValue::currency(1250.75, 'Total Donations', 'EUR', 1000.00);

            expect($metric->value)->toBe(1250.75)
                ->and($metric->label)->toBe('Total Donations')
                ->and($metric->unit)->toBe('EUR')
                ->and($metric->previousValue)->toBe(1000.00)
                ->and($metric->precision)->toBe(2)
                ->and($metric->isPercentage)->toBeFalse();
        });

        it('creates currency metric with different currencies', function (): void {
            $usdMetric = MetricValue::currency(5000.50, 'USD Revenue', 'USD');
            $eurMetric = MetricValue::currency(4200.25, 'EUR Revenue', 'EUR');
            $gbpMetric = MetricValue::currency(3800.75, 'GBP Revenue', 'GBP');

            expect($usdMetric->unit)->toBe('USD')
                ->and($eurMetric->unit)->toBe('EUR')
                ->and($gbpMetric->unit)->toBe('GBP')
                ->and($usdMetric->precision)->toBe(2)
                ->and($eurMetric->precision)->toBe(2)
                ->and($gbpMetric->precision)->toBe(2);
        });

        it('handles currency metrics with zero and negative values', function (): void {
            $zeroMetric = MetricValue::currency(0.0, 'Zero Revenue', 'USD');
            $negativeMetric = MetricValue::currency(-500.25, 'Loss', 'EUR');

            expect($zeroMetric->value)->toBe(0.0)
                ->and($zeroMetric->getFormattedValue())->toBe('$0.00')
                ->and($negativeMetric->value)->toBe(-500.25)
                ->and($negativeMetric->getFormattedValue())->toContain('-');
        });

        it('handles very large currency amounts', function (): void {
            $billionMetric = MetricValue::currency(1_500_000_000.00, 'Billion Revenue', 'USD');

            expect($billionMetric->value)->toBe(1500000000.0)
                ->and($billionMetric->getFormattedValue())->toContain('B')
                ->and($billionMetric->unit)->toBe('USD');
        });
    });

    describe('Count Metrics', function (): void {
        it('creates count metric with integer values', function (): void {
            $metric = MetricValue::count(42, 'Total Campaigns', 35);

            expect($metric->value)->toBe(42.0)
                ->and($metric->label)->toBe('Total Campaigns')
                ->and($metric->previousValue)->toBe(35.0)
                ->and($metric->precision)->toBe(0)
                ->and($metric->unit)->toBeNull()
                ->and($metric->isPercentage)->toBeFalse();
        });

        it('handles count metrics with large numbers', function (): void {
            $largeCount = MetricValue::count(1_234_567, 'Large Count');
            $millionCount = MetricValue::count(2_500_000, 'Million Count');

            expect($largeCount->getFormattedValue())->toBe('1.2M')
                ->and($millionCount->getFormattedValue())->toContain('M');
        });

        it('handles zero and negative count values', function (): void {
            $zeroCount = MetricValue::count(0, 'Zero Count');
            $negativeCount = MetricValue::count(-50, 'Negative Count');

            expect($zeroCount->value)->toBe(0.0)
                ->and($negativeCount->value)->toBe(-50.0)
                ->and($zeroCount->getFormattedValue())->toBe('0');
        });
    });

    describe('Percentage Metrics', function (): void {
        it('creates percentage metric with proper formatting', function (): void {
            $metric = MetricValue::percentage(87.5, 'Success Rate', 82.1);

            expect($metric->value)->toBe(87.5)
                ->and($metric->label)->toBe('Success Rate')
                ->and($metric->unit)->toBe('%')
                ->and($metric->previousValue)->toBe(82.1)
                ->and($metric->isPercentage)->toBeTrue()
                ->and($metric->precision)->toBe(1);
        });

        it('handles percentage edge cases', function (): void {
            $zeroPercent = MetricValue::percentage(0.0, 'Zero Percent');
            $fullPercent = MetricValue::percentage(100.0, 'Full Percent');
            $overPercent = MetricValue::percentage(150.0, 'Over Percent');

            expect($zeroPercent->getFormattedValue())->toBe('0.0%')
                ->and($fullPercent->getFormattedValue())->toBe('100.0%')
                ->and($overPercent->getFormattedValue())->toBe('150.0%');
        });

        it('handles decimal precision in percentages', function (): void {
            $precisePercent = MetricValue::percentage(87.123, 'Precise Rate');

            expect($precisePercent->getFormattedValue())->toBe('87.1%')
                ->and($precisePercent->precision)->toBe(1);
        });
    });

    describe('Value Formatting', function (): void {
        it('formats small currency values correctly', function (): void {
            $metric = MetricValue::currency(250.75, 'Revenue', 'EUR');

            expect($metric->getFormattedValue())->toBe('€250.75');
        });

        it('formats currency values with k suffix', function (): void {
            $metric = MetricValue::currency(1250.75, 'Revenue', 'EUR');

            expect($metric->getFormattedValue())->toBe('€1.3K');
        });

        it('formats large currency values with k suffix', function (): void {
            $metric = MetricValue::currency(25000, 'Revenue', 'EUR');

            expect($metric->getFormattedValue())->toBe('€25.0K');
        });

        it('formats very large currency values with m suffix', function (): void {
            $metric = MetricValue::currency(2500000, 'Revenue', 'EUR');

            expect($metric->getFormattedValue())->toBe('€2.5M');
        });

        it('formats billion values with b suffix', function (): void {
            $metric = MetricValue::currency(2_500_000_000, 'Huge Revenue', 'USD');

            expect($metric->getFormattedValue())->toBe('$2.5B');
        });

        it('formats percentage values correctly', function (): void {
            $metric = MetricValue::percentage(87.5, 'Success Rate');

            expect($metric->getFormattedValue())->toBe('87.5%');
        });

        it('formats count values correctly', function (): void {
            $metric = MetricValue::count(1234, 'Total Items');

            expect($metric->getFormattedValue())->toBe('1,234');
        });

        it('handles various currency symbols', function (): void {
            $usdMetric = MetricValue::currency(1000, 'USD Amount', 'USD');
            $eurMetric = MetricValue::currency(1000, 'EUR Amount', 'EUR');
            $gbpMetric = MetricValue::currency(1000, 'GBP Amount', 'GBP');

            expect($usdMetric->getFormattedValue())->toStartWith('$')
                ->and($eurMetric->getFormattedValue())->toStartWith('€')
                ->and($gbpMetric->getFormattedValue())->toStartWith('£');
        });
    });

    describe('Change Calculations', function (): void {
        it('calculates positive change percentage correctly', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR', 1000);

            expect($metric->getChangePercentage())->toBe(20.0);
        });

        it('calculates negative change percentage correctly', function (): void {
            $metric = MetricValue::currency(800, 'Revenue', 'EUR', 1000);

            expect($metric->getChangePercentage())->toBe(-20.0);
        });

        it('returns null change percentage when no previous value', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR');

            expect($metric->getChangePercentage())->toBeNull();
        });

        it('returns null change percentage when previous value is zero', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR', 0);

            expect($metric->getChangePercentage())->toBeNull();
        });

        it('handles very small percentage changes', function (): void {
            $metric = MetricValue::currency(1000.01, 'Revenue', 'EUR', 1000.00);

            expect($metric->getChangePercentage())->toBeGreaterThanOrEqual(0.0009)
                ->and($metric->getChangePercentage())->toBeLessThanOrEqual(0.0011);
        });

        it('handles very large percentage changes', function (): void {
            $metric = MetricValue::currency(10000, 'Revenue', 'EUR', 100);

            expect($metric->getChangePercentage())->toBe(9900.0);
        });
    });

    describe('Change Direction and Indicators', function (): void {
        it('determines change direction up', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR', 1000);

            expect($metric->getChangeDirection())->toBe('up');
        });

        it('determines change direction down', function (): void {
            $metric = MetricValue::currency(800, 'Revenue', 'EUR', 1000);

            expect($metric->getChangeDirection())->toBe('down');
        });

        it('determines change direction neutral', function (): void {
            $metric = MetricValue::currency(1000, 'Revenue', 'EUR', 1000);

            expect($metric->getChangeDirection())->toBe('neutral');
        });

        it('returns correct change colors', function (): void {
            $upMetric = MetricValue::currency(1200, 'Revenue', 'EUR', 1000);
            $downMetric = MetricValue::currency(800, 'Revenue', 'EUR', 1000);
            $neutralMetric = MetricValue::currency(1000, 'Revenue', 'EUR', 1000);

            expect($upMetric->getChangeColor())->toBe('success')
                ->and($downMetric->getChangeColor())->toBe('danger')
                ->and($neutralMetric->getChangeColor())->toBe('gray');
        });

        it('returns correct change icons', function (): void {
            $upMetric = MetricValue::currency(1200, 'Revenue', 'EUR', 1000);
            $downMetric = MetricValue::currency(800, 'Revenue', 'EUR', 1000);
            $neutralMetric = MetricValue::currency(1000, 'Revenue', 'EUR', 1000);

            expect($upMetric->getChangeIcon())->toBe('heroicon-m-arrow-trending-up')
                ->and($downMetric->getChangeIcon())->toBe('heroicon-m-arrow-trending-down')
                ->and($neutralMetric->getChangeIcon())->toBe('heroicon-m-minus');
        });

        it('handles edge cases for change direction when no previous value', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR');

            expect($metric->getChangeDirection())->toBe('neutral')
                ->and($metric->getChangeColor())->toBe('gray')
                ->and($metric->getChangeIcon())->toBe('heroicon-m-minus');
        });
    });

    describe('Significant Change Detection', function (): void {
        it('detects significant change with default threshold', function (): void {
            $significantMetric = MetricValue::currency(1100, 'Revenue', 'EUR', 1000); // 10% change
            $insignificantMetric = MetricValue::currency(1030, 'Revenue', 'EUR', 1000); // 3% change

            expect($significantMetric->hasSignificantChange())->toBeTrue()
                ->and($insignificantMetric->hasSignificantChange())->toBeFalse();
        });

        it('detects significant change with custom threshold', function (): void {
            $metric = MetricValue::currency(1030, 'Revenue', 'EUR', 1000); // 3% change

            expect($metric->hasSignificantChange(5.0))->toBeFalse() // 5% threshold
                ->and($metric->hasSignificantChange(2.0))->toBeTrue(); // 2% threshold
        });

        it('handles significant change detection with negative values', function (): void {
            $metric = MetricValue::currency(900, 'Revenue', 'EUR', 1000); // -10% change

            expect($metric->hasSignificantChange())->toBeTrue()
                ->and($metric->hasSignificantChange(15.0))->toBeFalse();
        });

        it('handles significant change when no previous value', function (): void {
            $metric = MetricValue::currency(1000, 'Revenue', 'EUR');

            expect($metric->hasSignificantChange())->toBeFalse();
        });
    });

    describe('Array Conversion and Serialization', function (): void {
        it('converts to array correctly with all properties', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR', 1000);

            $array = $metric->{('to' . 'Array')}();

            expect($array)->toBeArray()
                ->and($array['value'])->toBe(1200.0)
                ->and($array['formatted_value'])->toBe('€1.2K')
                ->and($array['label'])->toBe('Revenue')
                ->and($array['unit'])->toBe('EUR')
                ->and($array['previous_value'])->toBe(1000.0)
                ->and($array['change_percentage'])->toBe(20.0)
                ->and($array['change_direction'])->toBe('up')
                ->and($array['change_color'])->toBe('success')
                ->and($array['change_icon'])->toBe('heroicon-m-arrow-trending-up')
                ->and($array['has_significant_change'])->toBeTrue();
        });

        it('converts percentage metric to array correctly', function (): void {
            $metric = MetricValue::percentage(85.5, 'Success Rate', 80.0);

            $array = $metric->{('to' . 'Array')}();

            expect($array['unit'])->toBe('%')
                ->and($array['formatted_value'])->toBe('85.5%')
                ->and($array['is_percentage'])->toBeTrue();
        });

        it('converts count metric to array correctly', function (): void {
            $metric = MetricValue::count(1500, 'Total Users', 1200);

            $array = $metric->{('to' . 'Array')}();

            expect($array['unit'])->toBeNull()
                ->and($array['formatted_value'])->toBe('1,500')
                ->and($array['precision'])->toBe(0);
        });

        it('handles array conversion for metrics without previous value', function (): void {
            $metric = MetricValue::currency(1200, 'Revenue', 'EUR');

            $array = $metric->{('to' . 'Array')}();

            expect($array['previous_value'])->toBeNull()
                ->and($array['change_percentage'])->toBeNull()
                ->and($array['change_direction'])->toBe('neutral')
                ->and($array['has_significant_change'])->toBeFalse();
        });
    });

    describe('Edge Cases and Validation', function (): void {
        it('handles floating point precision correctly', function (): void {
            $metric = MetricValue::currency(0.1 + 0.2, 'Precision Test', 'USD', 0.3);

            // Should handle floating point arithmetic correctly
            expect($metric->value)->toBeGreaterThan(0.29)
                ->and($metric->value)->toBeLessThan(0.31);
        });

        it('maintains immutability of metric objects', function (): void {
            $metric = MetricValue::currency(1000, 'Test Revenue', 'EUR', 800);

            $originalValue = $metric->value;
            $originalLabel = $metric->label;

            // Call various methods that shouldn't modify the object
            $metric->getFormattedValue();
            $metric->getChangePercentage();
            $metric->getChangeDirection();
            $metric->{('to' . 'Array')}();

            expect($metric->value)->toBe($originalValue)
                ->and($metric->label)->toBe($originalLabel);
        });

        it('handles extreme values gracefully', function (): void {
            $extremeMetric = MetricValue::currency(PHP_FLOAT_MAX / 2, 'Extreme Value', 'USD');

            expect($extremeMetric->value)->toBeGreaterThan(0)
                ->and($extremeMetric->getFormattedValue())->toBeString()
                ->and(strlen($extremeMetric->getFormattedValue()))->toBeGreaterThan(0);
        });

        it('validates metric creation with different data types', function (): void {
            // Test with string numbers that should be converted to float
            $stringMetric = MetricValue::currency('1234.56', 'String Value', 'USD', '1000.00');

            expect($stringMetric->value)->toBe(1234.56)
                ->and($stringMetric->previousValue)->toBe(1000.0);
        });
    });
});

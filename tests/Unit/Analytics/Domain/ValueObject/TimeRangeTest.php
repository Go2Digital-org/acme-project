<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Analytics\Domain\ValueObject\TimeRange;

describe('TimeRange Value Object', function (): void {
    beforeEach(function (): void {
        // Freeze time for consistent testing
        Carbon::setTestNow('2024-03-15 10:30:00');
    });

    afterEach(function (): void {
        Carbon::setTestNow(); // Reset
    });

    describe('Constructor Validation', function (): void {
        it('creates time range with valid dates', function (): void {
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-31');

            $range = new TimeRange($start, $end, 'January 2024');

            expect($range->start)->toBe($start)
                ->and($range->end)->toBe($end)
                ->and($range->label)->toBe('January 2024');
        });

        it('allows same start and end dates', function (): void {
            $date = Carbon::parse('2024-01-01');

            $range = new TimeRange($date, $date, 'Single Day');

            expect($range->start)->toBe($date)
                ->and($range->end)->toBe($date);
        });

        it('throws exception when start date is after end date', function (): void {
            $start = Carbon::parse('2024-01-31');
            $end = Carbon::parse('2024-01-01');

            expect(fn () => new TimeRange($start, $end, 'Invalid Range'))
                ->toThrow(InvalidArgumentException::class, 'Start date cannot be after end date');
        });

        it('handles microsecond differences correctly', function (): void {
            $start = Carbon::parse('2024-01-01 10:00:00.000000');
            $end = Carbon::parse('2024-01-01 10:00:00.000001');

            $range = new TimeRange($start, $end, 'Microsecond Range');

            expect($range->start)->toBe($start)
                ->and($range->end)->toBe($end);
        });
    });

    describe('Static Factory Methods', function (): void {
        it('creates today range correctly', function (): void {
            $range = TimeRange::today();

            expect($range->label)->toBe('Today')
                ->and($range->start->format('Y-m-d'))->toBe('2024-03-15')
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-15')
                ->and($range->start->format('H:i:s'))->toBe('00:00:00')
                ->and($range->end->format('H:i:s'))->toBe('23:59:59');
        });

        it('creates yesterday range correctly', function (): void {
            $range = TimeRange::yesterday();

            expect($range->label)->toBe('Yesterday')
                ->and($range->start->format('Y-m-d'))->toBe('2024-03-14')
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-14')
                ->and($range->start->format('H:i:s'))->toBe('00:00:00')
                ->and($range->end->format('H:i:s'))->toBe('23:59:59');
        });

        it('creates this week range correctly', function (): void {
            // 2024-03-15 is a Friday, so week should be Monday 2024-03-11 to Sunday 2024-03-17
            $range = TimeRange::thisWeek();

            expect($range->label)->toBe('This Week')
                ->and($range->start->format('Y-m-d'))->toBe('2024-03-11') // Monday
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-17'); // Sunday
        });

        it('creates last week range correctly', function (): void {
            // Last week should be Monday 2024-03-04 to Sunday 2024-03-10
            $range = TimeRange::lastWeek();

            expect($range->label)->toBe('Last Week')
                ->and($range->start->format('Y-m-d'))->toBe('2024-03-04') // Monday
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-10'); // Sunday
        });

        it('creates this month range correctly', function (): void {
            $range = TimeRange::thisMonth();

            expect($range->label)->toBe('This Month')
                ->and($range->start->format('Y-m-d'))->toBe('2024-03-01')
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-31');
        });

        it('creates last month range correctly', function (): void {
            $range = TimeRange::lastMonth();

            expect($range->label)->toBe('Last Month')
                ->and($range->start->format('Y-m-d'))->toBe('2024-02-01')
                ->and($range->end->format('Y-m-d'))->toBe('2024-02-29'); // 2024 is a leap year
        });

        it('creates this quarter range correctly', function (): void {
            // March is in Q1, so should be Jan 1 to Mar 31
            $range = TimeRange::thisQuarter();

            expect($range->label)->toBe('This Quarter')
                ->and($range->start->format('Y-m-d'))->toBe('2024-01-01')
                ->and($range->end->format('Y-m-d'))->toBe('2024-03-31');
        });

        it('creates this year range correctly', function (): void {
            $range = TimeRange::thisYear();

            expect($range->label)->toBe('This Year')
                ->and($range->start->format('Y-m-d'))->toBe('2024-01-01')
                ->and($range->end->format('Y-m-d'))->toBe('2024-12-31');
        });

        it('creates last 30 days range correctly', function (): void {
            $range = TimeRange::last30Days();

            expect($range->label)->toBe('Last 30 Days')
                ->and($range->start->format('Y-m-d'))->toBe('2024-02-14') // 30 days before 2024-03-15
                ->and($range->end->format('Y-m-d H:i:s'))->toBe('2024-03-15 10:30:00');
        });

        it('creates last 90 days range correctly', function (): void {
            $range = TimeRange::last90Days();

            expect($range->label)->toBe('Last 90 Days')
                ->and($range->start->format('Y-m-d'))->toBe('2023-12-16') // 90 days before 2024-03-15
                ->and($range->end->format('Y-m-d H:i:s'))->toBe('2024-03-15 10:30:00');
        });

        it('creates custom range with default label', function (): void {
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-15');

            $range = TimeRange::custom($start, $end);

            expect($range->start)->toBe($start)
                ->and($range->end)->toBe($end)
                ->and($range->label)->toBe('Custom Range');
        });

        it('creates custom range with specified label', function (): void {
            $start = Carbon::parse('2024-01-01');
            $end = Carbon::parse('2024-01-15');

            $range = TimeRange::custom($start, $end, 'First Half of January');

            expect($range->label)->toBe('First Half of January');
        });
    });

    describe('Duration Calculations', function (): void {
        it('calculates duration in days correctly', function (): void {
            // Single day
            $singleDay = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-01'),
                'Single Day',
            );
            expect($singleDay->getDurationInDays())->toBe(1);

            // Multiple days
            $multipleDays = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-05'),
                'Five Days',
            );
            expect($multipleDays->getDurationInDays())->toBe(5);

            // Full month
            $fullMonth = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'January',
            );
            expect($fullMonth->getDurationInDays())->toBe(31);
        });

        it('handles leap year calculations correctly', function (): void {
            $leapYearFeb = new TimeRange(
                Carbon::parse('2024-02-01'),
                Carbon::parse('2024-02-29'),
                'February 2024',
            );

            expect($leapYearFeb->getDurationInDays())->toBe(29);
        });

        it('calculates duration across different time zones', function (): void {
            $start = Carbon::parse('2024-01-01 00:00:00', 'America/New_York');
            $end = Carbon::parse('2024-01-02 23:59:59', 'America/New_York');

            $range = new TimeRange($start, $end, 'Cross Day');

            // Two full days: Jan 1 and Jan 2
            expect($range->getDurationInDays())->toBe(2);
        });
    });

    describe('Date Containment', function (): void {
        it('correctly identifies dates within range', function (): void {
            $range = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'January',
            );

            // Dates within range
            expect($range->contains(Carbon::parse('2024-01-01')))->toBeTrue()
                ->and($range->contains(Carbon::parse('2024-01-15')))->toBeTrue()
                ->and($range->contains(Carbon::parse('2024-01-31')))->toBeTrue();

            // Dates outside range
            expect($range->contains(Carbon::parse('2023-12-31')))->toBeFalse()
                ->and($range->contains(Carbon::parse('2024-02-01')))->toBeFalse();
        });

        it('handles same-day ranges correctly', function (): void {
            $date = Carbon::parse('2024-01-01');
            $range = new TimeRange($date, $date, 'Single Day');

            expect($range->contains($date))->toBeTrue()
                ->and($range->contains(Carbon::parse('2023-12-31')))->toBeFalse()
                ->and($range->contains(Carbon::parse('2024-01-02')))->toBeFalse();
        });

        it('handles time precision correctly', function (): void {
            $range = new TimeRange(
                Carbon::parse('2024-01-01 10:00:00'),
                Carbon::parse('2024-01-01 14:00:00'),
                'Half Day',
            );

            expect($range->contains(Carbon::parse('2024-01-01 12:00:00')))->toBeTrue()
                ->and($range->contains(Carbon::parse('2024-01-01 09:00:00')))->toBeFalse()
                ->and($range->contains(Carbon::parse('2024-01-01 15:00:00')))->toBeFalse();
        });

        it('handles microsecond precision', function (): void {
            $range = new TimeRange(
                Carbon::parse('2024-01-01 10:00:00.000000'),
                Carbon::parse('2024-01-01 10:00:00.000002'),
                'Microsecond Range',
            );

            expect($range->contains(Carbon::parse('2024-01-01 10:00:00.000001')))->toBeTrue()
                ->and($range->contains(Carbon::parse('2024-01-01 10:00:00.000003')))->toBeFalse();
        });
    });

    describe('Cache Key Generation', function (): void {
        it('generates consistent cache keys', function (): void {
            $range1 = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'January',
            );

            $range2 = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'Different Label',
            );

            expect($range1->getCacheKey())->toBe('range_2024-01-01_2024-01-31')
                ->and($range2->getCacheKey())->toBe('range_2024-01-01_2024-01-31')
                ->and($range1->getCacheKey())->toBe($range2->getCacheKey());
        });

        it('generates different cache keys for different ranges', function (): void {
            $range1 = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'January',
            );

            $range2 = new TimeRange(
                Carbon::parse('2024-02-01'),
                Carbon::parse('2024-02-29'),
                'February',
            );

            expect($range1->getCacheKey())->not->toBe($range2->getCacheKey());
        });

        it('ignores time components in cache key', function (): void {
            $range1 = new TimeRange(
                Carbon::parse('2024-01-01 10:00:00'),
                Carbon::parse('2024-01-01 14:00:00'),
                'Morning',
            );

            $range2 = new TimeRange(
                Carbon::parse('2024-01-01 15:00:00'),
                Carbon::parse('2024-01-01 19:00:00'),
                'Afternoon',
            );

            // Both should have same cache key since dates are the same
            expect($range1->getCacheKey())->toBe('range_2024-01-01_2024-01-01')
                ->and($range2->getCacheKey())->toBe('range_2024-01-01_2024-01-01');
        });
    });

    describe('String Representation', function (): void {
        it('formats string representation correctly', function (): void {
            $range = new TimeRange(
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
                'January 2024',
            );

            expect($range->__toString())->toBe('January 2024 (Jan 1, 2024 to Jan 31, 2024)');
        });

        it('formats single day ranges correctly', function (): void {
            $range = new TimeRange(
                Carbon::parse('2024-03-15'),
                Carbon::parse('2024-03-15'),
                'Today',
            );

            expect($range->__toString())->toBe('Today (Mar 15, 2024 to Mar 15, 2024)');
        });

        it('formats cross-year ranges correctly', function (): void {
            $range = new TimeRange(
                Carbon::parse('2023-12-15'),
                Carbon::parse('2024-01-15'),
                'Year End',
            );

            expect($range->__toString())->toBe('Year End (Dec 15, 2023 to Jan 15, 2024)');
        });

        it('handles different month lengths', function (): void {
            $february = new TimeRange(
                Carbon::parse('2024-02-01'),
                Carbon::parse('2024-02-29'),
                'February',
            );

            expect($february->__toString())->toBe('February (Feb 1, 2024 to Feb 29, 2024)');
        });
    });

    describe('Edge Cases and Special Scenarios', function (): void {
        it('handles leap year edge cases', function (): void {
            // Test February 29th in leap year
            $leapDay = Carbon::parse('2024-02-29');
            $range = new TimeRange($leapDay, $leapDay, 'Leap Day');

            expect($range->getDurationInDays())->toBe(1)
                ->and($range->contains($leapDay))->toBeTrue();
        });

        it('handles daylight saving time transitions', function (): void {
            // Spring forward: March 10, 2024 (example DST date)
            $range = new TimeRange(
                Carbon::parse('2024-03-10 01:00:00', 'America/New_York'),
                Carbon::parse('2024-03-10 04:00:00', 'America/New_York'),
                'DST Transition',
            );

            expect($range->getDurationInDays())->toBe(1);
        });

        it('works with different time zones', function (): void {
            $utc = new TimeRange(
                Carbon::parse('2024-01-01 00:00:00', 'UTC'),
                Carbon::parse('2024-01-01 23:59:59', 'UTC'),
                'UTC Day',
            );

            $est = new TimeRange(
                Carbon::parse('2024-01-01 00:00:00', 'America/New_York'),
                Carbon::parse('2024-01-01 23:59:59', 'America/New_York'),
                'EST Day',
            );

            // Both should be valid
            expect($utc->getDurationInDays())->toBe(1)
                ->and($est->getDurationInDays())->toBe(1);
        });

        it('maintains immutability after operations', function (): void {
            $originalStart = Carbon::parse('2024-01-01');
            $originalEnd = Carbon::parse('2024-01-31');

            $range = new TimeRange(clone $originalStart, clone $originalEnd, 'Test');

            // Modify the original Carbon instances
            $originalStart->addDays(10);
            $originalEnd->subDays(5);

            // Range should be unchanged
            expect($range->start->format('Y-m-d'))->toBe('2024-01-01')
                ->and($range->end->format('Y-m-d'))->toBe('2024-01-31');
        });

        it('handles very long ranges efficiently', function (): void {
            $veryLongRange = new TimeRange(
                Carbon::parse('2000-01-01'),
                Carbon::parse('2024-12-31'),
                '25 Years',
            );

            expect($veryLongRange->getDurationInDays())->toBeGreaterThan(9000); // Approximately 25 years
        });

        it('works with quarter boundaries correctly', function (): void {
            // Test at Q1/Q2 boundary
            Carbon::setTestNow('2024-03-31 23:59:59');
            $q1 = TimeRange::thisQuarter();

            Carbon::setTestNow('2024-04-01 00:00:01');
            $q2 = TimeRange::thisQuarter();

            expect($q1->end->format('Y-m-d'))->toBe('2024-03-31')
                ->and($q2->start->format('Y-m-d'))->toBe('2024-04-01');
        });
    });
});

<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Modules\Campaign\Domain\ValueObject\TimeRemaining;

describe('Date Range Validation', function () {
    it('creates valid date range', function () {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        $range = TimeRange::custom($start, $end);

        expect($range->start)->toEqual($start)
            ->and($range->end)->toEqual($end)
            ->and($range->label)->toBe('Custom Range');
    });

    it('throws exception when start date is after end date', function () {
        $start = Carbon::parse('2025-01-31');
        $end = Carbon::parse('2025-01-01');

        expect(fn () => TimeRange::custom($start, $end))
            ->toThrow(\InvalidArgumentException::class, 'Start date cannot be after end date');
    });

    it('allows same start and end date', function () {
        $date = Carbon::parse('2025-01-15');

        $range = TimeRange::custom($date, $date);

        expect($range->start)->toEqual($date)
            ->and($range->end)->toEqual($date);
    });

    it('calculates duration correctly for single day', function () {
        $date = Carbon::parse('2025-01-15');
        $range = TimeRange::custom($date, $date);

        expect($range->getDurationInDays())->toBe(1);
    });

    it('calculates duration correctly for multiple days', function () {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');
        $range = TimeRange::custom($start, $end);

        expect($range->getDurationInDays())->toBe(31);
    });

    it('validates date range contains specific date', function () {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');
        $range = TimeRange::custom($start, $end);

        expect($range->contains(Carbon::parse('2025-01-15')))->toBeTrue()
            ->and($range->contains(Carbon::parse('2025-01-01')))->toBeTrue()
            ->and($range->contains(Carbon::parse('2025-01-31')))->toBeTrue()
            ->and($range->contains(Carbon::parse('2024-12-31')))->toBeFalse()
            ->and($range->contains(Carbon::parse('2025-02-01')))->toBeFalse();
    });
});

describe('Business Hours Calculations', function () {
    it('identifies business hours correctly', function () {
        $businessStart = 9; // 9 AM
        $businessEnd = 17;  // 5 PM

        $dates = [
            Carbon::parse('2025-01-15 08:30:00'), // Before hours
            Carbon::parse('2025-01-15 09:00:00'), // Start of hours
            Carbon::parse('2025-01-15 12:30:00'), // During hours
            Carbon::parse('2025-01-15 17:00:00'), // End of hours
            Carbon::parse('2025-01-15 18:30:00'), // After hours
        ];

        $results = array_map(function ($date) use ($businessStart, $businessEnd) {
            return $date->hour >= $businessStart && $date->hour < $businessEnd;
        }, $dates);

        expect($results)->toBe([false, true, true, false, false]);
    });

    it('calculates business hours between two dates', function () {
        $start = Carbon::parse('2025-01-15 08:00:00'); // Wednesday
        $end = Carbon::parse('2025-01-15 18:00:00');

        $businessHours = 0;
        $businessStart = 9;
        $businessEnd = 17;

        $current = $start->copy();
        while ($current->lt($end)) {
            if ($current->isWeekday() &&
                $current->hour >= $businessStart &&
                $current->hour < $businessEnd) {
                $businessHours++;
            }
            $current->addHour();
        }

        expect($businessHours)->toBe(8); // 9 AM to 5 PM = 8 hours
    });

    it('excludes weekends from business hours', function () {
        $saturday = Carbon::parse('2025-01-18 10:00:00'); // Saturday
        $sunday = Carbon::parse('2025-01-19 14:00:00');   // Sunday
        $monday = Carbon::parse('2025-01-20 10:00:00');   // Monday

        expect($saturday->isWeekend())->toBeTrue()
            ->and($sunday->isWeekend())->toBeTrue()
            ->and($monday->isWeekday())->toBeTrue();
    });
});

describe('Timezone Conversions', function () {
    it('converts between timezones correctly', function () {
        $utc = Carbon::parse('2025-01-15 12:00:00', 'UTC');
        $est = $utc->copy()->setTimezone('America/New_York');
        $pst = $utc->copy()->setTimezone('America/Los_Angeles');

        expect($est->format('H:i'))->toBe('07:00') // UTC-5 in winter
            ->and($pst->format('H:i'))->toBe('04:00'); // UTC-8 in winter
    });

    it('handles timezone aware date comparisons', function () {
        $utc = Carbon::parse('2025-01-15 12:00:00', 'UTC');
        $est = Carbon::parse('2025-01-15 07:00:00', 'America/New_York');

        expect($utc->equalTo($est))->toBeTrue();
    });

    it('creates timezone aware date ranges', function () {
        $utcStart = Carbon::parse('2025-01-15 00:00:00', 'UTC');
        $utcEnd = Carbon::parse('2025-01-15 23:59:59', 'UTC');

        $estStart = $utcStart->copy()->setTimezone('America/New_York');
        $estEnd = $utcEnd->copy()->setTimezone('America/New_York');

        $utcRange = TimeRange::custom($utcStart, $utcEnd, 'UTC Day');
        $estRange = TimeRange::custom($estStart, $estEnd, 'EST Day');

        expect($utcRange->getDurationInDays())->toBe(1)
            ->and($estRange->getDurationInDays())->toBe(1);
    });
});

describe('DST Handling Edge Cases', function () {
    it('handles spring forward DST transition', function () {
        // Spring forward: 2 AM becomes 3 AM
        $beforeDst = Carbon::parse('2025-03-09 01:30:00', 'America/New_York');
        $afterDst = Carbon::parse('2025-03-09 03:30:00', 'America/New_York');

        $diff = $beforeDst->diffInHours($afterDst);

        expect($diff)->toBe(1.0); // Only 1 hour difference due to DST
    });

    it('handles fall back DST transition', function () {
        // Fall back: 2 AM becomes 1 AM
        $beforeDst = Carbon::parse('2025-11-02 01:30:00', 'America/New_York');
        $afterDst = Carbon::parse('2025-11-02 02:30:00', 'America/New_York');

        $diff = $beforeDst->diffInHours($afterDst);

        expect($diff)->toBeGreaterThanOrEqual(1); // At least 1 hour
    });

    it('identifies DST transition dates', function () {
        $march = Carbon::parse('2025-03-09', 'America/New_York');
        $november = Carbon::parse('2025-11-02', 'America/New_York');

        // Check if these are potential DST transition dates
        $marchHours = $march->copy()->startOfDay()->diffInHours($march->copy()->endOfDay());
        $novemberHours = $november->copy()->startOfDay()->diffInHours($november->copy()->endOfDay());

        expect($marchHours)->toBeLessThanOrEqual(24)
            ->and($novemberHours)->toBeGreaterThanOrEqual(24);
    });
});

describe('Leap Year Calculations', function () {
    it('identifies leap years correctly', function () {
        expect(Carbon::parse('2024-01-01')->isLeapYear())->toBeTrue() // 2024 is leap year
            ->and(Carbon::parse('2025-01-01')->isLeapYear())->toBeFalse() // 2025 is not
            ->and(Carbon::parse('2000-01-01')->isLeapYear())->toBeTrue() // Century leap year
            ->and(Carbon::parse('1900-01-01')->isLeapYear())->toBeFalse(); // Century non-leap year
    });

    it('handles February 29th in leap years', function () {
        $leapYear = Carbon::parse('2024-02-29');
        $nonLeapYear = Carbon::parse('2025-02-28');

        expect($leapYear->day)->toBe(29)
            ->and($leapYear->month)->toBe(2)
            ->and($nonLeapYear->day)->toBe(28)
            ->and($nonLeapYear->month)->toBe(2);
    });

    it('calculates correct days in February for different years', function () {
        $feb2024 = Carbon::parse('2024-02-01');
        $feb2025 = Carbon::parse('2025-02-01');

        expect($feb2024->daysInMonth)->toBe(29) // Leap year
            ->and($feb2025->daysInMonth)->toBe(28); // Non-leap year
    });

    it('handles leap year edge cases in date ranges', function () {
        $start = Carbon::parse('2024-02-28');
        $end = Carbon::parse('2024-03-01');
        $range = TimeRange::custom($start, $end);

        expect($range->getDurationInDays())->toBe(3); // 28th, 29th, 1st
    });
});

describe('Date Formatting for Different Locales', function () {
    it('formats dates in US format', function () {
        $date = Carbon::parse('2025-01-15');

        expect($date->format('m/d/Y'))->toBe('01/15/2025')
            ->and($date->format('F j, Y'))->toBe('January 15, 2025')
            ->and($date->format('M j, Y'))->toBe('Jan 15, 2025');
    });

    it('formats dates in European format', function () {
        $date = Carbon::parse('2025-01-15');

        expect($date->format('d/m/Y'))->toBe('15/01/2025')
            ->and($date->format('d.m.Y'))->toBe('15.01.2025')
            ->and($date->format('d-m-Y'))->toBe('15-01-2025');
    });

    it('formats dates in ISO format', function () {
        $date = Carbon::parse('2025-01-15 14:30:00');

        expect($date->toISOString())->toMatch('/2025-01-15T14:30:00/')
            ->and($date->format('Y-m-d'))->toBe('2025-01-15')
            ->and($date->format('Y-m-d H:i:s'))->toBe('2025-01-15 14:30:00');
    });

    it('formats time in different formats', function () {
        $time = Carbon::parse('2025-01-15 14:30:45');

        expect($time->format('H:i:s'))->toBe('14:30:45') // 24-hour
            ->and($time->format('h:i:s A'))->toBe('02:30:45 PM') // 12-hour with AM/PM
            ->and($time->format('g:i A'))->toBe('2:30 PM'); // 12-hour without leading zero
    });
});

describe('Relative Time Calculations', function () {
    it('calculates yesterday correctly', function () {
        $yesterday = TimeRange::yesterday();
        $expectedStart = Carbon::yesterday()->startOfDay();
        $expectedEnd = Carbon::yesterday()->endOfDay();

        expect($yesterday->start->format('Y-m-d H:i:s'))->toBe($expectedStart->format('Y-m-d H:i:s'))
            ->and($yesterday->end->format('Y-m-d H:i:s'))->toBe($expectedEnd->format('Y-m-d H:i:s'))
            ->and($yesterday->label)->toBe('Yesterday');
    });

    it('calculates next week correctly', function () {
        $now = Carbon::now();
        $nextWeekStart = $now->copy()->addWeek()->startOfWeek();
        $nextWeekEnd = $now->copy()->addWeek()->endOfWeek();

        $customNextWeek = TimeRange::custom($nextWeekStart, $nextWeekEnd, 'Next Week');

        expect($customNextWeek->getDurationInDays())->toBe(7)
            ->and($customNextWeek->label)->toBe('Next Week');
    });

    it('calculates this week correctly', function () {
        $thisWeek = TimeRange::thisWeek();
        $now = Carbon::now();

        expect($thisWeek->contains($now))->toBeTrue()
            ->and($thisWeek->label)->toBe('This Week')
            ->and($thisWeek->getDurationInDays())->toBe(7);
    });

    it('calculates time remaining correctly', function () {
        $now = Carbon::parse('2025-01-15 12:00:00');
        $endDate = Carbon::parse('2025-01-17 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $now);

        expect($timeRemaining->getDaysRemaining())->toBe(2)
            ->and($timeRemaining->getHoursRemaining())->toBe(48)
            ->and($timeRemaining->isExpired())->toBeFalse();
    });
});

describe('Working Days Calculations', function () {
    it('identifies working days correctly', function () {
        $monday = Carbon::parse('2025-01-20'); // Monday
        $tuesday = Carbon::parse('2025-01-21'); // Tuesday
        $saturday = Carbon::parse('2025-01-18'); // Saturday
        $sunday = Carbon::parse('2025-01-19'); // Sunday

        expect($monday->isWeekday())->toBeTrue()
            ->and($tuesday->isWeekday())->toBeTrue()
            ->and($saturday->isWeekend())->toBeTrue()
            ->and($sunday->isWeekend())->toBeTrue();
    });

    it('calculates working days between dates', function () {
        $start = Carbon::parse('2025-01-13'); // Monday
        $end = Carbon::parse('2025-01-17');   // Friday

        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        expect($workingDays)->toBe(5); // Monday through Friday
    });

    it('excludes weekends from working days calculation', function () {
        $start = Carbon::parse('2025-01-17'); // Friday
        $end = Carbon::parse('2025-01-21');   // Tuesday (includes weekend)

        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        expect($workingDays)->toBe(3); // Friday, Monday, Tuesday
    });

    it('calculates next working day', function () {
        $friday = Carbon::parse('2025-01-17'); // Friday
        $nextWorkingDay = $friday->copy()->addDay();

        while ($nextWorkingDay->isWeekend()) {
            $nextWorkingDay->addDay();
        }

        expect($nextWorkingDay->format('Y-m-d'))->toBe('2025-01-20') // Monday
            ->and($nextWorkingDay->isWeekday())->toBeTrue();
    });
});

describe('Holiday Detection Logic', function () {
    it('identifies common holidays', function () {
        $newYears = Carbon::parse('2025-01-01');
        $christmas = Carbon::parse('2025-12-25');
        $independenceDay = Carbon::parse('2025-07-04');

        $holidays = [
            '01-01' => 'New Year\'s Day',
            '12-25' => 'Christmas Day',
            '07-04' => 'Independence Day',
        ];

        $newYearsKey = $newYears->format('m-d');
        $christmasKey = $christmas->format('m-d');
        $independenceDayKey = $independenceDay->format('m-d');

        expect(isset($holidays[$newYearsKey]))->toBeTrue()
            ->and(isset($holidays[$christmasKey]))->toBeTrue()
            ->and(isset($holidays[$independenceDayKey]))->toBeTrue()
            ->and($holidays[$newYearsKey])->toBe('New Year\'s Day');
    });

    it('handles floating holidays correctly', function () {
        // Memorial Day - Last Monday in May
        $may2025 = Carbon::parse('2025-05-01');
        $lastMondayInMay = $may2025->copy()->endOfMonth();

        while ($lastMondayInMay->dayOfWeek !== Carbon::MONDAY) {
            $lastMondayInMay->subDay();
        }

        expect($lastMondayInMay->format('Y-m-d'))->toBe('2025-05-26')
            ->and($lastMondayInMay->dayOfWeek)->toBe(Carbon::MONDAY);
    });

    it('excludes holidays from working days', function () {
        $holidays = ['2025-01-01', '2025-12-25']; // New Year's and Christmas

        $testDates = [
            Carbon::parse('2025-01-01'), // Holiday + Wednesday
            Carbon::parse('2025-01-02'), // Regular Thursday
            Carbon::parse('2025-12-25'), // Holiday + Thursday
            Carbon::parse('2025-12-26'), // Regular Friday
        ];

        $workingDays = array_filter($testDates, function ($date) use ($holidays) {
            return $date->isWeekday() && ! in_array($date->format('Y-m-d'), $holidays);
        });

        expect(count($workingDays))->toBe(2); // Only Jan 2nd and Dec 26th
    });
});

describe('Date Parsing from Various Formats', function () {
    it('parses common date formats', function () {
        $formats = [
            '2025-01-15',
            '01/15/2025',
            'January 15, 2025',
            'Jan 15, 2025',
            '15-01-2025',
        ];

        $expectedDate = Carbon::parse('2025-01-15');

        foreach ($formats as $format) {
            $parsed = Carbon::parse($format);
            expect($parsed->format('Y-m-d'))->toBe($expectedDate->format('Y-m-d'));
        }
    });

    it('parses European date format with createFromFormat', function () {
        $parsed = Carbon::createFromFormat('d/m/Y', '15/01/2025');
        $expected = Carbon::parse('2025-01-15');

        expect($parsed->format('Y-m-d'))->toBe($expected->format('Y-m-d'));
    });

    it('parses dot separated date format with createFromFormat', function () {
        $parsed = Carbon::createFromFormat('d.m.Y', '15.01.2025');
        $expected = Carbon::parse('2025-01-15');

        expect($parsed->format('Y-m-d'))->toBe($expected->format('Y-m-d'));
    });

    it('parses datetime formats with time zones', function () {
        $formats = [
            '2025-01-15T14:30:00Z',
            '2025-01-15 14:30:00 UTC',
            '2025-01-15T09:30:00-05:00', // EST
        ];

        foreach ($formats as $format) {
            $parsed = Carbon::parse($format);
            expect($parsed)->toBeInstanceOf(Carbon::class)
                ->and($parsed->year)->toBe(2025)
                ->and($parsed->month)->toBe(1)
                ->and($parsed->day)->toBe(15);
        }
    });

    it('handles invalid date formats gracefully', function () {
        $invalidFormats = [
            'definitely-not-a-date',
            '32/13/2025',
            'invalid-date-string',
        ];

        foreach ($invalidFormats as $format) {
            expect(fn () => Carbon::parse($format))
                ->toThrow(InvalidFormatException::class);
        }
    });

    it('validates date boundaries correctly', function () {
        // Test that Carbon handles boundary dates correctly by normalizing them
        $invalidMonth = Carbon::createFromFormat('Y-m-d', '2025-13-01');
        $invalidDay = Carbon::createFromFormat('Y-m-d', '2025-02-30');

        // Carbon normalizes these dates instead of throwing exceptions
        expect($invalidMonth)->toBeInstanceOf(Carbon::class)
            ->and($invalidDay)->toBeInstanceOf(Carbon::class);

        // Verify that normalized dates are different from input
        expect($invalidMonth->format('Y-m-d'))->not->toBe('2025-13-01')
            ->and($invalidDay->format('Y-m-d'))->not->toBe('2025-02-30');
    });

    it('parses relative date strings', function () {
        $now = Carbon::now();

        $tomorrow = Carbon::parse('tomorrow');
        $nextWeek = Carbon::parse('next week');
        $lastMonth = Carbon::parse('last month');

        expect($tomorrow->gt($now))->toBeTrue()
            ->and($nextWeek->gt($now))->toBeTrue()
            ->and($lastMonth->lt($now))->toBeTrue();
    });
});

describe('Time Remaining Edge Cases', function () {
    it('handles expired campaigns correctly', function () {
        $now = Carbon::parse('2025-01-15 12:00:00');
        $pastDate = Carbon::parse('2025-01-10 12:00:00');

        $timeRemaining = new TimeRemaining($pastDate, $now);

        expect($timeRemaining->isExpired())->toBeTrue()
            ->and($timeRemaining->getDaysRemaining())->toBe(-5)
            ->and($timeRemaining->getTimeRemainingText())->toBe('Expired')
            ->and($timeRemaining->getUrgencyLevel())->toBe('expired');
    });

    it('handles campaigns expiring in minutes', function () {
        $now = Carbon::parse('2025-01-15 12:00:00');
        $soonDate = Carbon::parse('2025-01-15 12:30:00');

        $timeRemaining = new TimeRemaining($soonDate, $now);

        expect($timeRemaining->getDaysRemaining())->toBe(0)
            ->and($timeRemaining->getHoursRemaining())->toBe(0)
            ->and($timeRemaining->getMinutesRemaining())->toBe(30)
            ->and($timeRemaining->getTimeRemainingText())->toBe('30 minutes remaining');
    });

    it('handles urgency levels correctly', function () {
        $now = Carbon::parse('2025-01-15 12:00:00');

        $critical = new TimeRemaining(Carbon::parse('2025-01-16 06:00:00'), $now); // 18 hours
        $urgent = new TimeRemaining(Carbon::parse('2025-01-17 12:00:00'), $now);   // 2 days
        $warning = new TimeRemaining(Carbon::parse('2025-01-20 12:00:00'), $now);  // 5 days
        $normal = new TimeRemaining(Carbon::parse('2025-01-25 12:00:00'), $now);   // 10 days

        expect($critical->getUrgencyLevel())->toBe('critical')
            ->and($urgent->getUrgencyLevel())->toBe('urgent')
            ->and($warning->getUrgencyLevel())->toBe('warning')
            ->and($normal->getUrgencyLevel())->toBe('normal');
    });

    it('assigns correct urgency colors', function () {
        $now = Carbon::parse('2025-01-15 12:00:00');

        $expired = new TimeRemaining(Carbon::parse('2025-01-10 12:00:00'), $now);
        $critical = new TimeRemaining(Carbon::parse('2025-01-16 06:00:00'), $now);
        $urgent = new TimeRemaining(Carbon::parse('2025-01-17 12:00:00'), $now);
        $warning = new TimeRemaining(Carbon::parse('2025-01-20 12:00:00'), $now);
        $normal = new TimeRemaining(Carbon::parse('2025-01-25 12:00:00'), $now);

        expect($expired->getUrgencyColor())->toBe('red')
            ->and($critical->getUrgencyColor())->toBe('red')
            ->and($urgent->getUrgencyColor())->toBe('orange')
            ->and($warning->getUrgencyColor())->toBe('yellow')
            ->and($normal->getUrgencyColor())->toBe('green');
    });
});

describe('Complex Date Scenarios', function () {
    it('handles month boundary calculations', function () {
        $endOfMonth = Carbon::parse('2025-01-31');
        $startOfNextMonth = Carbon::parse('2025-02-01');

        $range = TimeRange::custom($endOfMonth, $startOfNextMonth);

        expect($range->getDurationInDays())->toBe(2)
            ->and($range->contains(Carbon::parse('2025-01-31')))->toBeTrue()
            ->and($range->contains(Carbon::parse('2025-02-01')))->toBeTrue();
    });

    it('handles year boundary calculations', function () {
        $endOfYear = Carbon::parse('2024-12-31');
        $startOfNextYear = Carbon::parse('2025-01-01');

        $range = TimeRange::custom($endOfYear, $startOfNextYear);

        expect($range->getDurationInDays())->toBe(2)
            ->and($endOfYear->year)->toBe(2024)
            ->and($startOfNextYear->year)->toBe(2025);
    });

    it('calculates cache keys consistently', function () {
        $start = Carbon::parse('2025-01-15');
        $end = Carbon::parse('2025-01-20');

        $range1 = TimeRange::custom($start, $end);
        $range2 = TimeRange::custom($start->copy(), $end->copy());

        expect($range1->getCacheKey())->toBe($range2->getCacheKey())
            ->and($range1->getCacheKey())->toBe('range_2025-01-15_2025-01-20');
    });

    it('handles string representation correctly', function () {
        $start = Carbon::parse('2025-01-15');
        $end = Carbon::parse('2025-01-20');
        $range = TimeRange::custom($start, $end, 'Test Range');

        $expected = 'Test Range (Jan 15, 2025 to Jan 20, 2025)';

        expect((string) $range)->toBe($expected)
            ->and($range->__toString())->toBe($expected);
    });

    it('creates campaign time remaining from object', function () {
        $campaign = (object) [
            'end_date' => Carbon::parse('2025-01-20 12:00:00'),
        ];

        $now = Carbon::parse('2025-01-15 12:00:00');
        $timeRemaining = TimeRemaining::fromCampaign($campaign, $now);

        expect($timeRemaining->getDaysRemaining())->toBe(5)
            ->and($timeRemaining->isExpired())->toBeFalse();
    });

    it('handles campaign with null end date', function () {
        $campaign = (object) [
            'end_date' => null,
        ];

        $now = Carbon::parse('2025-01-15 12:00:00');
        $timeRemaining = TimeRemaining::fromCampaign($campaign, $now);

        expect($timeRemaining->getDaysRemaining())->toBeGreaterThan(36000) // Far future
            ->and($timeRemaining->isExpired())->toBeFalse();
    });
});

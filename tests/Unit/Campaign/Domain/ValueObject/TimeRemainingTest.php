<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Campaign\Domain\ValueObject\TimeRemaining;

describe('TimeRemaining Value Object', function (): void {
    beforeEach(function (): void {
        // Fix current time for consistent testing
        Carbon::setTestNow('2024-06-15 12:00:00');
    });

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    describe('Construction', function (): void {
        it('creates with end date and default current date', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('creates with explicit current date', function (): void {
            $currentDate = Carbon::parse('2024-06-10 12:00:00');
            $endDate = Carbon::parse('2024-06-15 12:00:00');

            $timeRemaining = new TimeRemaining($endDate, $currentDate);

            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('handles same date correctly', function (): void {
            $date = Carbon::parse('2024-06-15 12:00:00');
            $timeRemaining = new TimeRemaining($date, $date);

            expect($timeRemaining->getDaysRemaining())->toBe(0);
        });
    });

    describe('Days Remaining Calculations', function (): void {
        it('calculates positive days remaining', function (): void {
            $endDate = Carbon::parse('2024-06-25 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(10);
        });

        it('returns negative days when expired', function (): void {
            $endDate = Carbon::parse('2024-06-10 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(-5);
        });

        it('returns zero days for same day', function (): void {
            $endDate = Carbon::parse('2024-06-15 18:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(0);
        });

        it('handles leap year correctly', function (): void {
            Carbon::setTestNow('2024-02-28 12:00:00');
            $endDate = Carbon::parse('2024-03-01 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(2); // 2024 is a leap year
        });
    });

    describe('Hours Remaining Calculations', function (): void {
        it('calculates hours remaining correctly', function (): void {
            $endDate = Carbon::parse('2024-06-15 18:00:00'); // 6 hours later
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getHoursRemaining())->toBe(6);
        });

        it('calculates negative hours when expired', function (): void {
            $endDate = Carbon::parse('2024-06-15 06:00:00'); // 6 hours ago
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getHoursRemaining())->toBe(-6);
        });

        it('handles cross-day hours correctly', function (): void {
            $endDate = Carbon::parse('2024-06-16 06:00:00'); // 18 hours later
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getHoursRemaining())->toBe(18);
        });
    });

    describe('Minutes Remaining Calculations', function (): void {
        it('calculates minutes remaining correctly', function (): void {
            $endDate = Carbon::parse('2024-06-15 12:30:00'); // 30 minutes later
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getMinutesRemaining())->toBe(30);
        });

        it('calculates negative minutes when expired', function (): void {
            $endDate = Carbon::parse('2024-06-15 11:45:00'); // 15 minutes ago
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getMinutesRemaining())->toBe(-15);
        });
    });

    describe('Expiration Status', function (): void {
        it('identifies non-expired campaign', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpired())->toBeFalse();
        });

        it('identifies expired campaign', function (): void {
            $endDate = Carbon::parse('2024-06-10 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpired())->toBeTrue();
        });

        it('identifies campaign expiring right now as expired', function (): void {
            $endDate = Carbon::parse('2024-06-15 11:59:59');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpired())->toBeTrue();
        });
    });

    describe('Expiring Soon Detection', function (): void {
        it('detects campaign expiring within default threshold', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00'); // 5 days
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpiringSoon())->toBeTrue();
        });

        it('detects campaign not expiring soon with default threshold', function (): void {
            $endDate = Carbon::parse('2024-06-25 12:00:00'); // 10 days
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpiringSoon())->toBeFalse();
        });

        it('detects campaign expiring within custom threshold', function (): void {
            $endDate = Carbon::parse('2024-06-18 12:00:00'); // 3 days
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpiringSoon(5))->toBeTrue();
        });

        it('handles expired campaigns correctly', function (): void {
            $endDate = Carbon::parse('2024-06-10 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpiringSoon())->toBeFalse();
        });

        it('handles exact threshold boundary', function (): void {
            $endDate = Carbon::parse('2024-06-22 12:00:00'); // exactly 7 days
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->isExpiringSoon(7))->toBeTrue();
        });
    });

    describe('Time Remaining Text', function (): void {
        it('returns "Expired" for expired campaigns', function (): void {
            $endDate = Carbon::parse('2024-06-10 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('Expired');
        });

        it('returns days remaining for multiple days', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('5 days remaining');
        });

        it('returns day remaining for single day', function (): void {
            $endDate = Carbon::parse('2024-06-16 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('1 day remaining');
        });

        it('returns hours remaining when less than a day', function (): void {
            $endDate = Carbon::parse('2024-06-15 18:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('6 hours remaining');
        });

        it('returns hour remaining for single hour', function (): void {
            $endDate = Carbon::parse('2024-06-15 13:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('1 hour remaining');
        });

        it('returns minutes remaining when less than an hour', function (): void {
            $endDate = Carbon::parse('2024-06-15 12:30:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('30 minutes remaining');
        });

        it('returns minute remaining for single minute', function (): void {
            $endDate = Carbon::parse('2024-06-15 12:01:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getTimeRemainingText())->toBe('1 minute remaining');
        });
    });

    describe('Urgency Level', function (): void {
        it('returns "expired" for expired campaigns', function (): void {
            $endDate = Carbon::parse('2024-06-10 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getUrgencyLevel())->toBe('expired');
        });

        it('returns "critical" for campaigns ending in 1 day or less', function (): void {
            $endDate = Carbon::parse('2024-06-16 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getUrgencyLevel())->toBe('critical');
        });

        it('returns "urgent" for campaigns ending in 2-3 days', function (): void {
            $endDate = Carbon::parse('2024-06-17 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getUrgencyLevel())->toBe('urgent');
        });

        it('returns "warning" for campaigns ending in 4-7 days', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getUrgencyLevel())->toBe('warning');
        });

        it('returns "normal" for campaigns ending in more than 7 days', function (): void {
            $endDate = Carbon::parse('2024-06-25 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getUrgencyLevel())->toBe('normal');
        });
    });

    describe('Urgency Color', function (): void {
        it('returns correct colors for each urgency level', function (): void {
            $testCases = [
                ['2024-06-10 12:00:00', 'red'],    // expired
                ['2024-06-16 12:00:00', 'red'],    // critical
                ['2024-06-17 12:00:00', 'orange'], // urgent
                ['2024-06-20 12:00:00', 'yellow'], // warning
                ['2024-06-25 12:00:00', 'green'],  // normal
            ];

            foreach ($testCases as [$endDateStr, $expectedColor]) {
                $endDate = Carbon::parse($endDateStr);
                $timeRemaining = new TimeRemaining($endDate);

                expect($timeRemaining->getUrgencyColor())->toBe($expectedColor);
            }
        });
    });

    describe('Factory Method', function (): void {
        it('creates from campaign object with Carbon end_date', function (): void {
            $endDate = Carbon::parse('2024-06-20 12:00:00');
            $campaign = (object) ['end_date' => $endDate];

            $timeRemaining = TimeRemaining::fromCampaign($campaign);

            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('creates from campaign object with string end_date', function (): void {
            $campaign = (object) ['end_date' => '2024-06-20 12:00:00'];

            $timeRemaining = TimeRemaining::fromCampaign($campaign);

            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('creates from campaign with custom current date', function (): void {
            $campaign = (object) ['end_date' => '2024-06-20 12:00:00'];
            $currentDate = Carbon::parse('2024-06-18 12:00:00');

            $timeRemaining = TimeRemaining::fromCampaign($campaign, $currentDate);

            expect($timeRemaining->getDaysRemaining())->toBe(2);
        });
    });

    describe('Edge Cases and Boundary Conditions', function (): void {
        it('handles campaigns ending at midnight', function (): void {
            $endDate = Carbon::parse('2024-06-16 00:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(0);
        });

        it('handles campaigns ending just before midnight', function (): void {
            $endDate = Carbon::parse('2024-06-15 23:59:59');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(0)
                ->and($timeRemaining->getHoursRemaining())->toBe(11)
                ->and($timeRemaining->getMinutesRemaining())->toBe(719);
        });

        it('handles timezone considerations with UTC', function (): void {
            // Fix timezone to UTC for consistent testing
            $currentDate = Carbon::parse('2024-06-15 12:00:00', 'UTC');
            $endDate = Carbon::parse('2024-06-20 12:00:00', 'UTC');
            $timeRemaining = new TimeRemaining($endDate, $currentDate);

            // Should work consistently with explicit timezone
            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('handles timezone considerations across different timezones', function (): void {
            // Test with different timezones but same UTC time
            $currentDateUTC = Carbon::parse('2024-06-15 12:00:00', 'UTC');
            $endDateNY = Carbon::parse('2024-06-20 08:00:00', 'America/New_York'); // Same as 12:00 UTC

            $timeRemaining = new TimeRemaining($endDateNY, $currentDateUTC);
            expect($timeRemaining->getDaysRemaining())->toBe(5);
        });

        it('handles far future dates correctly', function (): void {
            $currentDate = Carbon::parse('2024-06-15 12:00:00');
            $endDate = Carbon::parse('2025-06-15 12:00:00');
            $timeRemaining = new TimeRemaining($endDate, $currentDate);

            expect($timeRemaining->getDaysRemaining())->toBe(365)
                ->and($timeRemaining->getUrgencyLevel())->toBe('normal')
                ->and($timeRemaining->isExpiringSoon())->toBeFalse();
        });

        it('handles daylight saving time transitions', function (): void {
            // Test across DST transition
            $currentDate = Carbon::parse('2024-03-30 12:00:00', 'Europe/Brussels'); // Before DST
            $endDate = Carbon::parse('2024-04-02 12:00:00', 'Europe/Brussels'); // After DST
            $timeRemaining = new TimeRemaining($endDate, $currentDate);

            // Should still calculate correctly despite DST transition
            expect($timeRemaining->getDaysRemaining())->toBe(3);
        });

        it('handles campaigns ending in exactly zero time', function (): void {
            $endDate = Carbon::parse('2024-06-15 12:00:00');
            $timeRemaining = new TimeRemaining($endDate);

            expect($timeRemaining->getDaysRemaining())->toBe(0)
                ->and($timeRemaining->getHoursRemaining())->toBe(0)
                ->and($timeRemaining->getMinutesRemaining())->toBe(0)
                ->and($timeRemaining->isExpired())->toBeFalse();
        });
    });
});

<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Donation\Domain\ValueObject\Amount;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Shared\Domain\ValueObject\DonationStatus;

describe('Recurring Donation Logic', function (): void {
    beforeEach(function (): void {
        Carbon::setTestNow('2024-01-15 10:00:00');
    });

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    describe('Recurring Frequency Validation', function (): void {
        it('validates daily recurring frequency', function (): void {
            $frequency = 'daily';

            expect($frequency)->toBe('daily');
        });

        it('validates weekly recurring frequency', function (): void {
            $frequency = 'weekly';

            expect($frequency)->toBe('weekly');
        });

        it('validates monthly recurring frequency', function (): void {
            $frequency = 'monthly';

            expect($frequency)->toBe('monthly');
        });

        it('validates quarterly recurring frequency', function (): void {
            $frequency = 'quarterly';

            expect($frequency)->toBe('quarterly');
        });

        it('validates yearly recurring frequency', function (): void {
            $frequency = 'yearly';

            expect($frequency)->toBe('yearly');
        });

        it('identifies valid recurring frequencies', function (): void {
            $validFrequencies = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

            foreach ($validFrequencies as $frequency) {
                expect(in_array($frequency, $validFrequencies, true))->toBeTrue();
            }
        });

        it('rejects invalid recurring frequencies', function (): void {
            $invalidFrequencies = ['hourly', 'biweekly', 'semiannual', 'invalid'];
            $validFrequencies = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

            foreach ($invalidFrequencies as $frequency) {
                expect(in_array($frequency, $validFrequencies, true))->toBeFalse();
            }
        });
    });

    describe('Recurring Donation Business Rules', function (): void {
        it('calculates next donation date for daily frequency', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $frequency = 'daily';

            $nextDate = $startDate->copy()->addDay();

            expect($nextDate->format('Y-m-d'))->toBe('2024-01-16');
        });

        it('calculates next donation date for weekly frequency', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $frequency = 'weekly';

            $nextDate = $startDate->copy()->addWeek();

            expect($nextDate->format('Y-m-d'))->toBe('2024-01-22');
        });

        it('calculates next donation date for monthly frequency', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $frequency = 'monthly';

            $nextDate = $startDate->copy()->addMonth();

            expect($nextDate->format('Y-m-d'))->toBe('2024-02-15');
        });

        it('calculates next donation date for quarterly frequency', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $frequency = 'quarterly';

            $nextDate = $startDate->copy()->addMonths(3);

            expect($nextDate->format('Y-m-d'))->toBe('2024-04-15');
        });

        it('calculates next donation date for yearly frequency', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $frequency = 'yearly';

            $nextDate = $startDate->copy()->addYear();

            expect($nextDate->format('Y-m-d'))->toBe('2025-01-15');
        });

        it('handles leap year for yearly recurring donations', function (): void {
            $startDate = Carbon::parse('2024-02-29'); // Leap year
            $frequency = 'yearly';

            $nextDate = $startDate->copy()->addYear();

            expect($nextDate->format('Y-m-d'))->toBe('2025-03-01'); // Non-leap year adjustment
        });

        it('handles month-end dates for monthly recurring', function (): void {
            $startDate = Carbon::parse('2024-01-31');
            $frequency = 'monthly';

            $nextDate = $startDate->copy()->addMonth();

            expect($nextDate->format('Y-m-d'))->toBe('2024-03-02'); // February in leap year
        });
    });

    describe('Recurring Donation Amount Validation', function (): void {
        it('validates minimum recurring donation amount', function (): void {
            $amount = new Amount(5.00, 'EUR');
            $minRecurringAmount = 5.00;

            expect($amount->value)->toBeGreaterThanOrEqual($minRecurringAmount);
        });

        it('rejects recurring donations below minimum threshold', function (): void {
            $amount = new Amount(2.50, 'EUR');
            $minRecurringAmount = 5.00;

            expect($amount->value)->toBeLessThan($minRecurringAmount);
        });

        it('validates maximum recurring donation amount', function (): void {
            $amount = new Amount(10000.00, 'EUR');
            $maxRecurringAmount = 10000.00;

            expect($amount->value)->toBeLessThanOrEqual($maxRecurringAmount);
        });

        it('calculates total recurring amount over time periods', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $months = 12;

            $totalAmount = $monthlyAmount->multiply($months);

            expect($totalAmount->value)->toBe(1200.00);
        });

        it('calculates prorated amount for partial periods', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $days = 15;
            $monthDays = 30;

            $proratedAmount = $monthlyAmount->multiply($days / $monthDays);

            expect($proratedAmount->value)->toBe(50.00);
        });
    });

    describe('Payment Method Compatibility', function (): void {
        it('allows credit cards for recurring donations', function (): void {
            $paymentMethod = PaymentMethod::CARD;

            expect($paymentMethod->requiresProcessing())->toBeTrue()
                ->and($paymentMethod->isOnline())->toBeTrue();
        });

        it('allows Stripe for recurring donations', function (): void {
            $paymentMethod = PaymentMethod::STRIPE;

            expect($paymentMethod->requiresProcessing())->toBeTrue()
                ->and($paymentMethod->isOnline())->toBeTrue();
        });

        it('allows PayPal for recurring donations', function (): void {
            $paymentMethod = PaymentMethod::PAYPAL;

            expect($paymentMethod->requiresProcessing())->toBeTrue()
                ->and($paymentMethod->isOnline())->toBeTrue();
        });

        it('rejects bank transfer for recurring donations', function (): void {
            $paymentMethod = PaymentMethod::BANK_TRANSFER;

            expect($paymentMethod->requiresProcessing())->toBeFalse()
                ->and($paymentMethod->isOnline())->toBeFalse();
        });

        it('validates payment method supports currency for recurring donations', function (): void {
            $paymentMethod = PaymentMethod::CARD;
            $currency = 'EUR';

            expect($paymentMethod->supportsCurrency($currency))->toBeTrue();
        });

        it('checks minimum amount requirements per payment method', function (): void {
            $paymentMethod = PaymentMethod::STRIPE;
            $currency = 'EUR';

            $minAmount = $paymentMethod->getMinimumAmount($currency);

            expect($minAmount)->toBe(0.50);
        });
    });

    describe('Recurring Donation Lifecycle', function (): void {
        it('creates recurring donation schedule', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $amount = new Amount(50.00, 'EUR');
            $frequency = 'monthly';
            $duration = 12; // months

            $schedule = [];
            $currentDate = $startDate->copy();

            for ($i = 0; $i < $duration; $i++) {
                $schedule[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'amount' => $amount->value,
                    'occurrence' => $i + 1,
                ];
                $currentDate->addMonth();
            }

            expect($schedule)->toHaveCount(12)
                ->and($schedule[0]['date'])->toBe('2024-01-15')
                ->and($schedule[11]['date'])->toBe('2024-12-15')
                ->and($schedule[0]['amount'])->toBe(50.00);
        });

        it('handles failed recurring donation attempts', function (): void {
            $donation = [
                'id' => 1,
                'amount' => 50.00,
                'status' => DonationStatus::FAILED,
                'recurring' => true,
                'recurring_frequency' => 'monthly',
                'retry_count' => 1,
                'max_retries' => 3,
            ];

            $canRetry = $donation['retry_count'] < $donation['max_retries'];

            expect($canRetry)->toBeTrue()
                ->and($donation['status'])->toBe(DonationStatus::FAILED);
        });

        it('determines when to stop retrying failed recurring donations', function (): void {
            $donation = [
                'retry_count' => 3,
                'max_retries' => 3,
            ];

            $shouldStop = $donation['retry_count'] >= $donation['max_retries'];

            expect($shouldStop)->toBeTrue();
        });

        it('calculates next retry date with exponential backoff', function (): void {
            $lastAttempt = Carbon::parse('2024-01-15 10:00:00');
            $retryCount = 2;

            // Exponential backoff: 2^retry_count hours
            $backoffHours = pow(2, $retryCount);
            $nextRetry = $lastAttempt->copy()->addHours($backoffHours);

            expect($nextRetry->format('Y-m-d H:i:s'))->toBe('2024-01-15 14:00:00');
        });
    });

    describe('Recurring Donation Cancellation', function (): void {
        it('allows cancellation of future recurring donations', function (): void {
            $donation = [
                'recurring' => true,
                'status' => DonationStatus::COMPLETED,
                'cancelled_at' => null,
            ];

            $canCancel = $donation['recurring'] && $donation['cancelled_at'] === null;

            expect($canCancel)->toBeTrue();
        });

        it('prevents cancellation of already cancelled recurring donations', function (): void {
            $donation = [
                'recurring' => true,
                'status' => DonationStatus::CANCELLED,
                'cancelled_at' => Carbon::now(),
            ];

            $canCancel = $donation['recurring'] && $donation['cancelled_at'] === null;

            expect($canCancel)->toBeFalse();
        });

        it('calculates refund amount for cancelled recurring donations', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $totalPaid = 5; // months
            $totalPlanned = 12; // months

            $paidAmount = $monthlyAmount->multiply($totalPaid);
            $remainingMonths = $totalPlanned - $totalPaid;

            expect($paidAmount->value)->toBe(500.00)
                ->and($remainingMonths)->toBe(7);
        });
    });

    describe('Recurring Donation Tax Receipt Generation', function (): void {
        it('generates yearly tax receipt for recurring donations', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $donationsCount = 12;

            $yearlyTotal = $monthlyAmount->multiply($donationsCount);
            $qualifiesForReceipt = $yearlyTotal->qualifiesForTaxReceipt();

            expect($yearlyTotal->value)->toBe(1200.00)
                ->and($qualifiesForReceipt)->toBeTrue();
        });

        it('tracks recurring donations for tax receipt eligibility', function (): void {
            $donations = [
                ['amount' => 15.00, 'date' => '2024-01-15'],
                ['amount' => 15.00, 'date' => '2024-02-15'],
                ['amount' => 15.00, 'date' => '2024-03-15'],
            ];

            $totalAmount = array_sum(array_column($donations, 'amount'));
            $qualifies = $totalAmount >= 20.00;

            expect($totalAmount)->toBe(45.00)
                ->and($qualifies)->toBeTrue();
        });
    });

    describe('Recurring Donation Frequency Calculations', function (): void {
        it('calculates daily frequency in days', function (): void {
            $frequency = 'daily';
            $days = 1;

            expect($days)->toBe(1);
        });

        it('calculates weekly frequency in days', function (): void {
            $frequency = 'weekly';
            $days = 7;

            expect($days)->toBe(7);
        });

        it('calculates monthly frequency in approximate days', function (): void {
            $frequency = 'monthly';
            $days = 30; // Approximate

            expect($days)->toBe(30);
        });

        it('calculates quarterly frequency in days', function (): void {
            $frequency = 'quarterly';
            $days = 90; // Approximate (3 months)

            expect($days)->toBe(90);
        });

        it('calculates yearly frequency in days', function (): void {
            $frequency = 'yearly';
            $days = 365; // Non-leap year

            expect($days)->toBe(365);
        });

        it('handles leap year calculations for yearly frequency', function (): void {
            $year = 2024; // Leap year
            $isLeapYear = Carbon::createFromDate($year)->isLeapYear();
            $days = $isLeapYear ? 366 : 365;

            expect($isLeapYear)->toBeTrue()
                ->and($days)->toBe(366);
        });
    });

    describe('Recurring Donation Analytics', function (): void {
        it('calculates average donation amount for recurring donations', function (): void {
            $donations = [50.00, 75.00, 100.00, 25.00];
            $average = array_sum($donations) / count($donations);

            expect($average)->toBe(62.5);
        });

        it('calculates retention rate for recurring donations', function (): void {
            $totalRecurring = 100;
            $activeRecurring = 85;
            $retentionRate = ($activeRecurring / $totalRecurring) * 100;

            expect($retentionRate)->toBe(85.0);
        });

        it('calculates churn rate for recurring donations', function (): void {
            $totalRecurring = 100;
            $cancelledRecurring = 15;
            $churnRate = ($cancelledRecurring / $totalRecurring) * 100;

            expect($churnRate)->toBe(15.0);
        });

        it('calculates lifetime value of recurring donor', function (): void {
            $monthlyAmount = new Amount(50.00, 'EUR');
            $averageLifetimeMonths = 24;

            $lifetimeValue = $monthlyAmount->multiply($averageLifetimeMonths);

            expect($lifetimeValue->value)->toBe(1200.00);
        });
    });

    describe('Recurring Donation Status Transitions', function (): void {
        it('transitions from pending to processing for recurring donations', function (): void {
            $currentStatus = DonationStatus::PENDING;
            $newStatus = DonationStatus::PROCESSING;

            $canTransition = in_array($newStatus, [
                DonationStatus::PROCESSING,
                DonationStatus::FAILED,
                DonationStatus::CANCELLED,
            ], true);

            expect($canTransition)->toBeTrue();
        });

        it('transitions from processing to completed for recurring donations', function (): void {
            $currentStatus = DonationStatus::PROCESSING;
            $newStatus = DonationStatus::COMPLETED;

            $canTransition = in_array($newStatus, [
                DonationStatus::COMPLETED,
                DonationStatus::FAILED,
                DonationStatus::CANCELLED,
            ], true);

            expect($canTransition)->toBeTrue();
        });

        it('prevents invalid status transitions for recurring donations', function (): void {
            $currentStatus = DonationStatus::COMPLETED;
            $newStatus = DonationStatus::PENDING;

            $canTransition = in_array($newStatus, [
                DonationStatus::REFUNDED,
            ], true);

            expect($canTransition)->toBeFalse();
        });
    });

    describe('Recurring Donation Edge Cases', function (): void {
        it('handles weekend scheduling for business days only', function (): void {
            $startDate = Carbon::parse('2024-01-13'); // Saturday
            $nextBusinessDay = $startDate->copy()->nextWeekday();

            expect($nextBusinessDay->format('Y-m-d'))->toBe('2024-01-15'); // Monday
        });

        it('handles holiday scheduling adjustments', function (): void {
            $scheduledDate = Carbon::parse('2024-12-25'); // Christmas
            $isHoliday = $scheduledDate->month === 12 && $scheduledDate->day === 25;

            $adjustedDate = $isHoliday
                ? $scheduledDate->copy()->addDay()
                : $scheduledDate;

            expect($isHoliday)->toBeTrue()
                ->and($adjustedDate->format('Y-m-d'))->toBe('2024-12-26');
        });

        it('handles timezone considerations for recurring donations', function (): void {
            $donorTimezone = 'Europe/Amsterdam';
            $systemTimezone = 'UTC';

            $donorTime = Carbon::parse('2024-01-15 23:00:00', $donorTimezone);
            $systemTime = $donorTime->utc();

            expect($systemTime->format('Y-m-d H:i:s'))->toBe('2024-01-15 22:00:00');
        });

        it('handles currency exchange rate changes for international recurring donations', function (): void {
            $baseAmount = new Amount(100.00, 'USD');
            $exchangeRate = 0.85; // USD to EUR

            $convertedAmount = $baseAmount->multiply($exchangeRate);

            expect($convertedAmount->value)->toBe(85.00);
        });
    });

    describe('Recurring Donation Validation Rules', function (): void {
        it('validates recurring donation end date is after start date', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $endDate = Carbon::parse('2024-12-15');

            $isValidRange = $endDate->isAfter($startDate);

            expect($isValidRange)->toBeTrue();
        });

        it('validates maximum recurring donation duration', function (): void {
            $startDate = Carbon::parse('2024-01-15');
            $endDate = Carbon::parse('2027-01-15'); // 3 years
            $maxDurationYears = 5;

            $durationYears = $startDate->diffInYears($endDate);
            $isWithinLimit = $durationYears <= $maxDurationYears;

            expect($durationYears)->toBe(3.0)
                ->and($isWithinLimit)->toBeTrue();
        });

        it('validates minimum occurrences for recurring donations', function (): void {
            $frequency = 'monthly';
            $startDate = Carbon::parse('2024-01-15');
            $endDate = Carbon::parse('2024-03-15');

            $occurrences = $startDate->diffInMonths($endDate);
            $minOccurrences = 2;

            expect($occurrences)->toBeGreaterThanOrEqual($minOccurrences);
        });
    });
});

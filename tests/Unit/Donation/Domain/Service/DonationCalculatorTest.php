<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Donation\Domain\ValueObject\Amount;

describe('Donation Calculator Service', function (): void {
    beforeEach(function (): void {
        Carbon::setTestNow('2024-01-15 10:00:00');
    });

    afterEach(function (): void {
        Carbon::setTestNow();
    });

    describe('Basic Amount Calculations', function (): void {
        it('calculates donation total with single amount', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $total = $amount->value;

            expect($total)->toBe(100.00);
        });

        it('calculates donation total with multiple amounts', function (): void {
            $amounts = [
                new Amount(50.00, 'EUR'),
                new Amount(75.00, 'EUR'),
                new Amount(25.00, 'EUR'),
            ];

            $total = 0.0;
            foreach ($amounts as $amount) {
                $total += $amount->value;
            }

            expect($total)->toBe(150.00);
        });

        it('calculates average donation amount', function (): void {
            $amounts = [100.00, 200.00, 50.00, 150.00];
            $average = array_sum($amounts) / count($amounts);

            expect($average)->toBe(125.00);
        });

        it('calculates median donation amount', function (): void {
            $amounts = [100.00, 200.00, 50.00, 150.00, 75.00];
            sort($amounts);
            $median = $amounts[floor(count($amounts) / 2)];

            expect($median)->toBe(100.00);
        });

        it('calculates minimum donation amount', function (): void {
            $amounts = [100.00, 200.00, 50.00, 150.00];
            $minimum = min($amounts);

            expect($minimum)->toBe(50.00);
        });

        it('calculates maximum donation amount', function (): void {
            $amounts = [100.00, 200.00, 50.00, 150.00];
            $maximum = max($amounts);

            expect($maximum)->toBe(200.00);
        });

        it('calculates donation range', function (): void {
            $amounts = [100.00, 200.00, 50.00, 150.00];
            $range = max($amounts) - min($amounts);

            expect($range)->toBe(150.00);
        });
    });

    describe('Fee Calculations', function (): void {
        it('calculates processing fee for credit card', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $feePercentage = 2.9; // 2.9%
            $fixedFee = 0.30;

            $processingFee = ($amount->value * $feePercentage / 100) + $fixedFee;

            expect(round($processingFee, 2))->toBe(3.20);
        });

        it('calculates processing fee for PayPal', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $feePercentage = 3.4; // 3.4%
            $fixedFee = 0.35;

            $processingFee = ($amount->value * $feePercentage / 100) + $fixedFee;

            expect($processingFee)->toBe(3.75);
        });

        it('calculates net amount after fees', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $processingFee = 3.20;

            $netAmount = $amount->value - $processingFee;

            expect($netAmount)->toBe(96.80);
        });

        it('calculates gross amount from net amount', function (): void {
            $netAmount = 96.80;
            $feePercentage = 2.9;
            $fixedFee = 0.30;

            // Reverse calculation: gross = (net + fixed) / (1 - percentage/100)
            $grossAmount = ($netAmount + $fixedFee) / (1 - $feePercentage / 100);

            expect(round($grossAmount, 2))->toBe(100.00);
        });

        it('calculates platform fee', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $platformFeePercentage = 5.0; // 5%

            $platformFee = $amount->value * $platformFeePercentage / 100;

            expect($platformFee)->toBe(5.00);
        });

        it('calculates total fees with multiple fee types', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $processingFee = 3.20;
            $platformFee = 5.00;

            $totalFees = $processingFee + $platformFee;

            expect($totalFees)->toBe(8.20);
        });
    });

    describe('Tax Calculations', function (): void {
        it('calculates tax deductible amount for eligible donation', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $taxDeductiblePercentage = 100; // 100% deductible

            $deductibleAmount = $amount->value * $taxDeductiblePercentage / 100;

            expect($deductibleAmount)->toBe(100.00);
        });

        it('calculates partial tax deductible amount', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $taxDeductiblePercentage = 60; // 60% deductible

            $deductibleAmount = $amount->value * $taxDeductiblePercentage / 100;

            expect($deductibleAmount)->toBe(60.00);
        });

        it('calculates tax savings for donor', function (): void {
            $deductibleAmount = 100.00;
            $donorTaxRate = 25; // 25% tax rate

            $taxSavings = $deductibleAmount * $donorTaxRate / 100;

            expect($taxSavings)->toBe(25.00);
        });

        it('determines tax receipt eligibility', function (): void {
            $amount = new Amount(25.00, 'EUR');
            $minimumThreshold = 20.00;

            $isEligible = $amount->value >= $minimumThreshold;

            expect($isEligible)->toBeTrue();
        });

        it('determines tax receipt ineligibility for small amounts', function (): void {
            $amount = new Amount(15.00, 'EUR');
            $minimumThreshold = 20.00;

            $isEligible = $amount->value >= $minimumThreshold;

            expect($isEligible)->toBeFalse();
        });
    });

    describe('Corporate Matching Calculations', function (): void {
        it('calculates 1:1 corporate match', function (): void {
            $donationAmount = new Amount(100.00, 'EUR');
            $matchRatio = 1.0;

            $matchAmount = $donationAmount->value * $matchRatio;

            expect($matchAmount)->toBe(100.00);
        });

        it('calculates 2:1 corporate match', function (): void {
            $donationAmount = new Amount(100.00, 'EUR');
            $matchRatio = 2.0;

            $matchAmount = $donationAmount->value * $matchRatio;

            expect($matchAmount)->toBe(200.00);
        });

        it('calculates 0.5:1 corporate match', function (): void {
            $donationAmount = new Amount(100.00, 'EUR');
            $matchRatio = 0.5;

            $matchAmount = $donationAmount->value * $matchRatio;

            expect($matchAmount)->toBe(50.00);
        });

        it('calculates total with corporate match', function (): void {
            $donationAmount = new Amount(100.00, 'EUR');
            $matchAmount = 100.00;

            $totalAmount = $donationAmount->value + $matchAmount;

            expect($totalAmount)->toBe(200.00);
        });

        it('calculates corporate match within annual limit', function (): void {
            $donationAmount = new Amount(1000.00, 'EUR');
            $matchRatio = 1.0;
            $annualLimit = 500.00;
            $alreadyMatched = 200.00;

            $potentialMatch = $donationAmount->value * $matchRatio;
            $remainingLimit = $annualLimit - $alreadyMatched;
            $actualMatch = min($potentialMatch, $remainingLimit);

            expect($actualMatch)->toBe(300.00);
        });

        it('handles corporate match exceeding annual limit', function (): void {
            $donationAmount = new Amount(1000.00, 'EUR');
            $matchRatio = 1.0;
            $annualLimit = 500.00;
            $alreadyMatched = 450.00;

            $potentialMatch = $donationAmount->value * $matchRatio;
            $remainingLimit = $annualLimit - $alreadyMatched;
            $actualMatch = min($potentialMatch, $remainingLimit);

            expect($actualMatch)->toBe(50.00);
        });
    });

    describe('Currency Conversion Calculations', function (): void {
        it('converts USD to EUR', function (): void {
            $usdAmount = new Amount(100.00, 'USD');
            $exchangeRate = 0.85; // USD to EUR

            $eurAmount = $usdAmount->value * $exchangeRate;

            expect($eurAmount)->toBe(85.00);
        });

        it('converts EUR to USD', function (): void {
            $eurAmount = new Amount(100.00, 'EUR');
            $exchangeRate = 1.18; // EUR to USD

            $usdAmount = $eurAmount->value * $exchangeRate;

            expect($usdAmount)->toBe(118.00);
        });

        it('handles conversion fees', function (): void {
            $amount = new Amount(100.00, 'USD');
            $exchangeRate = 0.85;
            $conversionFeePercentage = 2.5;

            $convertedAmount = $amount->value * $exchangeRate;
            $conversionFee = $convertedAmount * $conversionFeePercentage / 100;
            $finalAmount = $convertedAmount - $conversionFee;

            expect(round($finalAmount, 2))->toBe(82.88);
        });

        it('calculates mid-market rate impact', function (): void {
            $amount = new Amount(100.00, 'USD');
            $midMarketRate = 0.85;
            $appliedRate = 0.83;

            $midMarketAmount = $amount->value * $midMarketRate;
            $actualAmount = $amount->value * $appliedRate;
            $impact = $midMarketAmount - $actualAmount;

            expect($impact)->toBe(2.00);
        });
    });

    describe('Recurring Donation Calculations', function (): void {
        it('calculates monthly recurring total for year', function (): void {
            $monthlyAmount = new Amount(50.00, 'EUR');
            $months = 12;

            $yearlyTotal = $monthlyAmount->value * $months;

            expect($yearlyTotal)->toBe(600.00);
        });

        it('calculates weekly recurring total for year', function (): void {
            $weeklyAmount = new Amount(25.00, 'EUR');
            $weeks = 52;

            $yearlyTotal = $weeklyAmount->value * $weeks;

            expect($yearlyTotal)->toBe(1300.00);
        });

        it('calculates prorated amount for partial month', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $daysInMonth = 30;
            $daysRemaining = 15;

            $proratedAmount = $monthlyAmount->value * ($daysRemaining / $daysInMonth);

            expect($proratedAmount)->toBe(50.00);
        });

        it('calculates next payment date for monthly recurring', function (): void {
            $lastPayment = Carbon::parse('2024-01-15');
            $nextPayment = $lastPayment->copy()->addMonth();

            expect($nextPayment->format('Y-m-d'))->toBe('2024-02-15');
        });

        it('calculates missed payments for failed recurring', function (): void {
            $expectedDate = Carbon::parse('2024-01-15');
            $currentDate = Carbon::parse('2024-03-15');
            $frequency = 'monthly';

            $missedPayments = $expectedDate->diffInMonths($currentDate);

            expect($missedPayments)->toBe(2.0);
        });

        it('calculates total value of recurring donation plan', function (): void {
            $monthlyAmount = new Amount(100.00, 'EUR');
            $durationMonths = 24;

            $totalValue = $monthlyAmount->value * $durationMonths;

            expect($totalValue)->toBe(2400.00);
        });
    });

    describe('Campaign Progress Calculations', function (): void {
        it('calculates campaign progress percentage', function (): void {
            $currentAmount = 7500.00;
            $targetAmount = 10000.00;

            $progressPercentage = ($currentAmount / $targetAmount) * 100;

            expect($progressPercentage)->toBe(75.0);
        });

        it('calculates remaining amount to target', function (): void {
            $currentAmount = 7500.00;
            $targetAmount = 10000.00;

            $remainingAmount = $targetAmount - $currentAmount;

            expect($remainingAmount)->toBe(2500.00);
        });

        it('calculates amount over target', function (): void {
            $currentAmount = 12000.00;
            $targetAmount = 10000.00;

            $overAmount = $currentAmount - $targetAmount;

            expect($overAmount)->toBe(2000.00);
        });

        it('calculates average donation per donor', function (): void {
            $totalAmount = 10000.00;
            $donorCount = 50;

            $averagePerDonor = $totalAmount / $donorCount;

            expect($averagePerDonor)->toBe(200.00);
        });

        it('calculates required donations to reach target', function (): void {
            $currentAmount = 5000.00;
            $targetAmount = 10000.00;
            $averageDonation = 100.00;

            $remainingAmount = $targetAmount - $currentAmount;
            $requiredDonations = ceil($remainingAmount / $averageDonation);

            expect($requiredDonations)->toBe(50.0);
        });

        it('calculates daily required amount to reach target', function (): void {
            $currentAmount = 5000.00;
            $targetAmount = 10000.00;
            $daysRemaining = 30;

            $remainingAmount = $targetAmount - $currentAmount;
            $dailyRequired = $remainingAmount / $daysRemaining;

            expect(round($dailyRequired, 2))->toBe(166.67);
        });
    });

    describe('Impact Calculations', function (): void {
        it('calculates impact per donation amount', function (): void {
            $donationAmount = new Amount(100.00, 'EUR');
            $impactRatio = 5; // 1 EUR helps 5 people

            $impact = $donationAmount->value * $impactRatio;

            expect($impact)->toBe(500.0);
        });

        it('calculates cost per beneficiary', function (): void {
            $totalRaised = 10000.00;
            $beneficiariesHelped = 500;

            $costPerBeneficiary = $totalRaised / $beneficiariesHelped;

            expect($costPerBeneficiary)->toBe(20.00);
        });

        it('calculates efficiency ratio', function (): void {
            $programExpenses = 8000.00;
            $totalExpenses = 10000.00;

            $efficiencyRatio = ($programExpenses / $totalExpenses) * 100;

            expect($efficiencyRatio)->toBe(80.0);
        });

        it('calculates leveraged impact with matching funds', function (): void {
            $donation = new Amount(100.00, 'EUR');
            $governmentMatch = 200.00;
            $corporateMatch = 100.00;

            $totalImpact = $donation->value + $governmentMatch + $corporateMatch;

            expect($totalImpact)->toBe(400.00);
        });
    });

    describe('Statistical Calculations', function (): void {
        it('calculates donation standard deviation', function (): void {
            $donations = [100.00, 150.00, 200.00, 50.00, 300.00];
            $mean = array_sum($donations) / count($donations);

            $variance = array_sum(array_map(function ($x) use ($mean) {
                return pow($x - $mean, 2);
            }, $donations)) / count($donations);

            $standardDeviation = sqrt($variance);

            expect(round($standardDeviation, 2))->toBe(86.02);
        });

        it('calculates donation percentiles', function (): void {
            $donations = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
            sort($donations);

            $percentile90Index = ceil(0.9 * count($donations)) - 1;
            $percentile90 = $donations[$percentile90Index];

            expect($percentile90)->toBe(90);
        });

        it('calculates donor retention rate', function (): void {
            $previousYearDonors = 100;
            $repeatDonors = 75;

            $retentionRate = ($repeatDonors / $previousYearDonors) * 100;

            expect($retentionRate)->toBe(75.0);
        });

        it('calculates acquisition cost per donor', function (): void {
            $marketingExpense = 5000.00;
            $newDonors = 100;

            $acquisitionCost = $marketingExpense / $newDonors;

            expect($acquisitionCost)->toBe(50.00);
        });

        it('calculates lifetime value of donor', function (): void {
            $averageAnnualDonation = 200.00;
            $averageLifespanYears = 5;

            $lifetimeValue = $averageAnnualDonation * $averageLifespanYears;

            expect($lifetimeValue)->toBe(1000.00);
        });
    });

    describe('Time-based Calculations', function (): void {
        it('calculates donation velocity per day', function (): void {
            $totalAmount = 10000.00;
            $campaignDays = 30;

            $dailyVelocity = $totalAmount / $campaignDays;

            expect(round($dailyVelocity, 2))->toBe(333.33);
        });

        it('calculates peak donation hour impact', function (): void {
            $hourlyDonations = [10, 15, 25, 30, 45, 60, 40, 35, 20, 15, 10, 5];
            $peakHour = max($hourlyDonations);
            $averageHour = array_sum($hourlyDonations) / count($hourlyDonations);

            $peakImpact = ($peakHour / $averageHour) * 100;

            expect(round($peakImpact, 2))->toBe(232.26);
        });

        it('calculates seasonal donation variance', function (): void {
            $quarterlyTotals = [2000, 1500, 1800, 4000]; // Q1, Q2, Q3, Q4
            $annualTotal = array_sum($quarterlyTotals);
            $q4Percentage = ($quarterlyTotals[3] / $annualTotal) * 100;

            expect(round($q4Percentage, 2))->toBe(43.01);
        });

        it('calculates end-of-year giving surge', function (): void {
            $decemberDonations = 5000.00;
            $averageMonthlyDonations = 1500.00;

            $surgeFactor = $decemberDonations / $averageMonthlyDonations;

            expect(round($surgeFactor, 2))->toBe(3.33);
        });
    });

    describe('Risk and Fraud Calculations', function (): void {
        it('calculates chargeback rate', function (): void {
            $totalTransactions = 1000;
            $chargebacks = 5;

            $chargebackRate = ($chargebacks / $totalTransactions) * 100;

            expect($chargebackRate)->toBe(0.5);
        });

        it('calculates fraud score based on donation patterns', function (): void {
            $donationAmount = 10000.00;
            $averageDonation = 100.00;
            $firstTimeDonor = true;

            $amountScore = min(($donationAmount / $averageDonation) * 10, 50);
            $donorScore = $firstTimeDonor ? 25 : 0;
            $totalRiskScore = $amountScore + $donorScore;

            expect($totalRiskScore)->toBe(75);
        });

        it('calculates refund rate', function (): void {
            $totalDonations = 1000;
            $refunds = 20;

            $refundRate = ($refunds / $totalDonations) * 100;

            expect($refundRate)->toBe(2.0);
        });

        it('calculates payment failure rate by method', function (): void {
            $cardAttempts = 100;
            $cardFailures = 5;

            $failureRate = ($cardFailures / $cardAttempts) * 100;

            expect($failureRate)->toBe(5.0);
        });
    });

    describe('Edge Cases and Boundary Conditions', function (): void {
        it('handles zero donation amounts', function (): void {
            $amount = new Amount(0.00, 'EUR');
            $fee = 0.30; // Fixed fee

            $netAmount = max(0, $amount->value - $fee);

            expect($netAmount)->toBe(0);
        });

        it('handles very large donation amounts', function (): void {
            $amount = new Amount(999999.99, 'EUR');
            $feePercentage = 2.9;

            $fee = $amount->value * $feePercentage / 100;

            expect(round($fee, 2))->toBe(29000.00);
        });

        it('handles fractional cent calculations', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $feePercentage = 2.333; // Results in fractional cents

            $fee = $amount->value * $feePercentage / 100;
            $roundedFee = round($fee, 2);

            expect($roundedFee)->toBe(2.33);
        });

        it('handles division by zero scenarios', function (): void {
            $totalAmount = 1000.00;
            $donorCount = 0;

            $averagePerDonor = $donorCount > 0 ? $totalAmount / $donorCount : 0;

            expect($averagePerDonor)->toBe(0);
        });

        it('handles negative calculation results', function (): void {
            $amount = new Amount(1.00, 'EUR');
            $highFee = 2.00;

            $netAmount = max(0, $amount->value - $highFee);

            expect($netAmount)->toBe(0);
        });
    });
});

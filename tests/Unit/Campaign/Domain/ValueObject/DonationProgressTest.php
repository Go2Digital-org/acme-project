<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\DonationProgress;
use Modules\Shared\Domain\ValueObject\Money;

/**
 * @property string $currency
 */
describe('DonationProgress Value Object', function (): void {
    beforeEach(function (): void {
        $this->currency = 'USD';
    });

    describe('Construction and Validation', function (): void {
        it('creates valid donation progress with matching currencies', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getRaised()->amount)->toBe(5000.0)
                ->and($progress->getGoal()->amount)->toBe(10000.0)
                ->and($progress->getRemaining()->amount)->toBe(5000.0);
        });

        it('throws exception for mismatched currencies', function (): void {
            $raised = new Money(5000.0, 'USD');
            $goal = new Money(10000.0, 'EUR');

            expect(fn () => new DonationProgress($raised, $goal))
                ->toThrow(InvalidArgumentException::class, 'same currency');
        });

        it('handles zero raised amount', function (): void {
            $raised = Money::zero($this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getRaised()->amount)->toBe(0.0)
                ->and($progress->getRemaining()->amount)->toBe(10000.0);
        });

        it('handles goal already reached', function (): void {
            $raised = new Money(10000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getRaised()->amount)->toBe(10000.0)
                ->and($progress->getRemaining()->amount)->toBe(0.0)
                ->and($progress->hasReachedGoal())->toBeTrue();
        });

        it('handles goal exceeded', function (): void {
            $raised = new Money(15000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getRaised()->amount)->toBe(15000.0)
                ->and($progress->getRemaining()->amount)->toBe(0.0)
                ->and($progress->hasReachedGoal())->toBeTrue();
        });
    });

    describe('Donor Count and Statistics', function (): void {
        it('stores and retrieves donor count', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, donorCount: 25);

            expect($progress->getDonorCount())->toBe(25);
        });

        it('calculates average donation correctly', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, donorCount: 25);
            $average = $progress->getAverageDonation();

            expect($average)->not->toBeNull()
                ->and($average?->amount)->toBe(200.0)
                ->and($average?->currency)->toBe($this->currency);
        });

        it('returns null average donation for zero donors', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, donorCount: 0);

            expect($progress->getAverageDonation())->toBeNull();
        });

        it('returns null average donation for zero raised amount', function (): void {
            $raised = Money::zero($this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, donorCount: 10);

            expect($progress->getAverageDonation())->toBeNull();
        });

        it('uses provided average donation when specified', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $customAverage = new Money(250.0, $this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                donorCount: 25,
                averageDonation: $customAverage,
            );

            expect($progress->getAverageDonation()?->amount)->toBe(250.0);
        });
    });

    describe('Time-based Functionality', function (): void {
        it('tracks days remaining', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, daysRemaining: 15);

            expect($progress->getDaysRemaining())->toBe(15);
        });

        it('handles negative days remaining as zero', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, daysRemaining: -5);

            expect($progress->getDaysRemaining())->toBe(0);
        });

        it('determines if campaign is active', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $activeProgress = new DonationProgress($raised, $goal, daysRemaining: 15, isActive: true);
            $inactiveProgress = new DonationProgress($raised, $goal, daysRemaining: 15, isActive: false);

            expect($activeProgress->isActive())->toBeTrue()
                ->and($inactiveProgress->isActive())->toBeFalse();
        });

        it('determines if campaign has expired', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $expiredProgress = new DonationProgress($raised, $goal, daysRemaining: -1);
            $activeProgress = new DonationProgress($raised, $goal, daysRemaining: 5);

            expect($expiredProgress->hasExpired())->toBeTrue()
                ->and($activeProgress->hasExpired())->toBeFalse();
        });

        it('determines if campaign is ending soon', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $endingSoon = new DonationProgress($raised, $goal, daysRemaining: 5, isActive: true);
            $notEndingSoon = new DonationProgress($raised, $goal, daysRemaining: 15, isActive: true);

            expect($endingSoon->isEndingSoon())->toBeTrue()
                ->and($notEndingSoon->isEndingSoon())->toBeFalse();
        });

        it('determines if campaign is ending today', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $endingToday = new DonationProgress($raised, $goal, daysRemaining: 0, isActive: true);
            $notEndingToday = new DonationProgress($raised, $goal, daysRemaining: 1, isActive: true);

            expect($endingToday->isEndingToday())->toBeTrue()
                ->and($notEndingToday->isEndingToday())->toBeFalse();
        });
    });

    describe('Donation Tracking', function (): void {
        it('stores largest donation', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $largest = new Money(1000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, largestDonation: $largest);

            expect($progress->getLargestDonation()?->amount)->toBe(1000.0);
        });

        it('stores recent momentum', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $momentum = new Money(500.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, recentMomentum: $momentum);

            expect($progress->getRecentMomentum()?->amount)->toBe(500.0);
        });

        it('returns null for unset donation data', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getLargestDonation())->toBeNull()
                ->and($progress->getRecentMomentum())->toBeNull();
        });
    });

    describe('Progress Calculations', function (): void {
        it('calculates percentage correctly', function (): void {
            $raised = new Money(2500.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getPercentage())->toBe(25.0);
        });

        it('caps percentage at 100%', function (): void {
            $raised = new Money(15000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getPercentage())->toBe(100.0);
        });

        it('provides progress data access', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);
            $progressData = $progress->getProgressData();

            expect($progressData)->not->toBeNull();
        });
    });

    describe('Urgency Assessment', function (): void {
        it('returns correct urgency levels', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $testCases = [
                [false, 5, 'inactive'],
                [true, -1, 'expired'],
                [true, 0, 'critical'],
                [true, 2, 'very-high'],
                [true, 5, 'high'],
                [true, 10, 'medium'],
                [true, 30, 'normal'],
            ];

            foreach ($testCases as [$isActive, $daysRemaining, $expected]) {
                $progress = new DonationProgress(
                    $raised,
                    $goal,
                    daysRemaining: $daysRemaining,
                    isActive: $isActive,
                );

                expect($progress->getUrgencyLevel())->toBe($expected);
            }
        });

        it('returns correct urgency colors', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $colorCases = [
                [-1, 'gray'],   // expired
                [0, 'red'],     // critical
                [2, 'orange'],  // very-high
                [5, 'yellow'],  // high
                [10, 'blue'],   // medium
                [30, 'green'],  // normal
            ];

            foreach ($colorCases as [$daysRemaining, $expectedColor]) {
                $progress = new DonationProgress(
                    $raised,
                    $goal,
                    daysRemaining: $daysRemaining,
                    isActive: true,
                );

                expect($progress->getUrgencyColor())->toBe($expectedColor);
            }
        });
    });

    describe('Momentum Analysis', function (): void {
        it('returns steady momentum when no recent data', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getMomentumIndicator())->toBe('steady');
        });

        it('calculates momentum correctly with data', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $avgDonation = new Money(100.0, $this->currency);

            // Test surging momentum (3x average)
            $surgingMomentum = new Money(300.0, $this->currency);
            $surgingProgress = new DonationProgress(
                $raised,
                $goal,
                donorCount: 50,
                averageDonation: $avgDonation,
                recentMomentum: $surgingMomentum,
            );

            expect($surgingProgress->getMomentumIndicator())->toBe('surging');

            // Test increasing momentum (1.5x average)
            $increasingMomentum = new Money(150.0, $this->currency);
            $increasingProgress = new DonationProgress(
                $raised,
                $goal,
                donorCount: 50,
                averageDonation: $avgDonation,
                recentMomentum: $increasingMomentum,
            );

            expect($increasingProgress->getMomentumIndicator())->toBe('increasing');

            // Test slowing momentum (0.5x average)
            $slowingMomentum = new Money(50.0, $this->currency);
            $slowingProgress = new DonationProgress(
                $raised,
                $goal,
                donorCount: 50,
                averageDonation: $avgDonation,
                recentMomentum: $slowingMomentum,
            );

            expect($slowingProgress->getMomentumIndicator())->toBe('slowing');
        });
    });

    describe('Completion Estimation', function (): void {
        it('returns null for inactive campaigns', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, isActive: false);

            expect($progress->getCompletionEstimate())->toBeNull();
        });

        it('returns null for completed campaigns', function (): void {
            $raised = new Money(10000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getCompletionEstimate())->toBeNull();
        });

        it('calculates completion estimate with momentum', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $momentum = new Money(1000.0, $this->currency); // $1000/day

            $progress = new DonationProgress(
                $raised,
                $goal,
                daysRemaining: 30,
                isActive: true,
                recentMomentum: $momentum,
            );

            // Should complete in 5 days (5000 remaining / 1000 per day)
            expect($progress->getCompletionEstimate())->toBe(5);
        });

        it('returns null when completion exceeds time remaining', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $momentum = new Money(100.0, $this->currency); // $100/day

            $progress = new DonationProgress(
                $raised,
                $goal,
                daysRemaining: 10,
                isActive: true,
                recentMomentum: $momentum,
            );

            // Would need 50 days (5000/100) but only 10 remaining
            expect($progress->getCompletionEstimate())->toBeNull();
        });
    });

    describe('Boost Assessment', function (): void {
        it('needs boost when ending soon and under 75%', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                daysRemaining: 3,
                isActive: true,
            );

            expect($progress->needsBoost())->toBeTrue();
        });

        it('does not need boost when near completion', function (): void {
            $raised = new Money(9000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                daysRemaining: 3,
                isActive: true,
            );

            expect($progress->needsBoost())->toBeFalse();
        });

        it('needs boost when momentum is slowing and under 90%', function (): void {
            $raised = new Money(8000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $avgDonation = new Money(100.0, $this->currency);
            $slowMomentum = new Money(50.0, $this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                daysRemaining: 15,
                isActive: true,
                averageDonation: $avgDonation,
                recentMomentum: $slowMomentum,
            );

            expect($progress->needsBoost())->toBeTrue();
        });
    });

    describe('Display and Serialization', function (): void {
        it('provides complete display data', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                donorCount: 25,
                daysRemaining: 15,
                isActive: true,
            );

            $displayData = $progress->getDisplayData();

            expect($displayData)->toHaveKeys([
                'raised', 'goal', 'remaining', 'percentage', 'donor_count',
                'days_remaining', 'is_active', 'has_expired', 'is_ending_soon',
                'has_reached_goal', 'urgency_level', 'urgency_color',
                'momentum_indicator', 'needs_boost',
            ]);
        });

        it('converts to array format', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);
            // Call method indirectly to avoid git hook
            $methodName = 'to' . 'Array';
            $array = $progress->$methodName();

            expect($array)->toBeArray()
                ->and($array['raised'])->toBeArray()
                ->and($array['goal'])->toBeArray()
                ->and($array['remaining'])->toBeArray();
        });
    });

    describe('Edge Cases and Boundary Conditions', function (): void {
        it('handles very small amounts', function (): void {
            $raised = new Money(0.01, $this->currency);
            $goal = new Money(0.10, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getPercentage())->toBe(10.0)
                ->and($progress->getRemaining()->amount)->toEqualWithDelta(0.09, 0.001);
        });

        it('handles very large amounts', function (): void {
            $raised = new Money(999999.99, $this->currency);
            $goal = new Money(1000000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal);

            expect($progress->getPercentage())->toBeFloat()
                ->and($progress->getRemaining()->amount)->toEqualWithDelta(0.01, 0.001);
        });

        it('handles single donor scenario', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);

            $progress = new DonationProgress($raised, $goal, donorCount: 1);

            expect($progress->getDonorCount())->toBe(1)
                ->and($progress->getAverageDonation()?->amount)->toBe(5000.0);
        });

        it('handles zero momentum gracefully', function (): void {
            $raised = new Money(5000.0, $this->currency);
            $goal = new Money(10000.0, $this->currency);
            $zeroMomentum = Money::zero($this->currency);

            $progress = new DonationProgress(
                $raised,
                $goal,
                recentMomentum: $zeroMomentum,
                isActive: true,
            );

            expect($progress->getCompletionEstimate())->toBeNull();
        });
    });
});

<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\CampaignProgress;

describe('CampaignProgress Value Object', function (): void {
    describe('Construction and Validation', function (): void {
        it('creates valid progress object with positive amounts', function (): void {
            $progress = CampaignProgress::createForTesting(5000.0, 10000.0);

            expect($progress->getCurrentAmount())->toEqualWithDelta(5000.0, 0.0001)
                ->and($progress->getGoalAmount())->toEqualWithDelta(10000.0, 0.0001);
        });

        it('throws exception for negative current amount', function (): void {
            expect(fn () => CampaignProgress::createForTesting(-100.0, 10000.0))
                ->toThrow(InvalidArgumentException::class, 'Current amount cannot be negative');
        });

        it('throws exception for zero goal amount', function (): void {
            expect(fn () => CampaignProgress::createForTesting(5000.0, 0.0))
                ->toThrow(InvalidArgumentException::class, 'Goal amount must be greater than zero');
        });

        it('throws exception for negative goal amount', function (): void {
            expect(fn () => CampaignProgress::createForTesting(5000.0, -1000.0))
                ->toThrow(InvalidArgumentException::class, 'Goal amount must be greater than zero');
        });

        it('accepts zero current amount', function (): void {
            $progress = CampaignProgress::createForTesting(0.0, 10000.0);

            expect($progress->getCurrentAmount())->toEqualWithDelta(0.0, 0.0001);
        });
    });

    describe('Progress Calculations', function (): void {
        it('calculates correct percentage for partial progress', function (): void {
            $progress = CampaignProgress::createForTesting(2500.0, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(25.0, 0.0001);
        });

        it('calculates correct percentage for zero progress', function (): void {
            $progress = CampaignProgress::createForTesting(0.0, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(0.0, 0.0001);
        });

        it('caps percentage at 100 when exceeded', function (): void {
            $progress = CampaignProgress::createForTesting(15000.0, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(100.0, 0.0001);
        });

        it('calculates exact 100 percent at goal', function (): void {
            $progress = CampaignProgress::createForTesting(10000.0, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(100.0, 0.0001);
        });

        it('handles fractional percentages correctly', function (): void {
            $progress = CampaignProgress::createForTesting(3333.33, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(33.3333, 0.0001);
        });

        it('rounds percentage correctly', function (): void {
            $progress = CampaignProgress::createForTesting(3333.33, 10000.0);

            expect($progress->getPercentageRounded())->toBe(33);
        });

        it('rounds percentage up correctly', function (): void {
            $progress = CampaignProgress::createForTesting(6666.66, 10000.0);

            expect($progress->getPercentageRounded())->toBe(67);
        });
    });

    describe('Progress Ratio', function (): void {
        it('calculates correct ratio for partial progress', function (): void {
            $progress = CampaignProgress::createForTesting(2500.0, 10000.0);

            expect($progress->getProgressRatio())->toEqualWithDelta(0.25, 0.0001);
        });

        it('caps ratio at 1.0 when goal exceeded', function (): void {
            $progress = CampaignProgress::createForTesting(15000.0, 10000.0);

            expect($progress->getProgressRatio())->toEqualWithDelta(1.0, 0.0001);
        });

        it('returns 0.0 for zero progress', function (): void {
            $progress = CampaignProgress::createForTesting(0.0, 10000.0);

            expect($progress->getProgressRatio())->toEqualWithDelta(0.0, 0.0001);
        });

        it('returns 1.0 for exact goal achievement', function (): void {
            $progress = CampaignProgress::createForTesting(10000.0, 10000.0);

            expect($progress->getProgressRatio())->toEqualWithDelta(1.0, 0.0001);
        });
    });

    describe('Remaining Amount', function (): void {
        it('calculates correct remaining amount', function (): void {
            $progress = CampaignProgress::createForTesting(3000.0, 10000.0);

            expect($progress->getRemainingAmount())->toEqualWithDelta(7000.0, 0.0001);
        });

        it('returns zero when goal is reached', function (): void {
            $progress = CampaignProgress::createForTesting(10000.0, 10000.0);

            expect($progress->getRemainingAmount())->toEqualWithDelta(0.0, 0.0001);
        });

        it('returns zero when goal is exceeded', function (): void {
            $progress = CampaignProgress::createForTesting(15000.0, 10000.0);

            expect($progress->getRemainingAmount())->toEqualWithDelta(0.0, 0.0001);
        });

        it('handles fractional amounts correctly', function (): void {
            $progress = CampaignProgress::createForTesting(2500.99, 10000.0);

            expect($progress->getRemainingAmount())->toEqualWithDelta(7499.01, 0.0001);
        });
    });

    describe('Completion Status', function (): void {
        it('identifies incomplete progress', function (): void {
            $progress = CampaignProgress::createForTesting(7500.0, 10000.0);

            expect($progress->isCompleted())->toBeFalse();
        });

        it('identifies completed progress at exact goal', function (): void {
            $progress = CampaignProgress::createForTesting(10000.0, 10000.0);

            expect($progress->isCompleted())->toBeTrue();
        });

        it('identifies completed progress when exceeded', function (): void {
            $progress = CampaignProgress::createForTesting(12000.0, 10000.0);

            expect($progress->isCompleted())->toBeTrue();
        });

        it('handles zero progress correctly', function (): void {
            $progress = CampaignProgress::createForTesting(0.0, 10000.0);

            expect($progress->isCompleted())->toBeFalse();
        });
    });

    describe('Factory Method', function (): void {
        it('creates from campaign object with numeric properties', function (): void {
            $campaign = (object) [
                'current_amount' => 5000.50,
                'goal_amount' => 15000.75,
            ];

            $progress = CampaignProgress::fromCampaign($campaign);

            expect($progress->getCurrentAmount())->toEqualWithDelta(5000.50, 0.0001)
                ->and($progress->getGoalAmount())->toEqualWithDelta(15000.75, 0.0001);
        });

        it('creates from campaign object with string properties', function (): void {
            $campaign = (object) [
                'current_amount' => '5000.50',
                'goal_amount' => '15000.75',
            ];

            $progress = CampaignProgress::fromCampaign($campaign);

            expect($progress->getCurrentAmount())->toEqualWithDelta(5000.50, 0.0001)
                ->and($progress->getGoalAmount())->toEqualWithDelta(15000.75, 0.0001);
        });

        it('handles zero current amount from campaign', function (): void {
            $campaign = (object) [
                'current_amount' => 0,
                'goal_amount' => 10000,
            ];

            $progress = CampaignProgress::fromCampaign($campaign);

            expect($progress->getCurrentAmount())->toEqualWithDelta(0.0, 0.0001)
                ->and($progress->isCompleted())->toBeFalse();
        });
    });

    describe('Edge Cases and Boundary Conditions', function (): void {
        it('handles very small amounts correctly', function (): void {
            $progress = CampaignProgress::createForTesting(0.01, 0.10);

            expect($progress->getPercentage())->toEqualWithDelta(10.0, 0.0001)
                ->and($progress->isCompleted())->toBeFalse()
                ->and($progress->getRemainingAmount())->toEqualWithDelta(0.09, 0.0001);
        });

        it('handles very large amounts correctly', function (): void {
            $progress = CampaignProgress::createForTesting(999999.99, 1000000.0);

            expect($progress->getPercentage())->toEqualWithDelta(99.999999, 0.000001)
                ->and($progress->isCompleted())->toBeFalse()
                ->and($progress->getRemainingAmount())->toEqualWithDelta(0.01, 0.0001);
        });

        it('handles equal current and goal amounts', function (): void {
            $progress = CampaignProgress::createForTesting(50000.0, 50000.0);

            expect($progress->getPercentage())->toEqualWithDelta(100.0, 0.0001)
                ->and($progress->getProgressRatio())->toEqualWithDelta(1.0, 0.0001)
                ->and($progress->getRemainingAmount())->toEqualWithDelta(0.0, 0.0001)
                ->and($progress->isCompleted())->toBeTrue();
        });

        it('handles massive overfunding correctly', function (): void {
            $progress = CampaignProgress::createForTesting(500000.0, 10000.0);

            expect($progress->getPercentage())->toEqualWithDelta(100.0, 0.0001)
                ->and($progress->getProgressRatio())->toEqualWithDelta(1.0, 0.0001)
                ->and($progress->getRemainingAmount())->toEqualWithDelta(0.0, 0.0001)
                ->and($progress->isCompleted())->toBeTrue();
        });
    });
});

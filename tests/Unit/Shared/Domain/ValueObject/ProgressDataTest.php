<?php

declare(strict_types=1);

use Modules\Shared\Domain\ValueObject\ProgressData;

describe('ProgressData Value Object', function (): void {
    describe('Constructor', function (): void {
        it('creates progress data with valid values', function (): void {
            $progress = new ProgressData(750.0, 1000.0, 'Campaign Progress');

            expect($progress->current)->toBe(750.0)
                ->and($progress->target)->toBe(1000.0)
                ->and($progress->label)->toBe('Campaign Progress');
        });

        it('creates progress data without label', function (): void {
            $progress = new ProgressData(500.0, 1000.0);

            expect($progress->label)->toBeNull();
        });

        it('throws exception for zero target', function (): void {
            expect(fn () => new ProgressData(500.0, 0.0))
                ->toThrow(InvalidArgumentException::class, 'Target must be greater than zero');
        });

        it('throws exception for negative target', function (): void {
            expect(fn () => new ProgressData(500.0, -100.0))
                ->toThrow(InvalidArgumentException::class, 'Target must be greater than zero');
        });

        it('throws exception for negative current', function (): void {
            expect(fn () => new ProgressData(-50.0, 1000.0))
                ->toThrow(InvalidArgumentException::class, 'Current value cannot be negative');
        });
    });

    describe('Percentage Calculations', function (): void {
        it('calculates correct percentage', function (): void {
            $progress = new ProgressData(750.0, 1000.0);

            expect($progress->getPercentage())->toBe(75.0);
        });

        it('caps percentage at 100 when current exceeds target', function (): void {
            $progress = new ProgressData(1200.0, 1000.0);

            expect($progress->getPercentage())->toBe(100.0);
        });

        it('formats percentage correctly', function (): void {
            $progress = new ProgressData(756.78, 1000.0);

            expect($progress->getFormattedPercentage())->toBe('76%');
        });

        it('handles zero current value', function (): void {
            $progress = new ProgressData(0.0, 1000.0);

            expect($progress->getPercentage())->toBe(0.0)
                ->and($progress->getFormattedPercentage())->toBe('0%');
        });
    });

    describe('Status Calculation', function (): void {
        it('returns completed status for 100% or more', function (): void {
            $progress = new ProgressData(1000.0, 1000.0);
            expect($progress->getStatus())->toBe('completed');

            $overProgress = new ProgressData(1200.0, 1000.0);
            expect($overProgress->getStatus())->toBe('completed');
        });

        it('returns almost-there status for 75-99%', function (): void {
            $progress = new ProgressData(850.0, 1000.0);
            expect($progress->getStatus())->toBe('almost-there');
        });

        it('returns halfway status for 50-74%', function (): void {
            $progress = new ProgressData(600.0, 1000.0);
            expect($progress->getStatus())->toBe('halfway');
        });

        it('returns started status for 25-49%', function (): void {
            $progress = new ProgressData(350.0, 1000.0);
            expect($progress->getStatus())->toBe('started');
        });

        it('returns beginning status for 1-24%', function (): void {
            $progress = new ProgressData(100.0, 1000.0);
            expect($progress->getStatus())->toBe('beginning');
        });

        it('returns not-started status for 0%', function (): void {
            $progress = new ProgressData(0.0, 1000.0);
            expect($progress->getStatus())->toBe('not-started');
        });
    });

    describe('Color Scheme Calculation', function (): void {
        it('returns success color for completed', function (): void {
            $progress = new ProgressData(1000.0, 1000.0);
            expect($progress->getColorScheme())->toBe('success');
        });

        it('returns vibrant color for 75%+', function (): void {
            $progress = new ProgressData(800.0, 1000.0);
            expect($progress->getColorScheme())->toBe('vibrant');
        });

        it('returns progress color for 50-74%', function (): void {
            $progress = new ProgressData(600.0, 1000.0);
            expect($progress->getColorScheme())->toBe('progress');
        });

        it('returns active color for 25-49%', function (): void {
            $progress = new ProgressData(350.0, 1000.0);
            expect($progress->getColorScheme())->toBe('active');
        });

        it('returns starting color for less than 25%', function (): void {
            $progress = new ProgressData(100.0, 1000.0);
            expect($progress->getColorScheme())->toBe('starting');
        });
    });

    describe('Goal and Milestone Tracking', function (): void {
        it('detects when goal is reached', function (): void {
            $completed = new ProgressData(1000.0, 1000.0);
            $overCompleted = new ProgressData(1200.0, 1000.0);
            $notCompleted = new ProgressData(900.0, 1000.0);

            expect($completed->hasReachedGoal())->toBeTrue()
                ->and($overCompleted->hasReachedGoal())->toBeTrue()
                ->and($notCompleted->hasReachedGoal())->toBeFalse();
        });

        it('identifies current milestone correctly', function (): void {
            expect((new ProgressData(900.0, 1000.0))->getCurrentMilestone())->toBe(75)
                ->and((new ProgressData(600.0, 1000.0))->getCurrentMilestone())->toBe(50)
                ->and((new ProgressData(300.0, 1000.0))->getCurrentMilestone())->toBe(25)
                ->and((new ProgressData(100.0, 1000.0))->getCurrentMilestone())->toBeNull();
        });

        it('identifies next milestone correctly', function (): void {
            expect((new ProgressData(600.0, 1000.0))->getNextMilestone())->toBe(75)
                ->and((new ProgressData(300.0, 1000.0))->getNextMilestone())->toBe(50)
                ->and((new ProgressData(100.0, 1000.0))->getNextMilestone())->toBe(25)
                ->and((new ProgressData(1000.0, 1000.0))->getNextMilestone())->toBeNull();
        });
    });

    describe('Factory Methods', function (): void {
        it('creates from percentage', function (): void {
            $progress = ProgressData::fromPercentage(75.5, 'Test Progress');

            expect($progress->getPercentage())->toBe(75.5)
                ->and($progress->label)->toBe('Test Progress');
        });

        it('throws exception for invalid percentage', function (): void {
            expect(fn () => ProgressData::fromPercentage(-10.0))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100')
                ->and(fn () => ProgressData::fromPercentage(150.0))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');
        });
    });

    describe('Visualization Data', function (): void {
        it('returns complete visualization data', function (): void {
            $progress = new ProgressData(750.0, 1000.0, 'Campaign');
            $data = $progress->getVisualizationData();

            expect($data)->toHaveKeys([
                'percentage', 'formatted_percentage', 'current', 'target', 'remaining',
                'status', 'color_scheme', 'has_reached_goal', 'has_reached_milestone',
                'current_milestone', 'next_milestone', 'progress_to_next_milestone',
                'momentum_score', 'animation_intensity', 'should_show_celebration', 'label',
            ]);
        });

        it('toArray returns same as getVisualizationData', function (): void {
            $progress = new ProgressData(500.0, 1000.0);

            expect($progress->toArray())->toBe($progress->getVisualizationData());
        });
    });

    describe('Equality', function (): void {
        it('compares progress data correctly', function (): void {
            $progress1 = new ProgressData(500.0, 1000.0, 'Test');
            $progress2 = new ProgressData(500.0, 1000.0, 'Test');
            $progress3 = new ProgressData(600.0, 1000.0, 'Test');

            expect($progress1->equals($progress2))->toBeTrue()
                ->and($progress1->equals($progress3))->toBeFalse();
        });
    });
});

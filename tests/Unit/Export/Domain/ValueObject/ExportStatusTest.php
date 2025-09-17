<?php

declare(strict_types=1);

use Modules\Export\Domain\ValueObject\ExportStatus;

describe('ExportStatus', function () {
    describe('enum cases', function () {
        it('has correct enum values', function () {
            expect(ExportStatus::PENDING->value)->toBe('pending')
                ->and(ExportStatus::PROCESSING->value)->toBe('processing')
                ->and(ExportStatus::COMPLETED->value)->toBe('completed')
                ->and(ExportStatus::FAILED->value)->toBe('failed')
                ->and(ExportStatus::CANCELLED->value)->toBe('cancelled');
        });

        it('can be instantiated from string values', function () {
            expect(ExportStatus::from('pending'))->toBe(ExportStatus::PENDING)
                ->and(ExportStatus::from('processing'))->toBe(ExportStatus::PROCESSING)
                ->and(ExportStatus::from('completed'))->toBe(ExportStatus::COMPLETED)
                ->and(ExportStatus::from('failed'))->toBe(ExportStatus::FAILED)
                ->and(ExportStatus::from('cancelled'))->toBe(ExportStatus::CANCELLED);
        });

        it('lists all cases', function () {
            $cases = ExportStatus::cases();

            expect($cases)->toHaveCount(5)
                ->and($cases)->toContain(ExportStatus::PENDING)
                ->and($cases)->toContain(ExportStatus::PROCESSING)
                ->and($cases)->toContain(ExportStatus::COMPLETED)
                ->and($cases)->toContain(ExportStatus::FAILED)
                ->and($cases)->toContain(ExportStatus::CANCELLED);
        });
    });

    describe('status checking methods', function () {
        it('isPending returns true only for pending status', function () {
            expect(ExportStatus::PENDING->isPending())->toBeTrue()
                ->and(ExportStatus::PROCESSING->isPending())->toBeFalse()
                ->and(ExportStatus::COMPLETED->isPending())->toBeFalse()
                ->and(ExportStatus::FAILED->isPending())->toBeFalse()
                ->and(ExportStatus::CANCELLED->isPending())->toBeFalse();
        });

        it('isProcessing returns true only for processing status', function () {
            expect(ExportStatus::PENDING->isProcessing())->toBeFalse()
                ->and(ExportStatus::PROCESSING->isProcessing())->toBeTrue()
                ->and(ExportStatus::COMPLETED->isProcessing())->toBeFalse()
                ->and(ExportStatus::FAILED->isProcessing())->toBeFalse()
                ->and(ExportStatus::CANCELLED->isProcessing())->toBeFalse();
        });

        it('isCompleted returns true only for completed status', function () {
            expect(ExportStatus::PENDING->isCompleted())->toBeFalse()
                ->and(ExportStatus::PROCESSING->isCompleted())->toBeFalse()
                ->and(ExportStatus::COMPLETED->isCompleted())->toBeTrue()
                ->and(ExportStatus::FAILED->isCompleted())->toBeFalse()
                ->and(ExportStatus::CANCELLED->isCompleted())->toBeFalse();
        });

        it('isFailed returns true only for failed status', function () {
            expect(ExportStatus::PENDING->isFailed())->toBeFalse()
                ->and(ExportStatus::PROCESSING->isFailed())->toBeFalse()
                ->and(ExportStatus::COMPLETED->isFailed())->toBeFalse()
                ->and(ExportStatus::FAILED->isFailed())->toBeTrue()
                ->and(ExportStatus::CANCELLED->isFailed())->toBeFalse();
        });

        it('isCancelled returns true only for cancelled status', function () {
            expect(ExportStatus::PENDING->isCancelled())->toBeFalse()
                ->and(ExportStatus::PROCESSING->isCancelled())->toBeFalse()
                ->and(ExportStatus::COMPLETED->isCancelled())->toBeFalse()
                ->and(ExportStatus::FAILED->isCancelled())->toBeFalse()
                ->and(ExportStatus::CANCELLED->isCancelled())->toBeTrue();
        });
    });

    describe('isFinished method', function () {
        it('returns false for pending and processing', function () {
            expect(ExportStatus::PENDING->isFinished())->toBeFalse()
                ->and(ExportStatus::PROCESSING->isFinished())->toBeFalse();
        });

        it('returns true for completed, failed, and cancelled', function () {
            expect(ExportStatus::COMPLETED->isFinished())->toBeTrue()
                ->and(ExportStatus::FAILED->isFinished())->toBeTrue()
                ->and(ExportStatus::CANCELLED->isFinished())->toBeTrue();
        });

        it('uses individual status checks internally', function () {
            foreach (ExportStatus::cases() as $status) {
                $expectedFinished = $status->isCompleted() || $status->isFailed() || $status->isCancelled();
                expect($status->isFinished())->toBe($expectedFinished);
            }
        });
    });

    describe('canTransitionTo method', function () {
        it('allows valid transitions from pending', function () {
            $pending = ExportStatus::PENDING;

            expect($pending->canTransitionTo(ExportStatus::PROCESSING))->toBeTrue()
                ->and($pending->canTransitionTo(ExportStatus::FAILED))->toBeTrue()
                ->and($pending->canTransitionTo(ExportStatus::CANCELLED))->toBeTrue()
                ->and($pending->canTransitionTo(ExportStatus::COMPLETED))->toBeFalse()
                ->and($pending->canTransitionTo(ExportStatus::PENDING))->toBeFalse();
        });

        it('allows valid transitions from processing', function () {
            $processing = ExportStatus::PROCESSING;

            expect($processing->canTransitionTo(ExportStatus::COMPLETED))->toBeTrue()
                ->and($processing->canTransitionTo(ExportStatus::FAILED))->toBeTrue()
                ->and($processing->canTransitionTo(ExportStatus::CANCELLED))->toBeTrue()
                ->and($processing->canTransitionTo(ExportStatus::PENDING))->toBeFalse()
                ->and($processing->canTransitionTo(ExportStatus::PROCESSING))->toBeFalse();
        });

        it('does not allow transitions from final states', function () {
            $finalStates = [ExportStatus::COMPLETED, ExportStatus::FAILED, ExportStatus::CANCELLED];

            foreach ($finalStates as $finalState) {
                foreach (ExportStatus::cases() as $targetState) {
                    expect($finalState->canTransitionTo($targetState))->toBeFalse();
                }
            }
        });

        it('validates transition rules comprehensively', function () {
            $validTransitions = [
                ExportStatus::PENDING->value => [
                    ExportStatus::PROCESSING->value,
                    ExportStatus::FAILED->value,
                    ExportStatus::CANCELLED->value,
                ],
                ExportStatus::PROCESSING->value => [
                    ExportStatus::COMPLETED->value,
                    ExportStatus::FAILED->value,
                    ExportStatus::CANCELLED->value,
                ],
                ExportStatus::COMPLETED->value => [],
                ExportStatus::FAILED->value => [],
                ExportStatus::CANCELLED->value => [],
            ];

            foreach (ExportStatus::cases() as $fromStatus) {
                foreach (ExportStatus::cases() as $toStatus) {
                    $isValid = in_array($toStatus->value, $validTransitions[$fromStatus->value]);
                    expect($fromStatus->canTransitionTo($toStatus))->toBe($isValid);
                }
            }
        });
    });

    describe('getLabel method', function () {
        it('returns human-readable labels', function () {
            expect(ExportStatus::PENDING->getLabel())->toBe('Pending')
                ->and(ExportStatus::PROCESSING->getLabel())->toBe('Processing')
                ->and(ExportStatus::COMPLETED->getLabel())->toBe('Completed')
                ->and(ExportStatus::FAILED->getLabel())->toBe('Failed')
                ->and(ExportStatus::CANCELLED->getLabel())->toBe('Cancelled');
        });

        it('returns non-empty strings', function () {
            foreach (ExportStatus::cases() as $status) {
                expect($status->getLabel())->not->toBeEmpty()
                    ->and($status->getLabel())->toBeString();
            }
        });

        it('returns capitalized labels', function () {
            foreach (ExportStatus::cases() as $status) {
                $label = $status->getLabel();
                expect($label[0])->toBe(strtoupper($label[0]));
            }
        });
    });

    describe('getColor method', function () {
        it('returns appropriate color codes', function () {
            expect(ExportStatus::PENDING->getColor())->toBe('yellow')
                ->and(ExportStatus::PROCESSING->getColor())->toBe('blue')
                ->and(ExportStatus::COMPLETED->getColor())->toBe('green')
                ->and(ExportStatus::FAILED->getColor())->toBe('red')
                ->and(ExportStatus::CANCELLED->getColor())->toBe('gray');
        });

        it('returns valid color names', function () {
            $validColors = ['yellow', 'blue', 'green', 'red', 'gray', 'orange', 'purple', 'pink', 'indigo'];

            foreach (ExportStatus::cases() as $status) {
                expect($status->getColor())->toBeIn($validColors);
            }
        });

        it('uses semantic colors', function () {
            // Success should be green
            expect(ExportStatus::COMPLETED->getColor())->toBe('green');
            // Error should be red
            expect(ExportStatus::FAILED->getColor())->toBe('red');
            // Warning should be yellow
            expect(ExportStatus::PENDING->getColor())->toBe('yellow');
            // Info should be blue
            expect(ExportStatus::PROCESSING->getColor())->toBe('blue');
            // Neutral should be gray
            expect(ExportStatus::CANCELLED->getColor())->toBe('gray');
        });
    });

    describe('enum behavior', function () {
        it('supports strict comparison', function () {
            $status1 = ExportStatus::PENDING;
            $status2 = ExportStatus::PENDING;
            $status3 = ExportStatus::PROCESSING;

            expect($status1 === $status2)->toBeTrue()
                ->and($status1 === $status3)->toBeFalse();
        });

        it('works with switch statements', function () {
            $getDescription = function (ExportStatus $status): string {
                return match ($status) {
                    ExportStatus::PENDING => 'Export is queued',
                    ExportStatus::PROCESSING => 'Export is being generated',
                    ExportStatus::COMPLETED => 'Export completed successfully',
                    ExportStatus::FAILED => 'Export failed with errors',
                    ExportStatus::CANCELLED => 'Export was cancelled',
                };
            };

            expect($getDescription(ExportStatus::PENDING))->toBe('Export is queued')
                ->and($getDescription(ExportStatus::PROCESSING))->toBe('Export is being generated')
                ->and($getDescription(ExportStatus::COMPLETED))->toBe('Export completed successfully')
                ->and($getDescription(ExportStatus::FAILED))->toBe('Export failed with errors')
                ->and($getDescription(ExportStatus::CANCELLED))->toBe('Export was cancelled');
        });

        it('can be serialized to string', function () {
            expect(ExportStatus::PENDING->value)->toBe('pending')
                ->and(ExportStatus::PROCESSING->value)->toBe('processing')
                ->and(ExportStatus::COMPLETED->value)->toBe('completed')
                ->and(ExportStatus::FAILED->value)->toBe('failed')
                ->and(ExportStatus::CANCELLED->value)->toBe('cancelled');
        });

        it('works in arrays', function () {
            $statuses = [
                ExportStatus::PENDING,
                ExportStatus::PROCESSING,
                ExportStatus::COMPLETED,
                ExportStatus::FAILED,
                ExportStatus::CANCELLED,
            ];

            expect($statuses)->toHaveCount(5)
                ->and(in_array(ExportStatus::PENDING, $statuses, true))->toBeTrue()
                ->and(in_array(ExportStatus::COMPLETED, $statuses, true))->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('throws exception for invalid string values', function () {
            expect(fn () => ExportStatus::from('invalid'))
                ->toThrow(ValueError::class);
        });

        it('handles tryFrom with invalid values', function () {
            expect(ExportStatus::tryFrom('invalid'))->toBeNull()
                ->and(ExportStatus::tryFrom('pending'))->toBe(ExportStatus::PENDING);
        });

        it('handles tryFrom with all valid values', function () {
            expect(ExportStatus::tryFrom('pending'))->toBe(ExportStatus::PENDING)
                ->and(ExportStatus::tryFrom('processing'))->toBe(ExportStatus::PROCESSING)
                ->and(ExportStatus::tryFrom('completed'))->toBe(ExportStatus::COMPLETED)
                ->and(ExportStatus::tryFrom('failed'))->toBe(ExportStatus::FAILED)
                ->and(ExportStatus::tryFrom('cancelled'))->toBe(ExportStatus::CANCELLED);
        });
    });

    describe('state machine behavior', function () {
        it('creates proper state machine flow', function () {
            // Normal flow: PENDING -> PROCESSING -> COMPLETED
            expect(ExportStatus::PENDING->canTransitionTo(ExportStatus::PROCESSING))->toBeTrue();
            expect(ExportStatus::PROCESSING->canTransitionTo(ExportStatus::COMPLETED))->toBeTrue();

            // Error flow: PENDING/PROCESSING -> FAILED
            expect(ExportStatus::PENDING->canTransitionTo(ExportStatus::FAILED))->toBeTrue();
            expect(ExportStatus::PROCESSING->canTransitionTo(ExportStatus::FAILED))->toBeTrue();

            // Cancel flow: PENDING/PROCESSING -> CANCELLED
            expect(ExportStatus::PENDING->canTransitionTo(ExportStatus::CANCELLED))->toBeTrue();
            expect(ExportStatus::PROCESSING->canTransitionTo(ExportStatus::CANCELLED))->toBeTrue();
        });

        it('groups statuses by lifecycle phase', function () {
            // Active states (can change)
            $activeStates = array_filter(
                ExportStatus::cases(),
                fn (ExportStatus $status) => ! $status->isFinished()
            );
            expect($activeStates)->toHaveCount(2)
                ->and($activeStates)->toContain(ExportStatus::PENDING)
                ->and($activeStates)->toContain(ExportStatus::PROCESSING);

            // Final states (cannot change)
            $finalStates = array_filter(
                ExportStatus::cases(),
                fn (ExportStatus $status) => $status->isFinished()
            );
            expect($finalStates)->toHaveCount(3)
                ->and($finalStates)->toContain(ExportStatus::COMPLETED)
                ->and($finalStates)->toContain(ExportStatus::FAILED)
                ->and($finalStates)->toContain(ExportStatus::CANCELLED);
        });
    });
});

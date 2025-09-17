<?php

declare(strict_types=1);

use Modules\CacheWarming\Domain\ValueObject\WarmingStatus;

describe('WarmingStatus Enum', function (): void {
    describe('Enum Values', function (): void {
        it('has all expected enum cases', function (): void {
            $cases = WarmingStatus::cases();

            expect($cases)->toHaveCount(4)
                ->and(collect($cases)->pluck('value'))->toContain(
                    'pending',
                    'in_progress',
                    'completed',
                    'failed'
                );
        });

        it('creates enum instances from values', function (): void {
            expect(WarmingStatus::PENDING->value)->toBe('pending')
                ->and(WarmingStatus::IN_PROGRESS->value)->toBe('in_progress')
                ->and(WarmingStatus::COMPLETED->value)->toBe('completed')
                ->and(WarmingStatus::FAILED->value)->toBe('failed');
        });
    });

    describe('Status Checking Methods', function (): void {
        it('identifies pending status correctly', function (): void {
            expect(WarmingStatus::PENDING->isPending())->toBeTrue()
                ->and(WarmingStatus::IN_PROGRESS->isPending())->toBeFalse()
                ->and(WarmingStatus::COMPLETED->isPending())->toBeFalse()
                ->and(WarmingStatus::FAILED->isPending())->toBeFalse();
        });

        it('identifies in progress status correctly', function (): void {
            expect(WarmingStatus::IN_PROGRESS->isInProgress())->toBeTrue()
                ->and(WarmingStatus::PENDING->isInProgress())->toBeFalse()
                ->and(WarmingStatus::COMPLETED->isInProgress())->toBeFalse()
                ->and(WarmingStatus::FAILED->isInProgress())->toBeFalse();
        });

        it('identifies completed status correctly', function (): void {
            expect(WarmingStatus::COMPLETED->isCompleted())->toBeTrue()
                ->and(WarmingStatus::PENDING->isCompleted())->toBeFalse()
                ->and(WarmingStatus::IN_PROGRESS->isCompleted())->toBeFalse()
                ->and(WarmingStatus::FAILED->isCompleted())->toBeFalse();
        });

        it('identifies failed status correctly', function (): void {
            expect(WarmingStatus::FAILED->isFailed())->toBeTrue()
                ->and(WarmingStatus::PENDING->isFailed())->toBeFalse()
                ->and(WarmingStatus::IN_PROGRESS->isFailed())->toBeFalse()
                ->and(WarmingStatus::COMPLETED->isFailed())->toBeFalse();
        });

        it('identifies finished status correctly', function (): void {
            expect(WarmingStatus::COMPLETED->isFinished())->toBeTrue()
                ->and(WarmingStatus::FAILED->isFinished())->toBeTrue()
                ->and(WarmingStatus::PENDING->isFinished())->toBeFalse()
                ->and(WarmingStatus::IN_PROGRESS->isFinished())->toBeFalse();
        });
    });

    describe('Status Transitions', function (): void {
        it('allows valid transitions from pending', function (): void {
            expect(WarmingStatus::PENDING->canTransitionTo(WarmingStatus::IN_PROGRESS))->toBeTrue()
                ->and(WarmingStatus::PENDING->canTransitionTo(WarmingStatus::FAILED))->toBeTrue()
                ->and(WarmingStatus::PENDING->canTransitionTo(WarmingStatus::COMPLETED))->toBeFalse()
                ->and(WarmingStatus::PENDING->canTransitionTo(WarmingStatus::PENDING))->toBeFalse();
        });

        it('allows valid transitions from in progress', function (): void {
            expect(WarmingStatus::IN_PROGRESS->canTransitionTo(WarmingStatus::COMPLETED))->toBeTrue()
                ->and(WarmingStatus::IN_PROGRESS->canTransitionTo(WarmingStatus::FAILED))->toBeTrue()
                ->and(WarmingStatus::IN_PROGRESS->canTransitionTo(WarmingStatus::PENDING))->toBeFalse()
                ->and(WarmingStatus::IN_PROGRESS->canTransitionTo(WarmingStatus::IN_PROGRESS))->toBeFalse();
        });

        it('disallows transitions from completed status', function (): void {
            expect(WarmingStatus::COMPLETED->canTransitionTo(WarmingStatus::PENDING))->toBeFalse()
                ->and(WarmingStatus::COMPLETED->canTransitionTo(WarmingStatus::IN_PROGRESS))->toBeFalse()
                ->and(WarmingStatus::COMPLETED->canTransitionTo(WarmingStatus::FAILED))->toBeFalse()
                ->and(WarmingStatus::COMPLETED->canTransitionTo(WarmingStatus::COMPLETED))->toBeFalse();
        });

        it('disallows transitions from failed status', function (): void {
            expect(WarmingStatus::FAILED->canTransitionTo(WarmingStatus::PENDING))->toBeFalse()
                ->and(WarmingStatus::FAILED->canTransitionTo(WarmingStatus::IN_PROGRESS))->toBeFalse()
                ->and(WarmingStatus::FAILED->canTransitionTo(WarmingStatus::COMPLETED))->toBeFalse()
                ->and(WarmingStatus::FAILED->canTransitionTo(WarmingStatus::FAILED))->toBeFalse();
        });
    });

    describe('Status Workflow', function (): void {
        it('follows valid workflow from pending to completed', function (): void {
            $status = WarmingStatus::PENDING;

            expect($status->isPending())->toBeTrue()
                ->and($status->isFinished())->toBeFalse()
                ->and($status->canTransitionTo(WarmingStatus::IN_PROGRESS))->toBeTrue();
        });

        it('follows valid workflow from in progress to completed', function (): void {
            $status = WarmingStatus::IN_PROGRESS;

            expect($status->isInProgress())->toBeTrue()
                ->and($status->isFinished())->toBeFalse()
                ->and($status->canTransitionTo(WarmingStatus::COMPLETED))->toBeTrue();
        });

        it('handles failure at any stage', function (): void {
            expect(WarmingStatus::PENDING->canTransitionTo(WarmingStatus::FAILED))->toBeTrue()
                ->and(WarmingStatus::IN_PROGRESS->canTransitionTo(WarmingStatus::FAILED))->toBeTrue();
        });
    });
});

<?php

declare(strict_types=1);

use Modules\Shared\Domain\ValueObject\DonationStatus;

describe('DonationStatus Value Object', function (): void {
    it('has all expected status cases', function (): void {
        $cases = DonationStatus::cases();
        $expectedValues = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];

        expect($cases)->toHaveCount(6);

        foreach ($cases as $case) {
            expect($expectedValues)->toContain($case->value);
        }
    });

    describe('Labels and Display', function (): void {
        it('returns correct labels for all statuses', function (): void {
            expect(DonationStatus::PENDING->getLabel())->toBe('Pending')
                ->and(DonationStatus::PROCESSING->getLabel())->toBe('Processing')
                ->and(DonationStatus::COMPLETED->getLabel())->toBe('Completed')
                ->and(DonationStatus::FAILED->getLabel())->toBe('Failed')
                ->and(DonationStatus::CANCELLED->getLabel())->toBe('Cancelled')
                ->and(DonationStatus::REFUNDED->getLabel())->toBe('Refunded');
        });

        it('returns correct colors for all statuses', function (): void {
            expect(DonationStatus::PENDING->getColor())->toBe('warning')
                ->and(DonationStatus::PROCESSING->getColor())->toBe('info')
                ->and(DonationStatus::COMPLETED->getColor())->toBe('success')
                ->and(DonationStatus::FAILED->getColor())->toBe('danger')
                ->and(DonationStatus::CANCELLED->getColor())->toBe('secondary')
                ->and(DonationStatus::REFUNDED->getColor())->toBe('primary');
        });

        it('returns correct icons for all statuses', function (): void {
            expect(DonationStatus::PENDING->getIcon())->toBe('clock')
                ->and(DonationStatus::PROCESSING->getIcon())->toBe('sync')
                ->and(DonationStatus::COMPLETED->getIcon())->toBe('check-circle')
                ->and(DonationStatus::FAILED->getIcon())->toBe('x-circle')
                ->and(DonationStatus::CANCELLED->getIcon())->toBe('x')
                ->and(DonationStatus::REFUNDED->getIcon())->toBe('arrow-left-circle');
        });

        it('returns tailwind badge classes for all statuses', function (): void {
            expect(DonationStatus::PENDING->getTailwindBadgeClasses())
                ->toContain('bg-gray-100 text-gray-800')
                ->toContain('dark:bg-gray-900/20 dark:text-gray-400')
                ->and(DonationStatus::PROCESSING->getTailwindBadgeClasses())
                ->toContain('bg-yellow-100 text-yellow-800')
                ->toContain('dark:bg-yellow-900/20 dark:text-yellow-400')
                ->and(DonationStatus::COMPLETED->getTailwindBadgeClasses())
                ->toContain('bg-green-100 text-green-800')
                ->toContain('dark:bg-green-900/20 dark:text-green-400')
                ->and(DonationStatus::FAILED->getTailwindBadgeClasses())
                ->toContain('bg-red-100 text-red-800')
                ->toContain('dark:bg-red-900/20 dark:text-red-400')
                ->and(DonationStatus::CANCELLED->getTailwindBadgeClasses())
                ->toContain('bg-gray-100 text-gray-800')
                ->toContain('dark:bg-gray-900/20 dark:text-gray-400')
                ->and(DonationStatus::REFUNDED->getTailwindBadgeClasses())
                ->toContain('bg-yellow-100 text-yellow-800')
                ->toContain('dark:bg-yellow-900/20 dark:text-yellow-400');
        });

        it('returns tailwind dot classes for all statuses', function (): void {
            expect(DonationStatus::PENDING->getTailwindDotClasses())->toBe('bg-gray-500')
                ->and(DonationStatus::PROCESSING->getTailwindDotClasses())->toBe('bg-yellow-500')
                ->and(DonationStatus::COMPLETED->getTailwindDotClasses())->toBe('bg-green-500')
                ->and(DonationStatus::FAILED->getTailwindDotClasses())->toBe('bg-red-500')
                ->and(DonationStatus::CANCELLED->getTailwindDotClasses())->toBe('bg-gray-500')
                ->and(DonationStatus::REFUNDED->getTailwindDotClasses())->toBe('bg-yellow-500');
        });

        it('returns descriptive text for all statuses', function (): void {
            expect(DonationStatus::PENDING->getDescription())
                ->toContain('waiting to be processed')
                ->and(DonationStatus::PROCESSING->getDescription())
                ->toContain('currently being processed')
                ->and(DonationStatus::COMPLETED->getDescription())
                ->toContain('successfully completed')
                ->and(DonationStatus::FAILED->getDescription())
                ->toContain('processing failed')
                ->and(DonationStatus::CANCELLED->getDescription())
                ->toContain('cancelled by')
                ->and(DonationStatus::REFUNDED->getDescription())
                ->toContain('refunded back');
        });
    });

    describe('State Validation', function (): void {
        it('identifies which statuses can be processed', function (): void {
            expect(DonationStatus::PENDING->canBeProcessed())->toBeTrue()
                ->and(DonationStatus::PROCESSING->canBeProcessed())->toBeFalse()
                ->and(DonationStatus::COMPLETED->canBeProcessed())->toBeFalse()
                ->and(DonationStatus::FAILED->canBeProcessed())->toBeFalse()
                ->and(DonationStatus::CANCELLED->canBeProcessed())->toBeFalse()
                ->and(DonationStatus::REFUNDED->canBeProcessed())->toBeFalse();
        });

        it('identifies which statuses can be cancelled', function (): void {
            expect(DonationStatus::PENDING->canBeCancelled())->toBeTrue()
                ->and(DonationStatus::PROCESSING->canBeCancelled())->toBeTrue()
                ->and(DonationStatus::COMPLETED->canBeCancelled())->toBeFalse()
                ->and(DonationStatus::FAILED->canBeCancelled())->toBeFalse()
                ->and(DonationStatus::CANCELLED->canBeCancelled())->toBeFalse()
                ->and(DonationStatus::REFUNDED->canBeCancelled())->toBeFalse();
        });

        it('identifies which statuses can be refunded', function (): void {
            expect(DonationStatus::PENDING->canBeRefunded())->toBeFalse()
                ->and(DonationStatus::PROCESSING->canBeRefunded())->toBeFalse()
                ->and(DonationStatus::COMPLETED->canBeRefunded())->toBeTrue()
                ->and(DonationStatus::FAILED->canBeRefunded())->toBeFalse()
                ->and(DonationStatus::CANCELLED->canBeRefunded())->toBeFalse()
                ->and(DonationStatus::REFUNDED->canBeRefunded())->toBeFalse();
        });

        it('identifies successful statuses', function (): void {
            expect(DonationStatus::PENDING->isSuccessful())->toBeFalse()
                ->and(DonationStatus::PROCESSING->isSuccessful())->toBeFalse()
                ->and(DonationStatus::COMPLETED->isSuccessful())->toBeTrue()
                ->and(DonationStatus::FAILED->isSuccessful())->toBeFalse()
                ->and(DonationStatus::CANCELLED->isSuccessful())->toBeFalse()
                ->and(DonationStatus::REFUNDED->isSuccessful())->toBeFalse();
        });

        it('identifies failed statuses', function (): void {
            expect(DonationStatus::PENDING->isFailed())->toBeFalse()
                ->and(DonationStatus::PROCESSING->isFailed())->toBeFalse()
                ->and(DonationStatus::COMPLETED->isFailed())->toBeFalse()
                ->and(DonationStatus::FAILED->isFailed())->toBeTrue()
                ->and(DonationStatus::CANCELLED->isFailed())->toBeTrue()
                ->and(DonationStatus::REFUNDED->isFailed())->toBeFalse();
        });

        it('identifies final statuses', function (): void {
            expect(DonationStatus::PENDING->isFinal())->toBeFalse()
                ->and(DonationStatus::PROCESSING->isFinal())->toBeFalse()
                ->and(DonationStatus::COMPLETED->isFinal())->toBeTrue()
                ->and(DonationStatus::FAILED->isFinal())->toBeTrue()
                ->and(DonationStatus::CANCELLED->isFinal())->toBeTrue()
                ->and(DonationStatus::REFUNDED->isFinal())->toBeTrue();
        });

        it('identifies statuses that require user action', function (): void {
            expect(DonationStatus::PENDING->requiresUserAction())->toBeTrue()
                ->and(DonationStatus::PROCESSING->requiresUserAction())->toBeFalse()
                ->and(DonationStatus::COMPLETED->requiresUserAction())->toBeFalse()
                ->and(DonationStatus::FAILED->requiresUserAction())->toBeTrue()
                ->and(DonationStatus::CANCELLED->requiresUserAction())->toBeFalse()
                ->and(DonationStatus::REFUNDED->requiresUserAction())->toBeFalse();
        });

        it('identifies statuses that affect campaign totals', function (): void {
            expect(DonationStatus::PENDING->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::PROCESSING->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::COMPLETED->affectsCampaignTotal())->toBeTrue()
                ->and(DonationStatus::FAILED->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::CANCELLED->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::REFUNDED->affectsCampaignTotal())->toBeFalse();
        });
    });

    describe('Progress and Timeline', function (): void {
        it('returns correct progress percentages', function (): void {
            expect(DonationStatus::PENDING->getProgressPercentage())->toBe(10)
                ->and(DonationStatus::PROCESSING->getProgressPercentage())->toBe(50)
                ->and(DonationStatus::COMPLETED->getProgressPercentage())->toBe(100)
                ->and(DonationStatus::FAILED->getProgressPercentage())->toBe(0)
                ->and(DonationStatus::CANCELLED->getProgressPercentage())->toBe(0)
                ->and(DonationStatus::REFUNDED->getProgressPercentage())->toBe(0);
        });

        it('identifies statuses that show progress', function (): void {
            expect(DonationStatus::PENDING->showsProgress())->toBeTrue()
                ->and(DonationStatus::PROCESSING->showsProgress())->toBeTrue()
                ->and(DonationStatus::COMPLETED->showsProgress())->toBeTrue()
                ->and(DonationStatus::FAILED->showsProgress())->toBeFalse()
                ->and(DonationStatus::CANCELLED->showsProgress())->toBeFalse()
                ->and(DonationStatus::REFUNDED->showsProgress())->toBeFalse();
        });

        it('identifies statuses that have timeline', function (): void {
            expect(DonationStatus::PENDING->hasTimeline())->toBeTrue()
                ->and(DonationStatus::PROCESSING->hasTimeline())->toBeTrue()
                ->and(DonationStatus::COMPLETED->hasTimeline())->toBeTrue()
                ->and(DonationStatus::FAILED->hasTimeline())->toBeFalse()
                ->and(DonationStatus::CANCELLED->hasTimeline())->toBeFalse()
                ->and(DonationStatus::REFUNDED->hasTimeline())->toBeTrue();
        });

        it('returns correct sort priorities', function (): void {
            expect(DonationStatus::PROCESSING->getSortPriority())->toBe(6)
                ->and(DonationStatus::PENDING->getSortPriority())->toBe(5)
                ->and(DonationStatus::COMPLETED->getSortPriority())->toBe(4)
                ->and(DonationStatus::REFUNDED->getSortPriority())->toBe(3)
                ->and(DonationStatus::FAILED->getSortPriority())->toBe(2)
                ->and(DonationStatus::CANCELLED->getSortPriority())->toBe(1);
        });
    });

    describe('Time-based Rules', function (): void {
        it('validates time-based changes for pending status', function (): void {
            expect(DonationStatus::PENDING->canChangeWithinTime(30))->toBeTrue()
                ->and(DonationStatus::PENDING->canChangeWithinTime(60))->toBeTrue()
                ->and(DonationStatus::PENDING->canChangeWithinTime(90))->toBeFalse();
        });

        it('validates time-based changes for processing status', function (): void {
            expect(DonationStatus::PROCESSING->canChangeWithinTime(5))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canChangeWithinTime(10))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canChangeWithinTime(15))->toBeFalse();
        });

        it('validates time-based changes for final statuses', function (): void {
            expect(DonationStatus::COMPLETED->canChangeWithinTime(5))->toBeFalse()
                ->and(DonationStatus::FAILED->canChangeWithinTime(5))->toBeFalse()
                ->and(DonationStatus::CANCELLED->canChangeWithinTime(5))->toBeFalse()
                ->and(DonationStatus::REFUNDED->canChangeWithinTime(5))->toBeFalse();
        });
    });

    describe('Status Groups', function (): void {
        it('returns active statuses', function (): void {
            $activeStatuses = DonationStatus::getActiveStatuses();

            expect($activeStatuses)->toHaveCount(2)
                ->and($activeStatuses)->toContain(DonationStatus::PENDING)
                ->and($activeStatuses)->toContain(DonationStatus::PROCESSING);
        });

        it('returns final statuses', function (): void {
            $finalStatuses = DonationStatus::getFinalStatuses();

            expect($finalStatuses)->toHaveCount(4)
                ->and($finalStatuses)->toContain(DonationStatus::COMPLETED)
                ->and($finalStatuses)->toContain(DonationStatus::FAILED)
                ->and($finalStatuses)->toContain(DonationStatus::CANCELLED)
                ->and($finalStatuses)->toContain(DonationStatus::REFUNDED);
        });

        it('returns failed statuses', function (): void {
            $failedStatuses = DonationStatus::getFailedStatuses();

            expect($failedStatuses)->toHaveCount(2)
                ->and($failedStatuses)->toContain(DonationStatus::FAILED)
                ->and($failedStatuses)->toContain(DonationStatus::CANCELLED);
        });

        it('returns successful statuses', function (): void {
            $successfulStatuses = DonationStatus::getSuccessfulStatuses();

            expect($successfulStatuses)->toHaveCount(1)
                ->and($successfulStatuses)->toContain(DonationStatus::COMPLETED);
        });

        it('returns pending statuses', function (): void {
            $pendingStatuses = DonationStatus::getPendingStatuses();

            expect($pendingStatuses)->toHaveCount(2)
                ->and($pendingStatuses)->toContain(DonationStatus::PENDING)
                ->and($pendingStatuses)->toContain(DonationStatus::PROCESSING);
        });

        it('checks if status is one of given array', function (): void {
            $activeStatuses = [DonationStatus::PENDING, DonationStatus::PROCESSING];

            expect(DonationStatus::PENDING->isOneOf($activeStatuses))->toBeTrue()
                ->and(DonationStatus::PROCESSING->isOneOf($activeStatuses))->toBeTrue()
                ->and(DonationStatus::COMPLETED->isOneOf($activeStatuses))->toBeFalse();
        });
    });

    describe('State Transitions', function (): void {
        it('returns valid transitions for pending status', function (): void {
            $transitions = DonationStatus::PENDING->getValidTransitions();

            expect($transitions)->toHaveCount(3)
                ->and($transitions)->toContain(DonationStatus::PROCESSING)
                ->and($transitions)->toContain(DonationStatus::CANCELLED)
                ->and($transitions)->toContain(DonationStatus::FAILED);
        });

        it('returns valid transitions for processing status', function (): void {
            $transitions = DonationStatus::PROCESSING->getValidTransitions();

            expect($transitions)->toHaveCount(3)
                ->and($transitions)->toContain(DonationStatus::COMPLETED)
                ->and($transitions)->toContain(DonationStatus::FAILED)
                ->and($transitions)->toContain(DonationStatus::CANCELLED);
        });

        it('returns valid transitions for completed status', function (): void {
            $transitions = DonationStatus::COMPLETED->getValidTransitions();

            expect($transitions)->toHaveCount(1)
                ->and($transitions)->toContain(DonationStatus::REFUNDED);
        });

        it('returns no valid transitions for final statuses', function (): void {
            expect(DonationStatus::FAILED->getValidTransitions())->toBeEmpty()
                ->and(DonationStatus::CANCELLED->getValidTransitions())->toBeEmpty()
                ->and(DonationStatus::REFUNDED->getValidTransitions())->toBeEmpty();
        });

        it('validates transition rules comprehensively', function (): void {
            // Valid transitions from PENDING
            expect(DonationStatus::PENDING->canTransitionTo(DonationStatus::PROCESSING))->toBeTrue()
                ->and(DonationStatus::PENDING->canTransitionTo(DonationStatus::CANCELLED))->toBeTrue()
                ->and(DonationStatus::PENDING->canTransitionTo(DonationStatus::FAILED))->toBeTrue()
                ->and(DonationStatus::PENDING->canTransitionTo(DonationStatus::COMPLETED))->toBeFalse()
                ->and(DonationStatus::PENDING->canTransitionTo(DonationStatus::REFUNDED))->toBeFalse();

            // Valid transitions from PROCESSING
            expect(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::COMPLETED))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::FAILED))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::CANCELLED))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::PENDING))->toBeFalse()
                ->and(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::REFUNDED))->toBeFalse();

            // Valid transitions from COMPLETED
            expect(DonationStatus::COMPLETED->canTransitionTo(DonationStatus::REFUNDED))->toBeTrue()
                ->and(DonationStatus::COMPLETED->canTransitionTo(DonationStatus::PENDING))->toBeFalse()
                ->and(DonationStatus::COMPLETED->canTransitionTo(DonationStatus::PROCESSING))->toBeFalse()
                ->and(DonationStatus::COMPLETED->canTransitionTo(DonationStatus::FAILED))->toBeFalse()
                ->and(DonationStatus::COMPLETED->canTransitionTo(DonationStatus::CANCELLED))->toBeFalse();

            // No valid transitions from final statuses
            expect(DonationStatus::FAILED->canTransitionTo(DonationStatus::PENDING))->toBeFalse()
                ->and(DonationStatus::CANCELLED->canTransitionTo(DonationStatus::PENDING))->toBeFalse()
                ->and(DonationStatus::REFUNDED->canTransitionTo(DonationStatus::COMPLETED))->toBeFalse();
        });

        it('validates transitions using validateTransition method', function (): void {
            expect(DonationStatus::PENDING->validateTransition(DonationStatus::PROCESSING))->toBeTrue()
                ->and(DonationStatus::PENDING->validateTransition(DonationStatus::COMPLETED))->toBeFalse()
                ->and(DonationStatus::PROCESSING->validateTransition(DonationStatus::COMPLETED))->toBeTrue()
                ->and(DonationStatus::COMPLETED->validateTransition(DonationStatus::REFUNDED))->toBeTrue()
                ->and(DonationStatus::REFUNDED->validateTransition(DonationStatus::COMPLETED))->toBeFalse();
        });

        it('generates transition error messages', function (): void {
            $errorMessage = DonationStatus::PENDING->getTransitionErrorMessage(DonationStatus::COMPLETED);

            expect($errorMessage)->toContain('Cannot transition from Pending to Completed');

            $errorMessage2 = DonationStatus::FAILED->getTransitionErrorMessage(DonationStatus::PENDING);
            expect($errorMessage2)->toContain('Cannot transition from Failed to Pending');
        });

        it('ensures transition consistency with state validation rules', function (): void {
            // Statuses that can be processed should allow transitions to PROCESSING
            foreach (DonationStatus::cases() as $status) {
                if ($status->canBeProcessed()) {
                    expect($status->canTransitionTo(DonationStatus::PROCESSING))->toBeTrue();
                }
            }

            // Statuses that can be cancelled should allow transitions to CANCELLED
            foreach (DonationStatus::cases() as $status) {
                if ($status->canBeCancelled()) {
                    expect($status->canTransitionTo(DonationStatus::CANCELLED))->toBeTrue();
                }
            }

            // Statuses that can be refunded should allow transitions to REFUNDED
            foreach (DonationStatus::cases() as $status) {
                if ($status->canBeRefunded()) {
                    expect($status->canTransitionTo(DonationStatus::REFUNDED))->toBeTrue();
                }
            }
        });
    });

    describe('String Conversion', function (): void {
        it('creates status from string', function (): void {
            expect(DonationStatus::fromString('pending'))->toBe(DonationStatus::PENDING)
                ->and(DonationStatus::fromString('PROCESSING'))->toBe(DonationStatus::PROCESSING)
                ->and(DonationStatus::fromString(' completed '))->toBe(DonationStatus::COMPLETED);
        });

        it('throws exception for invalid string', function (): void {
            expect(fn () => DonationStatus::fromString('invalid'))
                ->toThrow(ValueError::class);
        });

        it('tries to create status from string safely', function (): void {
            expect(DonationStatus::tryFromString('pending'))->toBe(DonationStatus::PENDING)
                ->and(DonationStatus::tryFromString('invalid'))->toBeNull()
                ->and(DonationStatus::tryFromString(null))->toBeNull()
                ->and(DonationStatus::tryFromString(''))->toBeNull()
                ->and(DonationStatus::tryFromString('   '))->toBeNull();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles all enum values correctly', function (): void {
            foreach (DonationStatus::cases() as $status) {
                expect($status->getLabel())->toBeString()->not()->toBeEmpty()
                    ->and($status->getColor())->toBeString()->not()->toBeEmpty()
                    ->and($status->getIcon())->toBeString()->not()->toBeEmpty()
                    ->and($status->getDescription())->toBeString()->not()->toBeEmpty()
                    ->and($status->getTailwindBadgeClasses())->toBeString()->not()->toBeEmpty()
                    ->and($status->getTailwindDotClasses())->toBeString()->not()->toBeEmpty()
                    ->and($status->getProgressPercentage())->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
                    ->and($status->getSortPriority())->toBeInt()->toBeGreaterThan(0);
            }
        });

        it('maintains consistency in state validation', function (): void {
            foreach (DonationStatus::cases() as $status) {
                // If status is final, it should not be able to be processed
                if ($status->isFinal()) {
                    expect($status->canBeProcessed())->toBeFalse()
                        ->and($status->getValidTransitions())->toBeArray();
                }

                // Failed statuses should be final
                if ($status->isFailed()) {
                    expect($status->isFinal())->toBeTrue()
                        ->and($status->isSuccessful())->toBeFalse()
                        ->and($status->affectsCampaignTotal())->toBeFalse();
                }

                // Successful status should be final and affect campaign total
                if ($status->isSuccessful()) {
                    expect($status->isFinal())->toBeTrue()
                        ->and($status->affectsCampaignTotal())->toBeTrue()
                        ->and($status->isFailed())->toBeFalse();
                }

                // Active statuses should not be final
                if ($status->isOneOf(DonationStatus::getActiveStatuses())) {
                    expect($status->isFinal())->toBeFalse();
                }
            }
        });

        it('validates all status groups are mutually exclusive where appropriate', function (): void {
            $activeStatuses = DonationStatus::getActiveStatuses();
            $finalStatuses = DonationStatus::getFinalStatuses();
            $failedStatuses = DonationStatus::getFailedStatuses();
            $successfulStatuses = DonationStatus::getSuccessfulStatuses();
            $pendingStatuses = DonationStatus::getPendingStatuses();

            // Active and final should be mutually exclusive
            foreach ($activeStatuses as $activeStatus) {
                expect($finalStatuses)->not->toContain($activeStatus);
            }

            // Failed and successful should be mutually exclusive
            foreach ($failedStatuses as $failedStatus) {
                expect($successfulStatuses)->not->toContain($failedStatus);
            }

            // All statuses should be in either active or final
            foreach (DonationStatus::cases() as $status) {
                expect($status->isOneOf($activeStatuses) || $status->isOneOf($finalStatuses))->toBeTrue();
            }
        });

        it('validates time-based rules boundaries', function (): void {
            // Test edge cases for time limits
            expect(DonationStatus::PENDING->canChangeWithinTime(60))->toBeTrue()
                ->and(DonationStatus::PENDING->canChangeWithinTime(61))->toBeFalse()
                ->and(DonationStatus::PROCESSING->canChangeWithinTime(10))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canChangeWithinTime(11))->toBeFalse();

            // Zero and negative time should be handled properly
            expect(DonationStatus::PENDING->canChangeWithinTime(0))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canChangeWithinTime(0))->toBeTrue();
        });

        it('validates enum serialization consistency', function (): void {
            foreach (DonationStatus::cases() as $status) {
                // Test round-trip serialization
                $serialized = $status->value;
                $deserialized = DonationStatus::fromString($serialized);
                expect($deserialized)->toBe($status);

                // Test tryFromString consistency
                $tryDeserialized = DonationStatus::tryFromString($serialized);
                expect($tryDeserialized)->toBe($status);
            }
        });
    });

    describe('Business Rules Validation', function (): void {
        it('enforces correct donation lifecycle flow', function (): void {
            // New donation should start as PENDING
            expect(DonationStatus::PENDING->canBeProcessed())->toBeTrue();

            // PENDING can go to PROCESSING (payment gateway processing)
            expect(DonationStatus::PENDING->canTransitionTo(DonationStatus::PROCESSING))->toBeTrue();

            // PROCESSING can succeed or fail
            expect(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::COMPLETED))->toBeTrue()
                ->and(DonationStatus::PROCESSING->canTransitionTo(DonationStatus::FAILED))->toBeTrue();

            // Only COMPLETED donations can be refunded
            expect(DonationStatus::COMPLETED->canBeRefunded())->toBeTrue()
                ->and(DonationStatus::FAILED->canBeRefunded())->toBeFalse()
                ->and(DonationStatus::CANCELLED->canBeRefunded())->toBeFalse();

            // Only COMPLETED donations affect campaign totals
            expect(DonationStatus::COMPLETED->affectsCampaignTotal())->toBeTrue()
                ->and(DonationStatus::PENDING->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::PROCESSING->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::FAILED->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::CANCELLED->affectsCampaignTotal())->toBeFalse()
                ->and(DonationStatus::REFUNDED->affectsCampaignTotal())->toBeFalse();
        });

        it('validates cancellation rules', function (): void {
            // Only pending and processing donations can be cancelled
            expect(DonationStatus::PENDING->canBeCancelled())->toBeTrue()
                ->and(DonationStatus::PROCESSING->canBeCancelled())->toBeTrue()
                ->and(DonationStatus::COMPLETED->canBeCancelled())->toBeFalse()
                ->and(DonationStatus::FAILED->canBeCancelled())->toBeFalse()
                ->and(DonationStatus::REFUNDED->canBeCancelled())->toBeFalse();

            // Cancelled donations cannot transition anywhere
            expect(DonationStatus::CANCELLED->getValidTransitions())->toBeEmpty();
        });

        it('validates user action requirements', function (): void {
            // PENDING requires user action (e.g., complete payment)
            expect(DonationStatus::PENDING->requiresUserAction())->toBeTrue();

            // FAILED requires user action (e.g., retry payment)
            expect(DonationStatus::FAILED->requiresUserAction())->toBeTrue();

            // PROCESSING is automatic, no user action needed
            expect(DonationStatus::PROCESSING->requiresUserAction())->toBeFalse();

            // Final states don't require user action
            expect(DonationStatus::COMPLETED->requiresUserAction())->toBeFalse()
                ->and(DonationStatus::CANCELLED->requiresUserAction())->toBeFalse()
                ->and(DonationStatus::REFUNDED->requiresUserAction())->toBeFalse();
        });

        it('validates progress tracking rules', function (): void {
            // Progress should increase through successful flow
            expect(DonationStatus::PENDING->getProgressPercentage())->toBe(10)
                ->and(DonationStatus::PROCESSING->getProgressPercentage())->toBe(50)
                ->and(DonationStatus::COMPLETED->getProgressPercentage())->toBe(100);

            // Failed states should have 0 progress
            expect(DonationStatus::FAILED->getProgressPercentage())->toBe(0)
                ->and(DonationStatus::CANCELLED->getProgressPercentage())->toBe(0)
                ->and(DonationStatus::REFUNDED->getProgressPercentage())->toBe(0);

            // Only positive progress states should show progress
            foreach (DonationStatus::cases() as $status) {
                if ($status->getProgressPercentage() > 0) {
                    expect($status->showsProgress())->toBeTrue();
                } else {
                    expect($status->showsProgress())->toBeFalse();
                }
            }
        });

        it('validates sort priority logic', function (): void {
            // PROCESSING should have highest priority (most urgent)
            expect(DonationStatus::PROCESSING->getSortPriority())->toBe(6);

            // PENDING should be second highest (waiting for action)
            expect(DonationStatus::PENDING->getSortPriority())->toBe(5);

            // CANCELLED should have lowest priority (least important)
            expect(DonationStatus::CANCELLED->getSortPriority())->toBe(1);

            // Verify all priorities are unique
            $priorities = [];

            foreach (DonationStatus::cases() as $status) {
                $priority = $status->getSortPriority();
                expect($priorities)->not->toContain($priority, "Duplicate priority {$priority} found");
                $priorities[] = $priority;
            }
        });
    });
});

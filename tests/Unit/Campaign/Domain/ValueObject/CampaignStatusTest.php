<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\CampaignStatus;

describe('CampaignStatus Enum', function (): void {
    describe('Enum Values', function (): void {
        it('has all expected status values', function (): void {
            expect(CampaignStatus::DRAFT->value)->toBe('draft')
                ->and(CampaignStatus::PENDING_APPROVAL->value)->toBe('pending_approval')
                ->and(CampaignStatus::REJECTED->value)->toBe('rejected')
                ->and(CampaignStatus::ACTIVE->value)->toBe('active')
                ->and(CampaignStatus::PAUSED->value)->toBe('paused')
                ->and(CampaignStatus::COMPLETED->value)->toBe('completed')
                ->and(CampaignStatus::CANCELLED->value)->toBe('cancelled')
                ->and(CampaignStatus::EXPIRED->value)->toBe('expired');
        });

        it('can be created from string values', function (): void {
            expect(CampaignStatus::from('draft'))->toBe(CampaignStatus::DRAFT)
                ->and(CampaignStatus::from('pending_approval'))->toBe(CampaignStatus::PENDING_APPROVAL)
                ->and(CampaignStatus::from('rejected'))->toBe(CampaignStatus::REJECTED)
                ->and(CampaignStatus::from('active'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::from('paused'))->toBe(CampaignStatus::PAUSED)
                ->and(CampaignStatus::from('completed'))->toBe(CampaignStatus::COMPLETED)
                ->and(CampaignStatus::from('cancelled'))->toBe(CampaignStatus::CANCELLED)
                ->and(CampaignStatus::from('expired'))->toBe(CampaignStatus::EXPIRED);
        });
    });

    describe('Active Status Checking', function (): void {
        it('identifies active status correctly', function (): void {
            expect(CampaignStatus::ACTIVE->isActive())->toBeTrue();
        });

        it('identifies non-active statuses correctly', function (): void {
            expect(CampaignStatus::DRAFT->isActive())->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->isActive())->toBeFalse()
                ->and(CampaignStatus::REJECTED->isActive())->toBeFalse()
                ->and(CampaignStatus::PAUSED->isActive())->toBeFalse()
                ->and(CampaignStatus::COMPLETED->isActive())->toBeFalse()
                ->and(CampaignStatus::CANCELLED->isActive())->toBeFalse()
                ->and(CampaignStatus::EXPIRED->isActive())->toBeFalse();
        });
    });

    describe('Donation Acceptance', function (): void {
        it('allows donations only for active campaigns', function (): void {
            expect(CampaignStatus::ACTIVE->canAcceptDonations())->toBeTrue();
        });

        it('rejects donations for non-active campaigns', function (): void {
            expect(CampaignStatus::DRAFT->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::REJECTED->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::PAUSED->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::COMPLETED->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::CANCELLED->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::EXPIRED->canAcceptDonations())->toBeFalse();
        });
    });

    describe('Approval Status Detection', function (): void {
        it('identifies statuses requiring approval', function (): void {
            expect(CampaignStatus::PENDING_APPROVAL->requiresApproval())->toBeTrue();
        });

        it('identifies statuses not requiring approval', function (): void {
            expect(CampaignStatus::DRAFT->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::REJECTED->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::ACTIVE->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::PAUSED->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::COMPLETED->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::CANCELLED->requiresApproval())->toBeFalse()
                ->and(CampaignStatus::EXPIRED->requiresApproval())->toBeFalse();
        });

        it('identifies rejected statuses correctly', function (): void {
            expect(CampaignStatus::REJECTED->isRejected())->toBeTrue();
        });

        it('identifies non-rejected statuses correctly', function (): void {
            expect(CampaignStatus::DRAFT->isRejected())->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->isRejected())->toBeFalse()
                ->and(CampaignStatus::ACTIVE->isRejected())->toBeFalse()
                ->and(CampaignStatus::PAUSED->isRejected())->toBeFalse()
                ->and(CampaignStatus::COMPLETED->isRejected())->toBeFalse()
                ->and(CampaignStatus::CANCELLED->isRejected())->toBeFalse()
                ->and(CampaignStatus::EXPIRED->isRejected())->toBeFalse();
        });
    });

    describe('Final Status Detection', function (): void {
        it('identifies final statuses correctly', function (): void {
            expect(CampaignStatus::COMPLETED->isFinal())->toBeTrue()
                ->and(CampaignStatus::CANCELLED->isFinal())->toBeTrue()
                ->and(CampaignStatus::EXPIRED->isFinal())->toBeTrue();
        });

        it('identifies non-final statuses correctly', function (): void {
            expect(CampaignStatus::DRAFT->isFinal())->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->isFinal())->toBeFalse()
                ->and(CampaignStatus::REJECTED->isFinal())->toBeFalse()
                ->and(CampaignStatus::ACTIVE->isFinal())->toBeFalse()
                ->and(CampaignStatus::PAUSED->isFinal())->toBeFalse();
        });
    });

    describe('Status Transitions', function (): void {
        it('allows valid transitions from DRAFT', function (): void {
            expect(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::PENDING_APPROVAL))->toBeTrue()
                ->and(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue();
        });

        it('rejects invalid transitions from DRAFT', function (): void {
            expect(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse()
                ->and(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::COMPLETED))->toBeFalse()
                ->and(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::PAUSED))->toBeFalse()
                ->and(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::EXPIRED))->toBeFalse();
        });

        it('allows valid transitions from ACTIVE', function (): void {
            expect(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::PAUSED))->toBeTrue()
                ->and(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::COMPLETED))->toBeTrue()
                ->and(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
                ->and(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::EXPIRED))->toBeTrue();
        });

        it('rejects invalid transitions from ACTIVE', function (): void {
            expect(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse();
        });

        it('allows valid transitions from PENDING_APPROVAL', function (): void {
            expect(CampaignStatus::PENDING_APPROVAL->canTransitionTo(CampaignStatus::ACTIVE))->toBeTrue()
                ->and(CampaignStatus::PENDING_APPROVAL->canTransitionTo(CampaignStatus::REJECTED))->toBeTrue();
        });

        it('rejects invalid transitions from PENDING_APPROVAL', function (): void {
            expect(CampaignStatus::PENDING_APPROVAL->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->canTransitionTo(CampaignStatus::COMPLETED))->toBeFalse()
                ->and(CampaignStatus::PENDING_APPROVAL->canTransitionTo(CampaignStatus::CANCELLED))->toBeFalse();
        });

        it('allows valid transitions from REJECTED', function (): void {
            expect(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::DRAFT))->toBeTrue()
                ->and(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::PENDING_APPROVAL))->toBeTrue()
                ->and(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue();
        });

        it('rejects invalid transitions from REJECTED', function (): void {
            expect(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse()
                ->and(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::COMPLETED))->toBeFalse()
                ->and(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::PAUSED))->toBeFalse()
                ->and(CampaignStatus::REJECTED->canTransitionTo(CampaignStatus::EXPIRED))->toBeFalse();
        });

        it('allows valid transitions from PAUSED', function (): void {
            expect(CampaignStatus::PAUSED->canTransitionTo(CampaignStatus::ACTIVE))->toBeTrue()
                ->and(CampaignStatus::PAUSED->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
                ->and(CampaignStatus::PAUSED->canTransitionTo(CampaignStatus::EXPIRED))->toBeTrue();
        });

        it('rejects invalid transitions from PAUSED', function (): void {
            expect(CampaignStatus::PAUSED->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse()
                ->and(CampaignStatus::PAUSED->canTransitionTo(CampaignStatus::COMPLETED))->toBeFalse();
        });

        it('rejects all transitions from final statuses', function (): void {
            $finalStatuses = [CampaignStatus::COMPLETED, CampaignStatus::CANCELLED, CampaignStatus::EXPIRED];
            $allStatuses = [
                CampaignStatus::DRAFT, CampaignStatus::PENDING_APPROVAL, CampaignStatus::REJECTED,
                CampaignStatus::ACTIVE, CampaignStatus::PAUSED,
                CampaignStatus::COMPLETED, CampaignStatus::CANCELLED, CampaignStatus::EXPIRED,
            ];

            foreach ($finalStatuses as $finalStatus) {
                foreach ($allStatuses as $targetStatus) {
                    expect($finalStatus->canTransitionTo($targetStatus))->toBeFalse();
                }
            }
        });
    });

    describe('Display Properties', function (): void {
        it('returns correct labels', function (): void {
            expect(CampaignStatus::DRAFT->getLabel())->toBe('Draft')
                ->and(CampaignStatus::PENDING_APPROVAL->getLabel())->toBe('Pending Approval')
                ->and(CampaignStatus::REJECTED->getLabel())->toBe('Rejected')
                ->and(CampaignStatus::ACTIVE->getLabel())->toBe('Active')
                ->and(CampaignStatus::PAUSED->getLabel())->toBe('Paused')
                ->and(CampaignStatus::COMPLETED->getLabel())->toBe('Completed')
                ->and(CampaignStatus::CANCELLED->getLabel())->toBe('Cancelled')
                ->and(CampaignStatus::EXPIRED->getLabel())->toBe('Expired');
        });

        it('returns correct colors', function (): void {
            expect(CampaignStatus::DRAFT->getColor())->toBe('secondary')
                ->and(CampaignStatus::PENDING_APPROVAL->getColor())->toBe('info')
                ->and(CampaignStatus::REJECTED->getColor())->toBe('danger')
                ->and(CampaignStatus::ACTIVE->getColor())->toBe('success')
                ->and(CampaignStatus::PAUSED->getColor())->toBe('warning')
                ->and(CampaignStatus::COMPLETED->getColor())->toBe('primary')
                ->and(CampaignStatus::CANCELLED->getColor())->toBe('danger')
                ->and(CampaignStatus::EXPIRED->getColor())->toBe('warning');
        });

        it('returns appropriate descriptions', function (): void {
            expect(CampaignStatus::DRAFT->getDescription())
                ->toBe('Campaign is not yet published and is not visible to donors');
            expect(CampaignStatus::PENDING_APPROVAL->getDescription())
                ->toBe('Campaign is awaiting approval from administrators');
            expect(CampaignStatus::REJECTED->getDescription())
                ->toBe('Campaign was rejected and needs revisions before resubmission');
            expect(CampaignStatus::ACTIVE->getDescription())
                ->toBe('Campaign is live and accepting donations from supporters');
            expect(CampaignStatus::PAUSED->getDescription())
                ->toBe('Campaign is temporarily paused and not accepting donations');
            expect(CampaignStatus::COMPLETED->getDescription())
                ->toBe('Campaign has successfully reached its goal');
            expect(CampaignStatus::CANCELLED->getDescription())
                ->toBe('Campaign has been cancelled by the organizer');
            expect(CampaignStatus::EXPIRED->getDescription())
                ->toBe('Campaign has expired and is no longer accepting donations');
        });
    });

    describe('Status Collection Methods', function (): void {
        it('returns active statuses collection', function (): void {
            $activeStatuses = CampaignStatus::getActiveStatuses();

            expect($activeStatuses)->toBe([CampaignStatus::ACTIVE]);
        });

        it('returns final statuses collection', function (): void {
            $finalStatuses = CampaignStatus::getFinalStatuses();

            expect($finalStatuses)->toBe([
                CampaignStatus::COMPLETED,
                CampaignStatus::CANCELLED,
                CampaignStatus::EXPIRED,
            ]);
        });

        it('returns donation accepting statuses collection', function (): void {
            $donationStatuses = CampaignStatus::getDonationAcceptingStatuses();

            expect($donationStatuses)->toBe([CampaignStatus::ACTIVE]);
        });
    });

    describe('String Conversion Methods', function (): void {
        it('creates status from string with fromString', function (): void {
            expect(CampaignStatus::fromString('ACTIVE'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::fromString('active'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::fromString(' ACTIVE '))->toBe(CampaignStatus::ACTIVE);
        });

        it('throws exception for invalid string with fromString', function (): void {
            expect(fn () => CampaignStatus::fromString('invalid'))
                ->toThrow(ValueError::class);
        });

        it('creates status from string with tryFromString', function (): void {
            expect(CampaignStatus::tryFromString('ACTIVE'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::tryFromString('active'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::tryFromString(' ACTIVE '))->toBe(CampaignStatus::ACTIVE);
        });

        it('returns null for invalid string with tryFromString', function (): void {
            expect(CampaignStatus::tryFromString('invalid'))->toBeNull()
                ->and(CampaignStatus::tryFromString(''))->toBeNull()
                ->and(CampaignStatus::tryFromString('   '))->toBeNull()
                ->and(CampaignStatus::tryFromString(null))->toBeNull();
        });
    });

    describe('Status Checking with Collections', function (): void {
        it('checks if status is one of provided statuses', function (): void {
            $activeStatuses = [CampaignStatus::ACTIVE, CampaignStatus::PAUSED];

            expect(CampaignStatus::ACTIVE->isOneOf($activeStatuses))->toBeTrue()
                ->and(CampaignStatus::PAUSED->isOneOf($activeStatuses))->toBeTrue()
                ->and(CampaignStatus::DRAFT->isOneOf($activeStatuses))->toBeFalse()
                ->and(CampaignStatus::COMPLETED->isOneOf($activeStatuses))->toBeFalse();
        });

        it('returns false for empty status array', function (): void {
            expect(CampaignStatus::ACTIVE->isOneOf([]))->toBeFalse();
        });
    });

    describe('Valid Transitions', function (): void {
        it('returns valid transitions for each status', function (): void {
            expect(CampaignStatus::DRAFT->getValidTransitions())
                ->toBe([CampaignStatus::PENDING_APPROVAL, CampaignStatus::CANCELLED]);

            expect(CampaignStatus::PENDING_APPROVAL->getValidTransitions())
                ->toBe([CampaignStatus::ACTIVE, CampaignStatus::REJECTED]);

            expect(CampaignStatus::REJECTED->getValidTransitions())
                ->toBe([CampaignStatus::DRAFT, CampaignStatus::PENDING_APPROVAL, CampaignStatus::CANCELLED]);

            expect(CampaignStatus::ACTIVE->getValidTransitions())
                ->toBe([
                    CampaignStatus::PAUSED,
                    CampaignStatus::COMPLETED,
                    CampaignStatus::CANCELLED,
                    CampaignStatus::EXPIRED,
                ]);

            expect(CampaignStatus::PAUSED->getValidTransitions())
                ->toBe([
                    CampaignStatus::ACTIVE,
                    CampaignStatus::CANCELLED,
                    CampaignStatus::EXPIRED,
                ]);
        });

        it('returns empty array for final statuses', function (): void {
            expect(CampaignStatus::COMPLETED->getValidTransitions())->toBe([])
                ->and(CampaignStatus::CANCELLED->getValidTransitions())->toBe([])
                ->and(CampaignStatus::EXPIRED->getValidTransitions())->toBe([]);
        });
    });

    describe('Transition Error Messages', function (): void {
        it('returns appropriate error messages for invalid transitions', function (): void {
            $message = CampaignStatus::DRAFT->getTransitionErrorMessage(CampaignStatus::COMPLETED);

            expect($message)->toBe('Cannot transition from Draft to Completed status');
        });

        it('generates error messages for all status combinations', function (): void {
            $message = CampaignStatus::ACTIVE->getTransitionErrorMessage(CampaignStatus::DRAFT);

            expect($message)->toBe('Cannot transition from Active to Draft status');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles self-transitions correctly', function (): void {
            expect(CampaignStatus::ACTIVE->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse()
                ->and(CampaignStatus::DRAFT->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse();
        });

        it('maintains immutability', function (): void {
            $status = CampaignStatus::ACTIVE;
            $newStatus = CampaignStatus::COMPLETED;

            // Ensure no modification of original
            expect($status)->toBe(CampaignStatus::ACTIVE)
                ->and($newStatus)->toBe(CampaignStatus::COMPLETED)
                ->and($status->canTransitionTo($newStatus))->toBeTrue();
        });
    });
});

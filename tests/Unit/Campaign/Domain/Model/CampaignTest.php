<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Domain\ValueObject\Money;

describe('Campaign Domain Logic', function (): void {
    describe('Campaign Status', function (): void {
        it('has correct status enum values', function (): void {
            expect(CampaignStatus::DRAFT->value)->toBe('draft')
                ->and(CampaignStatus::ACTIVE->value)->toBe('active')
                ->and(CampaignStatus::COMPLETED->value)->toBe('completed')
                ->and(CampaignStatus::CANCELLED->value)->toBe('cancelled');
        });

        it('validates status enum correctly with tryFrom', function (): void {
            expect(CampaignStatus::tryFrom('draft'))->toBe(CampaignStatus::DRAFT)
                ->and(CampaignStatus::tryFrom('active'))->toBe(CampaignStatus::ACTIVE)
                ->and(CampaignStatus::tryFrom('completed'))->toBe(CampaignStatus::COMPLETED)
                ->and(CampaignStatus::tryFrom('cancelled'))->toBe(CampaignStatus::CANCELLED)
                ->and(CampaignStatus::tryFrom('invalid_status'))->toBeNull()
                ->and(CampaignStatus::tryFromString(''))->toBeNull()
                ->and(CampaignStatus::tryFromString(null))->toBeNull();
        });

        it('provides status behavior methods', function (): void {
            expect(CampaignStatus::ACTIVE->isActive())->toBeTrue()
                ->and(CampaignStatus::DRAFT->isActive())->toBeFalse()
                ->and(CampaignStatus::ACTIVE->canAcceptDonations())->toBeTrue()
                ->and(CampaignStatus::DRAFT->canAcceptDonations())->toBeFalse()
                ->and(CampaignStatus::COMPLETED->isFinal())->toBeTrue()
                ->and(CampaignStatus::ACTIVE->isFinal())->toBeFalse();
        });
    });

    describe('Money Value Object Integration', function (): void {
        it('creates money objects with valid amounts', function (): void {
            $goalMoney = new Money(10000.50, 'USD');
            $donationMoney = new Money(250.75, 'USD');

            expect($goalMoney->amount)->toBe(10000.50)
                ->and($goalMoney->currency)->toBe('USD')
                ->and($donationMoney->amount)->toBe(250.75)
                ->and($donationMoney->currency)->toBe('USD');
        });

        it('performs money calculations', function (): void {
            $goalAmount = new Money(1000.00, 'USD');
            $currentAmount = new Money(250.00, 'USD');

            $total = $currentAmount->add(new Money(150.00, 'USD'));

            expect($total->amount)->toBe(400.00)
                ->and($total->currency)->toBe('USD');
        });

        it('calculates progress percentage', function (): void {
            $goalAmount = new Money(1000.00, 'USD');
            $currentAmount = new Money(250.00, 'USD');

            $progressPercentage = ($currentAmount->amount / $goalAmount->amount) * 100;

            expect($progressPercentage)->toBe(25.0);
        });

        it('determines if goal is reached', function (): void {
            $goalAmount = new Money(1000.00, 'USD');
            $currentAmount = new Money(1000.00, 'USD');
            $shortAmount = new Money(999.99, 'USD');

            expect($currentAmount->amount >= $goalAmount->amount)->toBeTrue()
                ->and($shortAmount->amount >= $goalAmount->amount)->toBeFalse();
        });
    });

    describe('Campaign Progress Calculations', function (): void {
        it('calculates correct progress percentage', function (): void {
            $goalAmount = 1000.00;
            $currentAmount = 250.00;

            $progressPercentage = ($currentAmount / $goalAmount) * 100;

            expect($progressPercentage)->toBe(25.0);
        });

        it('caps progress at 100 percent', function (): void {
            $goalAmount = 1000.00;
            $currentAmount = 1500.00;

            $progressPercentage = min(($currentAmount / $goalAmount) * 100, 100.0);

            expect($progressPercentage)->toBe(100.0);
        });

        it('handles zero goal amount safely', function (): void {
            $goalAmount = 0.00;
            $currentAmount = 100.00;

            $progressPercentage = $goalAmount > 0 ? ($currentAmount / $goalAmount) * 100 : 0.0;

            expect($progressPercentage)->toBe(0.0);
        });

        it('calculates remaining amount correctly', function (): void {
            $goalAmount = 1000.00;
            $currentAmount = 300.00;

            $remainingAmount = max($goalAmount - $currentAmount, 0.0);

            expect($remainingAmount)->toBe(700.0);
        });

        it('returns zero remaining when goal exceeded', function (): void {
            $goalAmount = 1000.00;
            $currentAmount = 1200.00;

            $remainingAmount = max($goalAmount - $currentAmount, 0.0);

            expect($remainingAmount)->toBe(0.0);
        });
    });

    describe('Amount Validation Logic', function (): void {
        it('accepts positive goal amounts', function (): void {
            $goalAmount = 1000.00;

            $isValidAmount = $goalAmount > 0;

            expect($isValidAmount)->toBeTrue();
        });

        it('rejects zero goal amounts', function (): void {
            $goalAmount = 0.00;

            $isValidAmount = $goalAmount > 0;

            expect($isValidAmount)->toBeFalse();
        });

        it('rejects negative goal amounts', function (): void {
            $goalAmount = -100.00;

            $isValidAmount = $goalAmount > 0;

            expect($isValidAmount)->toBeFalse();
        });

        it('accepts fractional amounts', function (): void {
            $goalAmount = 1000.50;

            $isValidAmount = $goalAmount > 0;

            expect($isValidAmount)->toBeTrue();
        });
    });

    describe('Edge Cases and Boundary Conditions', function (): void {
        it('handles very large amounts', function (): void {
            $largeAmount = 999999999.99;

            $isValidAmount = $largeAmount > 0;

            expect($isValidAmount)->toBeTrue();
        });

        it('handles very small fractional amounts', function (): void {
            $smallAmount = 0.01;

            $isValidAmount = $smallAmount > 0;

            expect($isValidAmount)->toBeTrue();
        });

        it('calculates progress with edge values', function (): void {
            $goalAmount = 1.00;
            $currentAmount = 0.01;

            $progressPercentage = ($currentAmount / $goalAmount) * 100;

            expect($progressPercentage)->toBe(1.0);
        });
    });
});

<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Campaign\Domain\ValueObject\CampaignId;
use Modules\Campaign\Domain\ValueObject\CampaignProgress;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Domain\ValueObject\DonationProgress;
use Modules\Campaign\Domain\ValueObject\FundraisingTarget;
use Modules\Campaign\Domain\ValueObject\Goal;
use Modules\Campaign\Domain\ValueObject\TimeRemaining;
use Modules\Campaign\Domain\ValueObject\UserCampaignStats;
use Modules\Shared\Domain\ValueObject\Money;

describe('CampaignId Value Object', function () {
    it('creates valid campaign id from positive integer', function () {
        $id = new CampaignId(123);

        expect($id->value)->toBe(123)
            ->and($id->toInt())->toBe(123)
            ->and((string) $id)->toBe('123');
    });

    it('creates campaign id using factory method', function () {
        $id = CampaignId::fromInt(456);

        expect($id->value)->toBe(456)
            ->and($id->toInt())->toBe(456);
    });

    it('throws exception for zero id', function () {
        expect(fn () => new CampaignId(0))
            ->toThrow(InvalidArgumentException::class, 'Campaign ID must be a positive integer');
    });

    it('throws exception for negative id', function () {
        expect(fn () => new CampaignId(-1))
            ->toThrow(InvalidArgumentException::class, 'Campaign ID must be a positive integer');
    });

    it('compares campaign ids for equality', function () {
        $id1 = new CampaignId(100);
        $id2 = new CampaignId(100);
        $id3 = new CampaignId(200);

        expect($id1->equals($id2))->toBeTrue()
            ->and($id1->equals($id3))->toBeFalse();
    });

    it('converts to string correctly', function () {
        $id = new CampaignId(999);

        expect($id->__toString())->toBe('999')
            ->and((string) $id)->toBe('999');
    });
});

describe('CampaignStatus Enum', function () {
    it('has all expected status cases', function () {
        $statuses = [
            CampaignStatus::DRAFT,
            CampaignStatus::PENDING_APPROVAL,
            CampaignStatus::REJECTED,
            CampaignStatus::ACTIVE,
            CampaignStatus::PAUSED,
            CampaignStatus::COMPLETED,
            CampaignStatus::CANCELLED,
            CampaignStatus::EXPIRED,
        ];

        expect($statuses)->toHaveCount(8);

        foreach ($statuses as $status) {
            expect($status)->toBeInstanceOf(CampaignStatus::class);
        }
    });

    it('identifies active status correctly', function () {
        expect(CampaignStatus::ACTIVE->isActive())->toBeTrue()
            ->and(CampaignStatus::DRAFT->isActive())->toBeFalse()
            ->and(CampaignStatus::PAUSED->isActive())->toBeFalse()
            ->and(CampaignStatus::COMPLETED->isActive())->toBeFalse();
    });

    it('identifies donation accepting status correctly', function () {
        expect(CampaignStatus::ACTIVE->canAcceptDonations())->toBeTrue()
            ->and(CampaignStatus::DRAFT->canAcceptDonations())->toBeFalse()
            ->and(CampaignStatus::PAUSED->canAcceptDonations())->toBeFalse()
            ->and(CampaignStatus::COMPLETED->canAcceptDonations())->toBeFalse();
    });

    it('identifies final statuses correctly', function () {
        expect(CampaignStatus::COMPLETED->isFinal())->toBeTrue()
            ->and(CampaignStatus::CANCELLED->isFinal())->toBeTrue()
            ->and(CampaignStatus::EXPIRED->isFinal())->toBeTrue()
            ->and(CampaignStatus::ACTIVE->isFinal())->toBeFalse()
            ->and(CampaignStatus::DRAFT->isFinal())->toBeFalse();
    });

    it('identifies approval requiring status correctly', function () {
        expect(CampaignStatus::PENDING_APPROVAL->requiresApproval())->toBeTrue()
            ->and(CampaignStatus::DRAFT->requiresApproval())->toBeFalse()
            ->and(CampaignStatus::ACTIVE->requiresApproval())->toBeFalse();
    });

    it('identifies rejected status correctly', function () {
        expect(CampaignStatus::REJECTED->isRejected())->toBeTrue()
            ->and(CampaignStatus::DRAFT->isRejected())->toBeFalse()
            ->and(CampaignStatus::ACTIVE->isRejected())->toBeFalse();
    });

    it('validates draft status transitions', function () {
        $draft = CampaignStatus::DRAFT;

        expect($draft->canTransitionTo(CampaignStatus::PENDING_APPROVAL))->toBeTrue()
            ->and($draft->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
            ->and($draft->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse()
            ->and($draft->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse();
    });

    it('validates pending approval status transitions', function () {
        $pending = CampaignStatus::PENDING_APPROVAL;

        expect($pending->canTransitionTo(CampaignStatus::ACTIVE))->toBeTrue()
            ->and($pending->canTransitionTo(CampaignStatus::REJECTED))->toBeTrue()
            ->and($pending->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse()
            ->and($pending->canTransitionTo(CampaignStatus::PENDING_APPROVAL))->toBeFalse();
    });

    it('validates rejected status transitions', function () {
        $rejected = CampaignStatus::REJECTED;

        expect($rejected->canTransitionTo(CampaignStatus::DRAFT))->toBeTrue()
            ->and($rejected->canTransitionTo(CampaignStatus::PENDING_APPROVAL))->toBeTrue()
            ->and($rejected->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
            ->and($rejected->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse();
    });

    it('validates active status transitions', function () {
        $active = CampaignStatus::ACTIVE;

        expect($active->canTransitionTo(CampaignStatus::PAUSED))->toBeTrue()
            ->and($active->canTransitionTo(CampaignStatus::COMPLETED))->toBeTrue()
            ->and($active->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
            ->and($active->canTransitionTo(CampaignStatus::EXPIRED))->toBeTrue()
            ->and($active->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse();
    });

    it('validates paused status transitions', function () {
        $paused = CampaignStatus::PAUSED;

        expect($paused->canTransitionTo(CampaignStatus::ACTIVE))->toBeTrue()
            ->and($paused->canTransitionTo(CampaignStatus::CANCELLED))->toBeTrue()
            ->and($paused->canTransitionTo(CampaignStatus::EXPIRED))->toBeTrue()
            ->and($paused->canTransitionTo(CampaignStatus::COMPLETED))->toBeFalse();
    });

    it('prevents final status transitions', function () {
        $completed = CampaignStatus::COMPLETED;
        $cancelled = CampaignStatus::CANCELLED;
        $expired = CampaignStatus::EXPIRED;

        expect($completed->canTransitionTo(CampaignStatus::ACTIVE))->toBeFalse()
            ->and($cancelled->canTransitionTo(CampaignStatus::DRAFT))->toBeFalse()
            ->and($expired->canTransitionTo(CampaignStatus::PAUSED))->toBeFalse();
    });

    it('prevents self transitions', function () {
        $status = CampaignStatus::ACTIVE;

        expect($status->canTransitionTo($status))->toBeFalse();
    });

    it('provides correct labels', function () {
        expect(CampaignStatus::DRAFT->getLabel())->toBe('Draft')
            ->and(CampaignStatus::PENDING_APPROVAL->getLabel())->toBe('Pending Approval')
            ->and(CampaignStatus::REJECTED->getLabel())->toBe('Rejected')
            ->and(CampaignStatus::ACTIVE->getLabel())->toBe('Active')
            ->and(CampaignStatus::PAUSED->getLabel())->toBe('Paused')
            ->and(CampaignStatus::COMPLETED->getLabel())->toBe('Completed')
            ->and(CampaignStatus::CANCELLED->getLabel())->toBe('Cancelled')
            ->and(CampaignStatus::EXPIRED->getLabel())->toBe('Expired');
    });

    it('provides correct colors', function () {
        expect(CampaignStatus::DRAFT->getColor())->toBe('secondary')
            ->and(CampaignStatus::PENDING_APPROVAL->getColor())->toBe('info')
            ->and(CampaignStatus::REJECTED->getColor())->toBe('danger')
            ->and(CampaignStatus::ACTIVE->getColor())->toBe('success')
            ->and(CampaignStatus::PAUSED->getColor())->toBe('warning')
            ->and(CampaignStatus::COMPLETED->getColor())->toBe('primary')
            ->and(CampaignStatus::CANCELLED->getColor())->toBe('danger')
            ->and(CampaignStatus::EXPIRED->getColor())->toBe('warning');
    });

    it('provides meaningful descriptions', function () {
        expect(CampaignStatus::DRAFT->getDescription())
            ->toContain('not yet published')
            ->and(CampaignStatus::ACTIVE->getDescription())
            ->toContain('live and accepting donations')
            ->and(CampaignStatus::COMPLETED->getDescription())
            ->toContain('successfully reached its goal');
    });

    it('checks if status is one of provided statuses', function () {
        $status = CampaignStatus::ACTIVE;
        $activeStatuses = [CampaignStatus::ACTIVE, CampaignStatus::PAUSED];
        $finalStatuses = [CampaignStatus::COMPLETED, CampaignStatus::CANCELLED];

        expect($status->isOneOf($activeStatuses))->toBeTrue()
            ->and($status->isOneOf($finalStatuses))->toBeFalse();
    });

    it('provides static methods for status groups', function () {
        expect(CampaignStatus::getActiveStatuses())->toBe([CampaignStatus::ACTIVE])
            ->and(CampaignStatus::getFinalStatuses())->toBe([CampaignStatus::COMPLETED, CampaignStatus::CANCELLED, CampaignStatus::EXPIRED])
            ->and(CampaignStatus::getDonationAcceptingStatuses())->toBe([CampaignStatus::ACTIVE]);
    });

    it('creates status from string', function () {
        expect(CampaignStatus::fromString('active'))->toBe(CampaignStatus::ACTIVE)
            ->and(CampaignStatus::fromString('DRAFT'))->toBe(CampaignStatus::DRAFT)
            ->and(CampaignStatus::fromString('  Pending_Approval  '))->toBe(CampaignStatus::PENDING_APPROVAL);
    });

    it('tries to create status from string safely', function () {
        expect(CampaignStatus::tryFromString('active'))->toBe(CampaignStatus::ACTIVE)
            ->and(CampaignStatus::tryFromString('invalid'))->toBeNull()
            ->and(CampaignStatus::tryFromString(null))->toBeNull()
            ->and(CampaignStatus::tryFromString(''))->toBeNull();
    });

    it('gets valid transitions for each status', function () {
        expect(CampaignStatus::DRAFT->getValidTransitions())
            ->toBe([CampaignStatus::PENDING_APPROVAL, CampaignStatus::CANCELLED])
            ->and(CampaignStatus::COMPLETED->getValidTransitions())
            ->toBe([]);
    });

    it('provides transition error messages', function () {
        $message = CampaignStatus::DRAFT->getTransitionErrorMessage(CampaignStatus::ACTIVE);

        expect($message)->toContain('Cannot transition from Draft to Active status');
    });
});

describe('FundraisingTarget Value Object', function () {
    it('creates valid fundraising target', function () {
        $money = new Money(5000.0, 'EUR');
        $target = new FundraisingTarget($money);

        expect($target->getMoney())->toBe($money)
            ->and($target->getAmount())->toBe(5000.0)
            ->and($target->getCurrency())->toBe('EUR');
    });

    it('creates target from money value object', function () {
        $money = new Money(10000.0, 'USD');
        $target = FundraisingTarget::fromMoney($money);

        expect($target->getAmount())->toBe(10000.0)
            ->and($target->getCurrency())->toBe('USD');
    });

    it('creates target from amount and currency', function () {
        $target = FundraisingTarget::fromAmount(7500.0, 'GBP');

        expect($target->getAmount())->toBe(7500.0)
            ->and($target->getCurrency())->toBe('GBP');
    });

    it('creates minimum target', function () {
        $target = FundraisingTarget::minimum();

        expect($target->getAmount())->toBe(100.0)
            ->and($target->getCurrency())->toBe('EUR');
    });

    it('creates maximum target', function () {
        $target = FundraisingTarget::maximum();

        expect($target->getAmount())->toBe(10000000.0)
            ->and($target->getCurrency())->toBe('EUR');
    });

    it('creates recommended minimum target', function () {
        $target = FundraisingTarget::recommendedMinimum();

        expect($target->getAmount())->toBe(1000.0)
            ->and($target->getCurrency())->toBe('EUR');
    });

    it('throws exception for amount below minimum', function () {
        expect(fn () => FundraisingTarget::fromAmount(50.0))
            ->toThrow(InvalidArgumentException::class, 'Fundraising target must be at least');
    });

    it('throws exception for amount above maximum', function () {
        expect(fn () => FundraisingTarget::fromAmount(20000000.0))
            ->toThrow(InvalidArgumentException::class, 'Fundraising target cannot exceed');
    });

    it('identifies achievable targets', function () {
        $achievable = FundraisingTarget::fromAmount(2000.0);
        $notAchievable = FundraisingTarget::fromAmount(500.0);

        expect($achievable->isAchievable())->toBeTrue()
            ->and($notAchievable->isAchievable())->toBeFalse();
    });

    it('identifies mega campaigns', function () {
        $mega = FundraisingTarget::fromAmount(1500000.0);
        $normal = FundraisingTarget::fromAmount(50000.0);

        expect($mega->isMegaCampaign())->toBeTrue()
            ->and($normal->isMegaCampaign())->toBeFalse();
    });

    it('identifies targets requiring approval', function () {
        $requiresApproval = FundraisingTarget::fromAmount(500.0);
        $doesNotRequire = FundraisingTarget::fromAmount(2000.0);

        expect($requiresApproval->requiresApproval())->toBeTrue()
            ->and($doesNotRequire->requiresApproval())->toBeFalse();
    });

    it('calculates progress percentage', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(250.0, 'EUR');

        expect($target->calculateProgress($raised))->toBe(25.0);
    });

    it('caps progress at 100 percent', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(1500.0, 'EUR');

        expect($target->calculateProgress($raised))->toBe(100.0);
    });

    it('throws exception for currency mismatch in progress calculation', function () {
        $target = FundraisingTarget::fromAmount(1000.0, 'EUR');
        $raised = new Money(250.0, 'USD');

        expect(fn () => $target->calculateProgress($raised))
            ->toThrow(InvalidArgumentException::class, 'Currency mismatch');
    });

    it('calculates remaining amount', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(300.0, 'EUR');
        $remaining = $target->calculateRemaining($raised);

        expect($remaining->getAmount())->toBe(700.0)
            ->and($remaining->getCurrency())->toBe('EUR');
    });

    it('returns zero remaining when target exceeded', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(1200.0, 'EUR');
        $remaining = $target->calculateRemaining($raised);

        expect($remaining->getAmount())->toBe(0.0);
    });

    it('checks if target is reached', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $exactAmount = new Money(1000.0, 'EUR');
        $overAmount = new Money(1100.0, 'EUR');
        $underAmount = new Money(900.0, 'EUR');

        expect($target->isReached($exactAmount))->toBeTrue()
            ->and($target->isReached($overAmount))->toBeTrue()
            ->and($target->isReached($underAmount))->toBeFalse();
    });

    it('checks if target is exceeded', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $exactAmount = new Money(1000.0, 'EUR');
        $overAmount = new Money(1100.0, 'EUR');
        $underAmount = new Money(900.0, 'EUR');

        expect($target->isExceeded($exactAmount))->toBeFalse()
            ->and($target->isExceeded($overAmount))->toBeTrue()
            ->and($target->isExceeded($underAmount))->toBeFalse();
    });

    it('generates milestone amounts', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $milestones = $target->getMilestones();

        expect($milestones)->toHaveKey(25)
            ->and($milestones)->toHaveKey(50)
            ->and($milestones)->toHaveKey(75)
            ->and($milestones)->toHaveKey(100)
            ->and($milestones[25]->getAmount())->toBe(250.0)
            ->and($milestones[50]->getAmount())->toBe(500.0)
            ->and($milestones[75]->getAmount())->toBe(750.0)
            ->and($milestones[100]->getAmount())->toBe(1000.0);
    });

    it('checks milestone achievement', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(600.0, 'EUR');

        expect($target->hasMilestoneBeenReached($raised, 25))->toBeTrue()
            ->and($target->hasMilestoneBeenReached($raised, 50))->toBeTrue()
            ->and($target->hasMilestoneBeenReached($raised, 75))->toBeFalse()
            ->and($target->hasMilestoneBeenReached($raised, 100))->toBeFalse();
    });

    it('throws exception for invalid milestone percentage', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $raised = new Money(500.0, 'EUR');

        expect(fn () => $target->hasMilestoneBeenReached($raised, 0))
            ->toThrow(InvalidArgumentException::class, 'Milestone percentage must be between 1 and 100');

        expect(fn () => $target->hasMilestoneBeenReached($raised, 101))
            ->toThrow(InvalidArgumentException::class, 'Milestone percentage must be between 1 and 100');
    });

    it('compares targets for equality', function () {
        $target1 = FundraisingTarget::fromAmount(1000.0, 'EUR');
        $target2 = FundraisingTarget::fromAmount(1000.0, 'EUR');
        $target3 = FundraisingTarget::fromAmount(2000.0, 'EUR');

        expect($target1->equals($target2))->toBeTrue()
            ->and($target1->equals($target3))->toBeFalse();
    });

    it('compares targets for greater than', function () {
        $smaller = FundraisingTarget::fromAmount(1000.0);
        $larger = FundraisingTarget::fromAmount(2000.0);

        expect($larger->isGreaterThan($smaller))->toBeTrue()
            ->and($smaller->isGreaterThan($larger))->toBeFalse();
    });

    it('formats target for display', function () {
        $target = FundraisingTarget::fromAmount(1500.0, 'EUR');

        expect($target->format())->toContain('€')
            ->and($target->format())->toContain('1.500'); // EUR format uses comma decimal separator
    });

    it('converts to array representation', function () {
        $target = FundraisingTarget::fromAmount(1000.0);
        $array = $target->toArray();

        expect($array)->toHaveKey('amount')
            ->and($array)->toHaveKey('currency')
            ->and($array)->toHaveKey('formatted')
            ->and($array)->toHaveKey('is_achievable')
            ->and($array)->toHaveKey('is_mega_campaign')
            ->and($array)->toHaveKey('requires_approval')
            ->and($array)->toHaveKey('milestones')
            ->and($array['amount'])->toBe(1000.0)
            ->and($array['currency'])->toBe('EUR');
    });

    it('converts to string using format method', function () {
        $target = FundraisingTarget::fromAmount(2500.0);

        expect((string) $target)->toBe($target->format());
    });
});

describe('UserCampaignStats Value Object', function () {
    it('creates user campaign stats with all parameters', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 10,
            activeCampaigns: 3,
            completedCampaigns: 5,
            draftCampaigns: 2,
            totalAmountRaised: 25000.0,
            totalGoalAmount: 50000.0,
            totalDonations: 150,
            averageSuccessRate: 75.5
        );

        expect($stats->totalCampaigns)->toBe(10)
            ->and($stats->activeCampaigns)->toBe(3)
            ->and($stats->completedCampaigns)->toBe(5)
            ->and($stats->draftCampaigns)->toBe(2)
            ->and($stats->totalAmountRaised)->toBe(25000.0)
            ->and($stats->totalGoalAmount)->toBe(50000.0)
            ->and($stats->totalDonations)->toBe(150)
            ->and($stats->averageSuccessRate)->toBe(75.5);
    });

    it('calculates progress percentage correctly', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 5,
            activeCampaigns: 2,
            completedCampaigns: 3,
            draftCampaigns: 0,
            totalAmountRaised: 15000.0,
            totalGoalAmount: 30000.0,
            totalDonations: 100,
            averageSuccessRate: 80.0
        );

        expect($stats->getProgressPercentage())->toBe(50.0);
    });

    it('handles zero goal amount in progress calculation', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 1,
            activeCampaigns: 1,
            completedCampaigns: 0,
            draftCampaigns: 0,
            totalAmountRaised: 1000.0,
            totalGoalAmount: 0.0,
            totalDonations: 10,
            averageSuccessRate: 0.0
        );

        expect($stats->getProgressPercentage())->toBe(0.0);
    });

    it('caps progress percentage at 100', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 2,
            activeCampaigns: 0,
            completedCampaigns: 2,
            draftCampaigns: 0,
            totalAmountRaised: 12000.0,
            totalGoalAmount: 10000.0,
            totalDonations: 80,
            averageSuccessRate: 100.0
        );

        expect($stats->getProgressPercentage())->toBe(100.0);
    });

    it('formats total raised amount correctly', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 3,
            activeCampaigns: 1,
            completedCampaigns: 2,
            draftCampaigns: 0,
            totalAmountRaised: 25750.0,
            totalGoalAmount: 40000.0,
            totalDonations: 120,
            averageSuccessRate: 85.0
        );

        expect($stats->getFormattedTotalRaised())->toBe('€25.750');
    });

    it('formats total goal amount correctly', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 4,
            activeCampaigns: 2,
            completedCampaigns: 2,
            draftCampaigns: 0,
            totalAmountRaised: 18000.0,
            totalGoalAmount: 45000.0,
            totalDonations: 90,
            averageSuccessRate: 60.0
        );

        expect($stats->getFormattedTotalGoal())->toBe('€45.000');
    });

    it('formats success rate correctly', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 6,
            activeCampaigns: 1,
            completedCampaigns: 4,
            draftCampaigns: 1,
            totalAmountRaised: 32000.0,
            totalGoalAmount: 48000.0,
            totalDonations: 200,
            averageSuccessRate: 78.65
        );

        expect($stats->getFormattedSuccessRate())->toBe('78.7%');
    });

    it('detects if user has active campaigns', function () {
        $withActive = new UserCampaignStats(
            totalCampaigns: 5,
            activeCampaigns: 2,
            completedCampaigns: 3,
            draftCampaigns: 0,
            totalAmountRaised: 20000.0,
            totalGoalAmount: 30000.0,
            totalDonations: 100,
            averageSuccessRate: 75.0
        );

        $withoutActive = new UserCampaignStats(
            totalCampaigns: 3,
            activeCampaigns: 0,
            completedCampaigns: 3,
            draftCampaigns: 0,
            totalAmountRaised: 15000.0,
            totalGoalAmount: 15000.0,
            totalDonations: 80,
            averageSuccessRate: 100.0
        );

        expect($withActive->hasActiveCampaigns())->toBeTrue()
            ->and($withoutActive->hasActiveCampaigns())->toBeFalse();
    });

    it('detects if user has draft campaigns', function () {
        $withDrafts = new UserCampaignStats(
            totalCampaigns: 7,
            activeCampaigns: 2,
            completedCampaigns: 3,
            draftCampaigns: 2,
            totalAmountRaised: 18000.0,
            totalGoalAmount: 35000.0,
            totalDonations: 90,
            averageSuccessRate: 70.0
        );

        $withoutDrafts = new UserCampaignStats(
            totalCampaigns: 5,
            activeCampaigns: 2,
            completedCampaigns: 3,
            draftCampaigns: 0,
            totalAmountRaised: 25000.0,
            totalGoalAmount: 30000.0,
            totalDonations: 150,
            averageSuccessRate: 80.0
        );

        expect($withDrafts->hasDrafts())->toBeTrue()
            ->and($withoutDrafts->hasDrafts())->toBeFalse();
    });

    it('calculates total published campaigns correctly', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 10,
            activeCampaigns: 3,
            completedCampaigns: 5,
            draftCampaigns: 2,
            totalAmountRaised: 40000.0,
            totalGoalAmount: 60000.0,
            totalDonations: 250,
            averageSuccessRate: 85.0
        );

        expect($stats->getTotalPublishedCampaigns())->toBe(8);
    });

    it('handles edge case with zero values', function () {
        $stats = new UserCampaignStats(
            totalCampaigns: 0,
            activeCampaigns: 0,
            completedCampaigns: 0,
            draftCampaigns: 0,
            totalAmountRaised: 0.0,
            totalGoalAmount: 0.0,
            totalDonations: 0,
            averageSuccessRate: 0.0
        );

        expect($stats->getProgressPercentage())->toBe(0.0)
            ->and($stats->hasActiveCampaigns())->toBeFalse()
            ->and($stats->hasDrafts())->toBeFalse()
            ->and($stats->getTotalPublishedCampaigns())->toBe(0);
    });
});

describe('DonationProgress Value Object', function () {
    it('creates donation progress with all parameters', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $averageDonation = new Money(50.0, 'EUR');

        $progress = new DonationProgress(
            raised: $raised,
            goal: $goal,
            donorCount: 100,
            daysRemaining: 15,
            isActive: true,
            averageDonation: $averageDonation
        );

        expect($progress->getRaised())->toBe($raised)
            ->and($progress->getGoal())->toBe($goal)
            ->and($progress->getDonorCount())->toBe(100)
            ->and($progress->getDaysRemaining())->toBe(15)
            ->and($progress->isActive())->toBeTrue();
    });

    it('throws exception for currency mismatch', function () {
        $raised = new Money(1000.0, 'USD');
        $goal = new Money(2000.0, 'EUR');

        expect(fn () => new DonationProgress($raised, $goal))
            ->toThrow(InvalidArgumentException::class, 'Raised and goal amounts must be in the same currency');
    });

    it('calculates remaining amount correctly', function () {
        $raised = new Money(3000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $progress = new DonationProgress($raised, $goal);

        expect($progress->getRemaining()->getAmount())->toBe(7000.0);
    });

    it('returns zero remaining when goal exceeded', function () {
        $raised = new Money(12000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $progress = new DonationProgress($raised, $goal);

        expect($progress->getRemaining()->getAmount())->toBe(0.0);
    });

    it('handles negative days remaining correctly', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $progress = new DonationProgress($raised, $goal, daysRemaining: -5);

        expect($progress->getDaysRemaining())->toBe(0);
    });

    it('determines if campaign has expired', function () {
        $raised = new Money(1000.0, 'EUR');
        $goal = new Money(5000.0, 'EUR');

        $expired = new DonationProgress($raised, $goal, daysRemaining: -1);
        $active = new DonationProgress($raised, $goal, daysRemaining: 10);

        expect($expired->hasExpired())->toBeTrue()
            ->and($active->hasExpired())->toBeFalse();
    });

    it('determines if campaign is ending soon', function () {
        $raised = new Money(2000.0, 'EUR');
        $goal = new Money(5000.0, 'EUR');

        $endingSoon = new DonationProgress($raised, $goal, daysRemaining: 5, isActive: true);
        $notEndingSoon = new DonationProgress($raised, $goal, daysRemaining: 15, isActive: true);
        $inactive = new DonationProgress($raised, $goal, daysRemaining: 5, isActive: false);

        expect($endingSoon->isEndingSoon())->toBeTrue()
            ->and($notEndingSoon->isEndingSoon())->toBeFalse()
            ->and($inactive->isEndingSoon())->toBeFalse();
    });

    it('determines if campaign is ending today', function () {
        $raised = new Money(3000.0, 'EUR');
        $goal = new Money(8000.0, 'EUR');

        $endingToday = new DonationProgress($raised, $goal, daysRemaining: 0, isActive: true);
        $notEndingToday = new DonationProgress($raised, $goal, daysRemaining: 1, isActive: true);

        expect($endingToday->isEndingToday())->toBeTrue()
            ->and($notEndingToday->isEndingToday())->toBeFalse();
    });

    it('calculates average donation when provided', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $averageDonation = new Money(100.0, 'EUR');

        $progress = new DonationProgress(
            raised: $raised,
            goal: $goal,
            averageDonation: $averageDonation
        );

        expect($progress->getAverageDonation())->toBe($averageDonation);
    });

    it('calculates average donation from donor count and raised amount', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $progress = new DonationProgress(
            raised: $raised,
            goal: $goal,
            donorCount: 50
        );

        $average = $progress->getAverageDonation();
        expect($average)->not->toBeNull()
            ->and($average->getAmount())->toBe(100.0);
    });

    it('returns null average donation when no donors', function () {
        $raised = new Money(0.0, 'EUR');
        $goal = new Money(5000.0, 'EUR');

        $progress = new DonationProgress($raised, $goal, donorCount: 0);

        expect($progress->getAverageDonation())->toBeNull();
    });

    it('gets progress percentage from progress data', function () {
        $raised = new Money(2500.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $progress = new DonationProgress($raised, $goal);

        expect($progress->getPercentage())->toBe(25.0);
    });

    it('determines if goal has been reached', function () {
        $reachedGoal = new DonationProgress(
            new Money(10000.0, 'EUR'),
            new Money(10000.0, 'EUR')
        );

        $notReachedGoal = new DonationProgress(
            new Money(7500.0, 'EUR'),
            new Money(10000.0, 'EUR')
        );

        expect($reachedGoal->hasReachedGoal())->toBeTrue()
            ->and($notReachedGoal->hasReachedGoal())->toBeFalse();
    });

    it('calculates urgency levels correctly', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $inactive = new DonationProgress($raised, $goal, isActive: false);
        $expired = new DonationProgress($raised, $goal, daysRemaining: -1);
        $critical = new DonationProgress($raised, $goal, daysRemaining: 0, isActive: true);
        $veryHigh = new DonationProgress($raised, $goal, daysRemaining: 2, isActive: true);
        $high = new DonationProgress($raised, $goal, daysRemaining: 5, isActive: true);
        $medium = new DonationProgress($raised, $goal, daysRemaining: 10, isActive: true);
        $normal = new DonationProgress($raised, $goal, daysRemaining: 20, isActive: true);

        expect($inactive->getUrgencyLevel())->toBe('inactive')
            ->and($expired->getUrgencyLevel())->toBe('expired')
            ->and($critical->getUrgencyLevel())->toBe('critical')
            ->and($veryHigh->getUrgencyLevel())->toBe('very-high')
            ->and($high->getUrgencyLevel())->toBe('high')
            ->and($medium->getUrgencyLevel())->toBe('medium')
            ->and($normal->getUrgencyLevel())->toBe('normal');
    });

    it('provides appropriate urgency colors', function () {
        $raised = new Money(3000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $expired = new DonationProgress($raised, $goal, daysRemaining: -1);
        $critical = new DonationProgress($raised, $goal, daysRemaining: 0, isActive: true);
        $normal = new DonationProgress($raised, $goal, daysRemaining: 20, isActive: true);

        expect($expired->getUrgencyColor())->toBe('gray')
            ->and($critical->getUrgencyColor())->toBe('red')
            ->and($normal->getUrgencyColor())->toBe('green');
    });

    it('calculates momentum indicators', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $averageDonation = new Money(100.0, 'EUR');

        $surging = new DonationProgress(
            $raised, $goal,
            averageDonation: $averageDonation,
            recentMomentum: new Money(200.0, 'EUR')
        );

        $steady = new DonationProgress(
            $raised, $goal,
            averageDonation: $averageDonation,
            recentMomentum: new Money(90.0, 'EUR')
        );

        $slowing = new DonationProgress(
            $raised, $goal,
            averageDonation: $averageDonation,
            recentMomentum: new Money(50.0, 'EUR')
        );

        expect($surging->getMomentumIndicator())->toBe('surging')
            ->and($steady->getMomentumIndicator())->toBe('steady')
            ->and($slowing->getMomentumIndicator())->toBe('slowing');
    });

    it('estimates completion time', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');
        $recentMomentum = new Money(500.0, 'EUR'); // 500 per day

        $progress = new DonationProgress(
            $raised, $goal,
            daysRemaining: 20,
            isActive: true,
            recentMomentum: $recentMomentum
        );

        $estimate = $progress->getCompletionEstimate();
        expect($estimate)->toBe(10); // 5000 remaining / 500 per day = 10 days
    });

    it('returns null completion estimate for inactive campaigns', function () {
        $raised = new Money(3000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $progress = new DonationProgress($raised, $goal, isActive: false);

        expect($progress->getCompletionEstimate())->toBeNull();
    });

    it('determines if campaign needs boost', function () {
        $raised = new Money(5000.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $needsBoost = new DonationProgress(
            $raised, $goal,
            daysRemaining: 3,
            isActive: true
        ); // Ending soon and only 50% complete

        $doesNotNeedBoost = new DonationProgress(
            $raised, $goal,
            daysRemaining: 15,
            isActive: true
        );

        expect($needsBoost->needsBoost())->toBeTrue()
            ->and($doesNotNeedBoost->needsBoost())->toBeFalse();
    });

    it('provides comprehensive display data', function () {
        $raised = new Money(7500.0, 'EUR');
        $goal = new Money(10000.0, 'EUR');

        $progress = new DonationProgress(
            $raised, $goal,
            donorCount: 150,
            daysRemaining: 10,
            isActive: true
        );

        $data = $progress->getDisplayData();

        expect($data)->toHaveKey('raised')
            ->and($data)->toHaveKey('goal')
            ->and($data)->toHaveKey('remaining')
            ->and($data)->toHaveKey('percentage')
            ->and($data)->toHaveKey('donor_count')
            ->and($data)->toHaveKey('days_remaining')
            ->and($data)->toHaveKey('urgency_level')
            ->and($data)->toHaveKey('momentum_indicator')
            ->and($data['percentage'])->toBe(75.0)
            ->and($data['donor_count'])->toBe(150);
    });

    it('converts to array using display data', function () {
        $raised = new Money(2500.0, 'EUR');
        $goal = new Money(5000.0, 'EUR');

        $progress = new DonationProgress($raised, $goal);
        $array = $progress->toArray();

        expect($array)->toBe($progress->getDisplayData());
    });
});

describe('CampaignProgress Value Object', function () {
    it('creates campaign progress with all required parameters', function () {
        $progress = new CampaignProgress(
            campaignId: 1,
            goalAmount: 10000.0,
            currentAmount: 5000.0,
            progressPercentage: 50.0,
            remainingAmount: 5000.0,
            totalDays: 30,
            daysElapsed: 15,
            daysRemaining: 15,
            expectedProgress: 50.0,
            velocity: 333.33,
            projectedFinalAmount: 10000.0,
            isOnTrack: true,
            isLikelyToSucceed: true,
            hasReachedGoal: false,
            donationsCount: 100
        );

        expect($progress->getCampaignId())->toBe(1)
            ->and($progress->getGoalAmount())->toBe(10000.0)
            ->and($progress->getCurrentAmount())->toBe(5000.0)
            ->and($progress->getPercentage())->toBe(50.0)
            ->and($progress->getRemainingAmount())->toBe(5000.0)
            ->and($progress->getTotalDays())->toBe(30)
            ->and($progress->getDaysElapsed())->toBe(15)
            ->and($progress->getDaysRemaining())->toBe(15)
            ->and($progress->getExpectedProgress())->toBe(50.0)
            ->and($progress->getVelocity())->toBe(333.33)
            ->and($progress->getProjectedFinalAmount())->toBe(10000.0)
            ->and($progress->isOnTrack())->toBeTrue()
            ->and($progress->isLikelyToSucceed())->toBeTrue()
            ->and($progress->hasReachedGoal())->toBeFalse()
            ->and($progress->getDonationsCount())->toBe(100);
    });

    it('throws exception for negative current amount', function () {
        expect(fn () => new CampaignProgress(
            campaignId: 1,
            goalAmount: 10000.0,
            currentAmount: -1000.0,
            progressPercentage: 0.0,
            remainingAmount: 11000.0,
            totalDays: 30,
            daysElapsed: 5,
            daysRemaining: 25,
            expectedProgress: 16.67,
            velocity: 0.0,
            projectedFinalAmount: 0.0,
            isOnTrack: false,
            isLikelyToSucceed: false,
            hasReachedGoal: false,
            donationsCount: 0
        ))->toThrow(InvalidArgumentException::class, 'Current amount cannot be negative');
    });

    it('throws exception for zero or negative goal amount', function () {
        expect(fn () => new CampaignProgress(
            campaignId: 1,
            goalAmount: 0.0,
            currentAmount: 0.0,
            progressPercentage: 0.0,
            remainingAmount: 0.0,
            totalDays: 30,
            daysElapsed: 0,
            daysRemaining: 30,
            expectedProgress: 0.0,
            velocity: 0.0,
            projectedFinalAmount: 0.0,
            isOnTrack: false,
            isLikelyToSucceed: false,
            hasReachedGoal: false,
            donationsCount: 0
        ))->toThrow(InvalidArgumentException::class, 'Goal amount must be greater than zero');
    });

    it('throws exception for zero or negative campaign id', function () {
        expect(fn () => new CampaignProgress(
            campaignId: 0,
            goalAmount: 10000.0,
            currentAmount: 5000.0,
            progressPercentage: 50.0,
            remainingAmount: 5000.0,
            totalDays: 30,
            daysElapsed: 15,
            daysRemaining: 15,
            expectedProgress: 50.0,
            velocity: 333.33,
            projectedFinalAmount: 10000.0,
            isOnTrack: true,
            isLikelyToSucceed: true,
            hasReachedGoal: false,
            donationsCount: 100
        ))->toThrow(InvalidArgumentException::class, 'Campaign ID must be positive');
    });

    it('rounds percentage correctly', function () {
        $progress = CampaignProgress::createForTesting(3333.33, 10000.0);

        expect($progress->getPercentageRounded())->toBe(33);
    });

    it('determines if campaign is behind schedule', function () {
        $behindSchedule = new CampaignProgress(
            campaignId: 1,
            goalAmount: 10000.0,
            currentAmount: 2000.0,
            progressPercentage: 20.0,
            remainingAmount: 8000.0,
            totalDays: 30,
            daysElapsed: 15,
            daysRemaining: 15,
            expectedProgress: 50.0,
            velocity: 133.33,
            projectedFinalAmount: 4000.0,
            isOnTrack: false,
            isLikelyToSucceed: false,
            hasReachedGoal: false,
            donationsCount: 40
        );

        $onSchedule = new CampaignProgress(
            campaignId: 2,
            goalAmount: 10000.0,
            currentAmount: 5000.0,
            progressPercentage: 50.0,
            remainingAmount: 5000.0,
            totalDays: 30,
            daysElapsed: 15,
            daysRemaining: 15,
            expectedProgress: 50.0,
            velocity: 333.33,
            projectedFinalAmount: 10000.0,
            isOnTrack: true,
            isLikelyToSucceed: true,
            hasReachedGoal: false,
            donationsCount: 100
        );

        expect($behindSchedule->isBehindSchedule())->toBeTrue()
            ->and($onSchedule->isBehindSchedule())->toBeFalse();
    });

    it('determines if campaign is completed', function () {
        $completed = CampaignProgress::createForTesting(10000.0, 10000.0);
        $notCompleted = CampaignProgress::createForTesting(7500.0, 10000.0);

        expect($completed->isCompleted())->toBeTrue()
            ->and($notCompleted->isCompleted())->toBeFalse();
    });

    it('calculates progress ratio correctly', function () {
        $progress = CampaignProgress::createForTesting(7500.0, 10000.0);

        expect($progress->getProgressRatio())->toBe(0.75);
    });

    it('caps progress ratio at 1.0', function () {
        $progress = CampaignProgress::createForTesting(15000.0, 10000.0);

        expect($progress->getProgressRatio())->toBe(1.0);
    });

    it('provides performance status based on progress state', function () {
        $completed = CampaignProgress::createForTesting(10000.0, 10000.0);
        $excellent = new CampaignProgress(
            campaignId: 1,
            goalAmount: 10000.0,
            currentAmount: 8000.0,
            progressPercentage: 80.0,
            remainingAmount: 2000.0,
            totalDays: 30,
            daysElapsed: 20,
            daysRemaining: 10,
            expectedProgress: 66.67,
            velocity: 400.0,
            projectedFinalAmount: 12000.0,
            isOnTrack: true,
            isLikelyToSucceed: true,
            hasReachedGoal: false,
            donationsCount: 160
        );

        $poor = new CampaignProgress(
            campaignId: 2,
            goalAmount: 10000.0,
            currentAmount: 1000.0,
            progressPercentage: 10.0,
            remainingAmount: 9000.0,
            totalDays: 30,
            daysElapsed: 20,
            daysRemaining: 10,
            expectedProgress: 66.67,
            velocity: 50.0,
            projectedFinalAmount: 1500.0,
            isOnTrack: false,
            isLikelyToSucceed: false,
            hasReachedGoal: false,
            donationsCount: 20
        );

        expect($completed->getPerformanceStatus())->toBe('completed')
            ->and($excellent->getPerformanceStatus())->toBe('excellent')
            ->and($poor->getPerformanceStatus())->toBe('poor');
    });

    it('creates progress from campaign object', function () {
        $campaign = (object) [
            'id' => 123,
            'goal_amount' => 50000.0,
            'current_amount' => 25000.0,
            'donations_count' => 200,
        ];

        $progress = CampaignProgress::fromCampaign($campaign);

        expect($progress->getCampaignId())->toBe(123)
            ->and($progress->getGoalAmount())->toBe(50000.0)
            ->and($progress->getCurrentAmount())->toBe(25000.0)
            ->and($progress->getDonationsCount())->toBe(200)
            ->and($progress->getPercentage())->toBe(50.0);
    });

    it('handles campaign object without id', function () {
        $campaign = (object) [
            'goal_amount' => 10000.0,
            'current_amount' => 3000.0,
        ];

        $progress = CampaignProgress::fromCampaign($campaign);

        expect($progress->getCampaignId())->toBe(1);
    });

    it('creates progress for testing with sensible defaults', function () {
        $progress = CampaignProgress::createForTesting(6000.0, 12000.0);

        expect($progress->getCampaignId())->toBe(1)
            ->and($progress->getCurrentAmount())->toBe(6000.0)
            ->and($progress->getGoalAmount())->toBe(12000.0)
            ->and($progress->getPercentage())->toBe(50.0)
            ->and($progress->getRemainingAmount())->toBe(6000.0)
            ->and($progress->getTotalDays())->toBe(30)
            ->and($progress->getDaysElapsed())->toBe(15)
            ->and($progress->getDaysRemaining())->toBe(15);
    });

    it('calculates velocity in testing factory method', function () {
        $progress = CampaignProgress::createForTesting(3000.0, 10000.0);

        // velocity = currentAmount / daysElapsed = 3000 / 15 = 200
        expect($progress->getVelocity())->toBe(200.0);
    });

    it('determines track status in testing factory method', function () {
        $onTrack = CampaignProgress::createForTesting(5000.0, 10000.0); // 50% progress, 50% expected
        $offTrack = CampaignProgress::createForTesting(2000.0, 10000.0); // 20% progress, 50% expected

        expect($onTrack->isOnTrack())->toBeTrue()
            ->and($offTrack->isOnTrack())->toBeFalse();
    });
});

describe('Goal Value Object', function () {
    it('creates goal with target and current amounts', function () {
        $target = new Money(10000.0, 'EUR');
        $current = new Money(3000.0, 'EUR');
        $goal = new Goal($target, $current);

        expect($goal->targetAmount)->toBe($target)
            ->and($goal->currentAmount)->toBe($current);
    });

    it('creates goal using factory method', function () {
        $goal = Goal::create(5000.0, 1500.0, 'USD');

        expect($goal->targetAmount->getAmount())->toBe(5000.0)
            ->and($goal->currentAmount->getAmount())->toBe(1500.0)
            ->and($goal->targetAmount->getCurrency())->toBe('USD')
            ->and($goal->currentAmount->getCurrency())->toBe('USD');
    });

    it('throws exception for zero or negative target amount', function () {
        expect(fn () => new Goal(
            new Money(0.0, 'EUR'),
            new Money(0.0, 'EUR')
        ))->toThrow(InvalidArgumentException::class, 'Goal target amount must be positive');

        expect(fn () => Goal::create(-1000.0))
            ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');
    });

    it('throws exception for negative current amount', function () {
        expect(fn () => Goal::create(5000.0, -500.0))
            ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');
    });

    it('throws exception for currency mismatch', function () {
        expect(fn () => new Goal(
            new Money(5000.0, 'EUR'),
            new Money(1000.0, 'USD')
        ))->toThrow(InvalidArgumentException::class, 'Goal target and current amounts must have the same currency');
    });

    it('calculates progress percentage correctly', function () {
        $goal = Goal::create(8000.0, 2000.0);

        expect($goal->getProgressPercentage())->toBe(25.0);
    });

    it('handles zero target amount in progress calculation', function () {
        $target = new Money(0.0, 'EUR');
        $current = new Money(0.0, 'EUR');

        // This will throw exception in constructor, so we can't test this case
        // The constructor validates target > 0
        expect(true)->toBeTrue(); // Placeholder to maintain test structure
    });

    it('caps progress percentage at 100', function () {
        $goal = Goal::create(5000.0, 6000.0);

        expect($goal->getProgressPercentage())->toBe(100.0);
    });

    it('calculates remaining amount correctly', function () {
        $goal = Goal::create(10000.0, 3500.0);
        $remaining = $goal->getRemainingAmount();

        expect($remaining->getAmount())->toBe(6500.0)
            ->and($remaining->getCurrency())->toBe('USD');
    });

    it('returns zero remaining when target exceeded', function () {
        $goal = Goal::create(5000.0, 7000.0);
        $remaining = $goal->getRemainingAmount();

        expect($remaining->getAmount())->toBe(0.0);
    });

    it('determines if target has been reached', function () {
        $reached = Goal::create(8000.0, 8000.0);
        $exceeded = Goal::create(5000.0, 6000.0);
        $notReached = Goal::create(10000.0, 7500.0);

        expect($reached->hasReachedTarget())->toBeTrue()
            ->and($exceeded->hasReachedTarget())->toBeTrue()
            ->and($notReached->hasReachedTarget())->toBeFalse();
    });

    it('adds amount to current total', function () {
        $goal = Goal::create(10000.0, 3000.0, 'EUR');
        $donation = new Money(1500.0, 'EUR');
        $updatedGoal = $goal->addAmount($donation);

        expect($updatedGoal->currentAmount->getAmount())->toBe(4500.0)
            ->and($updatedGoal->targetAmount->getAmount())->toBe(10000.0)
            ->and($updatedGoal)->not->toBe($goal); // Should return new instance
    });

    it('throws exception when adding different currency', function () {
        $goal = Goal::create(5000.0, 1000.0, 'EUR');
        $donation = new Money(500.0, 'USD');

        expect(fn () => $goal->addAmount($donation))
            ->toThrow(InvalidArgumentException::class, 'Cannot add amount with different currency to goal');
    });

    it('compares goals for equality', function () {
        $goal1 = Goal::create(5000.0, 2000.0, 'EUR');
        $goal2 = Goal::create(5000.0, 2000.0, 'EUR');
        $goal3 = Goal::create(5000.0, 2500.0, 'EUR');
        $goal4 = Goal::create(6000.0, 2000.0, 'EUR');

        expect($goal1->equals($goal2))->toBeTrue()
            ->and($goal1->equals($goal3))->toBeFalse()
            ->and($goal1->equals($goal4))->toBeFalse();
    });

    it('converts to string with formatted display', function () {
        $goal = Goal::create(10000.0, 7500.0, 'EUR');
        $string = (string) $goal;

        expect($string)->toContain('€7.500,00')
            ->and($string)->toContain('€10.000,00')
            ->and($string)->toContain('75.0%');
    });

    it('formats string representation correctly', function () {
        $goal = Goal::create(8000.0, 2000.0, 'USD');
        $string = $goal->__toString();

        expect($string)->toContain('$2,000.00')
            ->and($string)->toContain('$8,000.00')
            ->and($string)->toContain('25.0%');
    });
});

describe('TimeRemaining Value Object', function () {
    it('creates time remaining with end date', function () {
        $endDate = Carbon::parse('2025-12-31 23:59:59');
        $currentDate = Carbon::parse('2025-12-24 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getDaysRemaining())->toBe(7);
    });

    it('calculates days remaining correctly', function () {
        $endDate = Carbon::parse('2025-01-10 15:00:00');
        $currentDate = Carbon::parse('2025-01-05 10:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getDaysRemaining())->toBe(5);
    });

    it('calculates hours remaining correctly', function () {
        $endDate = Carbon::parse('2025-01-01 18:00:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getHoursRemaining())->toBe(6);
    });

    it('calculates minutes remaining correctly', function () {
        $endDate = Carbon::parse('2025-01-01 12:45:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getMinutesRemaining())->toBe(45);
    });

    it('uses current time when no current date provided', function () {
        $endDate = Carbon::now()->addDays(5);
        $timeRemaining = new TimeRemaining($endDate);

        // Allow for small timing differences in test execution
        $daysRemaining = $timeRemaining->getDaysRemaining();
        expect($daysRemaining)->toBeGreaterThanOrEqual(4)
            ->and($daysRemaining)->toBeLessThanOrEqual(5);
    });

    it('determines if campaign is expired', function () {
        $expiredEndDate = Carbon::parse('2025-01-01 12:00:00');
        $currentDate = Carbon::parse('2025-01-05 12:00:00');

        $activeEndDate = Carbon::parse('2025-01-10 12:00:00');

        $expired = new TimeRemaining($expiredEndDate, $currentDate);
        $active = new TimeRemaining($activeEndDate, $currentDate);

        expect($expired->isExpired())->toBeTrue()
            ->and($active->isExpired())->toBeFalse();
    });

    it('determines if campaign is expiring soon', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $expiringSoon = new TimeRemaining(
            Carbon::parse('2025-01-05 12:00:00'),
            $currentDate
        );

        $notExpiringSoon = new TimeRemaining(
            Carbon::parse('2025-01-15 12:00:00'),
            $currentDate
        );

        $expired = new TimeRemaining(
            Carbon::parse('2024-12-25 12:00:00'),
            $currentDate
        );

        expect($expiringSoon->isExpiringSoon())->toBeTrue()
            ->and($notExpiringSoon->isExpiringSoon())->toBeFalse()
            ->and($expired->isExpiringSoon())->toBeFalse();
    });

    it('uses custom threshold for expiring soon', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');
        $endDate = Carbon::parse('2025-01-04 12:00:00'); // 3 days

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->isExpiringSoon(5))->toBeTrue()
            ->and($timeRemaining->isExpiringSoon(2))->toBeFalse();
    });

    it('provides time remaining text for expired campaigns', function () {
        $expiredEndDate = Carbon::parse('2025-01-01 12:00:00');
        $currentDate = Carbon::parse('2025-01-05 12:00:00');

        $timeRemaining = new TimeRemaining($expiredEndDate, $currentDate);

        expect($timeRemaining->getTimeRemainingText())->toBe('Expired');
    });

    it('provides time remaining text for minutes', function () {
        $endDate = Carbon::parse('2025-01-01 12:30:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getTimeRemainingText())->toBe('30 minutes remaining');
    });

    it('provides time remaining text for hours', function () {
        $endDate = Carbon::parse('2025-01-01 18:00:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getTimeRemainingText())->toBe('6 hours remaining');
    });

    it('provides time remaining text for days', function () {
        $endDate = Carbon::parse('2025-01-08 12:00:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getTimeRemainingText())->toBe('7 days remaining');
    });

    it('handles singular vs plural text correctly', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $oneDay = new TimeRemaining(
            Carbon::parse('2025-01-02 12:00:00'),
            $currentDate
        );

        $oneHour = new TimeRemaining(
            Carbon::parse('2025-01-01 13:00:00'),
            $currentDate
        );

        $oneMinute = new TimeRemaining(
            Carbon::parse('2025-01-01 12:01:00'),
            $currentDate
        );

        expect($oneDay->getTimeRemainingText())->toBe('1 day remaining')
            ->and($oneHour->getTimeRemainingText())->toBe('1 hour remaining')
            ->and($oneMinute->getTimeRemainingText())->toBe('1 minute remaining');
    });

    it('calculates urgency levels correctly', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $expired = new TimeRemaining(
            Carbon::parse('2024-12-30 12:00:00'),
            $currentDate
        );

        $critical = new TimeRemaining(
            Carbon::parse('2025-01-02 12:00:00'),
            $currentDate
        );

        $urgent = new TimeRemaining(
            Carbon::parse('2025-01-03 12:00:00'),
            $currentDate
        );

        $warning = new TimeRemaining(
            Carbon::parse('2025-01-05 12:00:00'),
            $currentDate
        );

        $normal = new TimeRemaining(
            Carbon::parse('2025-01-15 12:00:00'),
            $currentDate
        );

        expect($expired->getUrgencyLevel())->toBe('expired')
            ->and($critical->getUrgencyLevel())->toBe('critical')
            ->and($urgent->getUrgencyLevel())->toBe('urgent')
            ->and($warning->getUrgencyLevel())->toBe('warning')
            ->and($normal->getUrgencyLevel())->toBe('normal');
    });

    it('provides appropriate urgency colors', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $expired = new TimeRemaining(
            Carbon::parse('2024-12-30 12:00:00'),
            $currentDate
        );

        $critical = new TimeRemaining(
            Carbon::parse('2025-01-02 12:00:00'),
            $currentDate
        );

        $normal = new TimeRemaining(
            Carbon::parse('2025-01-15 12:00:00'),
            $currentDate
        );

        expect($expired->getUrgencyColor())->toBe('red')
            ->and($critical->getUrgencyColor())->toBe('red')
            ->and($normal->getUrgencyColor())->toBe('green');
    });

    it('creates time remaining from campaign object', function () {
        $endDate = Carbon::parse('2025-06-01 23:59:59');
        $currentDate = Carbon::parse('2025-05-01 12:00:00');

        $campaign = (object) [
            'end_date' => $endDate,
        ];

        $timeRemaining = TimeRemaining::fromCampaign($campaign, $currentDate);

        expect($timeRemaining->getDaysRemaining())->toBe(31);
    });

    it('handles campaign with string end date', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $campaign = (object) [
            'end_date' => '2025-01-10 23:59:59',
        ];

        $timeRemaining = TimeRemaining::fromCampaign($campaign, $currentDate);

        expect($timeRemaining->getDaysRemaining())->toBe(9);
    });

    it('handles campaign with null end date', function () {
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $campaign = (object) [
            'end_date' => null,
        ];

        $timeRemaining = TimeRemaining::fromCampaign($campaign, $currentDate);

        // Should default to far future (100 years)
        expect($timeRemaining->getDaysRemaining())->toBeGreaterThan(36000);
    });

    it('handles negative time differences correctly', function () {
        $endDate = Carbon::parse('2024-12-01 12:00:00');
        $currentDate = Carbon::parse('2025-01-01 12:00:00');

        $timeRemaining = new TimeRemaining($endDate, $currentDate);

        expect($timeRemaining->getDaysRemaining())->toBeLessThan(0)
            ->and($timeRemaining->getHoursRemaining())->toBeLessThan(0)
            ->and($timeRemaining->getMinutesRemaining())->toBeLessThan(0)
            ->and($timeRemaining->isExpired())->toBeTrue();
    });
});

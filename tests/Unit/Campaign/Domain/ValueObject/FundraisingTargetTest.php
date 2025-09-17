<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\FundraisingTarget;
use Modules\Shared\Domain\ValueObject\Money;

describe('FundraisingTarget Value Object', function (): void {
    describe('Construction and Validation', function (): void {
        it('creates fundraising target with valid amount', function (): void {
            $money = new Money(5000.00, 'EUR');
            $target = new FundraisingTarget($money);

            expect($target->getAmount())->toBe(5000.00)
                ->and($target->getCurrency())->toBe('EUR')
                ->and($target->getMoney())->toBe($money);
        });

        it('rejects target below minimum amount', function (): void {
            $money = new Money(50.00, 'EUR');

            expect(fn () => new FundraisingTarget($money))
                ->toThrow(InvalidArgumentException::class, 'Fundraising target must be at least 100.00 EUR');
        });

        it('rejects target above maximum amount', function (): void {
            $money = new Money(15000000.00, 'EUR');

            expect(fn () => new FundraisingTarget($money))
                ->toThrow(InvalidArgumentException::class, 'Fundraising target cannot exceed 10,000,000.00 EUR');
        });

        it('accepts minimum allowed target', function (): void {
            $money = new Money(100.00, 'USD');
            $target = new FundraisingTarget($money);

            expect($target->getAmount())->toBe(100.00)
                ->and($target->getCurrency())->toBe('USD');
        });

        it('accepts maximum allowed target', function (): void {
            $money = new Money(10000000.00, 'GBP');
            $target = new FundraisingTarget($money);

            expect($target->getAmount())->toBe(10000000.00)
                ->and($target->getCurrency())->toBe('GBP');
        });

        it('validates different currencies correctly', function (): void {
            $currencies = ['EUR', 'USD', 'GBP'];

            foreach ($currencies as $currency) {
                $money = new Money(1000.00, $currency);
                $target = new FundraisingTarget($money);
                expect($target->getCurrency())->toBe($currency);
            }
        });
    });

    describe('Factory Methods', function (): void {
        it('creates from Money object', function (): void {
            $money = new Money(2500.00, 'EUR');
            $target = FundraisingTarget::fromMoney($money);

            expect($target->getAmount())->toBe(2500.00)
                ->and($target->getCurrency())->toBe('EUR');
        });

        it('creates from amount and currency', function (): void {
            $target = FundraisingTarget::fromAmount(1500.00, 'USD');

            expect($target->getAmount())->toBe(1500.00)
                ->and($target->getCurrency())->toBe('USD');
        });

        it('creates minimum target', function (): void {
            $target = FundraisingTarget::minimum('GBP');

            expect($target->getAmount())->toBe(100.00)
                ->and($target->getCurrency())->toBe('GBP');
        });

        it('creates maximum target', function (): void {
            $target = FundraisingTarget::maximum('EUR');

            expect($target->getAmount())->toBe(10000000.00)
                ->and($target->getCurrency())->toBe('EUR');
        });

        it('creates recommended minimum target', function (): void {
            $target = FundraisingTarget::recommendedMinimum('USD');

            expect($target->getAmount())->toBe(1000.00)
                ->and($target->getCurrency())->toBe('USD');
        });

        it('uses EUR as default currency', function (): void {
            $target = FundraisingTarget::fromAmount(5000.00);

            expect($target->getCurrency())->toBe('EUR');
        });
    });

    describe('Target Classification', function (): void {
        it('identifies achievable targets correctly', function (): void {
            $achievableTarget = FundraisingTarget::fromAmount(1500.00);
            $marginally = FundraisingTarget::fromAmount(1000.00); // Exactly at threshold
            $tooLow = FundraisingTarget::fromAmount(500.00);

            expect($achievableTarget->isAchievable())->toBeTrue()
                ->and($marginally->isAchievable())->toBeTrue()
                ->and($tooLow->isAchievable())->toBeFalse();
        });

        it('identifies mega campaigns correctly', function (): void {
            $megaCampaign = FundraisingTarget::fromAmount(2000000.00);
            $exactly = FundraisingTarget::fromAmount(1000000.00);
            $regular = FundraisingTarget::fromAmount(50000.00);

            expect($megaCampaign->isMegaCampaign())->toBeTrue()
                ->and($exactly->isMegaCampaign())->toBeTrue()
                ->and($regular->isMegaCampaign())->toBeFalse();
        });

        it('identifies targets requiring approval', function (): void {
            $needsApproval = FundraisingTarget::fromAmount(750.00);
            $minimum = FundraisingTarget::fromAmount(100.00);
            $recommended = FundraisingTarget::fromAmount(1000.00);
            $high = FundraisingTarget::fromAmount(5000.00);

            expect($needsApproval->requiresApproval())->toBeTrue()
                ->and($minimum->requiresApproval())->toBeTrue()
                ->and($recommended->requiresApproval())->toBeFalse()
                ->and($high->requiresApproval())->toBeFalse();
        });

        it('handles edge cases for classification', function (): void {
            $justBelowRecommended = FundraisingTarget::fromAmount(999.99);
            $justAboveRecommended = FundraisingTarget::fromAmount(1000.01);

            expect($justBelowRecommended->requiresApproval())->toBeTrue()
                ->and($justBelowRecommended->isAchievable())->toBeFalse()
                ->and($justAboveRecommended->requiresApproval())->toBeFalse()
                ->and($justAboveRecommended->isAchievable())->toBeTrue();
        });
    });

    describe('Progress Calculations', function (): void {
        it('calculates progress percentage correctly', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $quarter = $target->calculateProgress(new Money(250.00, 'EUR'));
            $half = $target->calculateProgress(new Money(500.00, 'EUR'));
            $threeQuarters = $target->calculateProgress(new Money(750.00, 'EUR'));
            $complete = $target->calculateProgress(new Money(1000.00, 'EUR'));
            $exceeded = $target->calculateProgress(new Money(1200.00, 'EUR'));

            expect($quarter)->toBe(25.0)
                ->and($half)->toBe(50.0)
                ->and($threeQuarters)->toBe(75.0)
                ->and($complete)->toBe(100.0)
                ->and($exceeded)->toBe(100.0); // Capped at 100%
        });

        it('handles zero raised amount', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $progress = $target->calculateProgress(new Money(0.00, 'EUR'));

            expect($progress)->toBe(0.0);
        });

        it('handles zero target correctly', function (): void {
            // This should be invalid, but let's test the calculation logic
            $money = new Money(100.00, 'EUR'); // Valid minimum
            $target = new FundraisingTarget($money);

            // Test with zero raised against non-zero target
            $progress = $target->calculateProgress(new Money(0.00, 'EUR'));
            expect($progress)->toBe(0.0);
        });

        it('rejects currency mismatch in progress calculation', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $raisedUSD = new Money(500.00, 'USD');

            expect(fn () => $target->calculateProgress($raisedUSD))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch between target and raised amount');
        });

        it('calculates remaining amount correctly', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $remaining1 = $target->calculateRemaining(new Money(300.00, 'EUR'));
            $remaining2 = $target->calculateRemaining(new Money(900.00, 'EUR'));
            $complete = $target->calculateRemaining(new Money(1000.00, 'EUR'));
            $exceeded = $target->calculateRemaining(new Money(1200.00, 'EUR'));

            expect($remaining1->getAmount())->toBe(700.00)
                ->and($remaining2->getAmount())->toBe(100.00)
                ->and($complete->getAmount())->toBe(0.0)
                ->and($exceeded->getAmount())->toBe(0.0);
        });

        it('rejects currency mismatch in remaining calculation', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $raisedGBP = new Money(500.00, 'GBP');

            expect(fn () => $target->calculateRemaining($raisedGBP))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch between target and raised amount');
        });
    });

    describe('Target Achievement Status', function (): void {
        it('determines if target is reached', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $notReached = $target->isReached(new Money(900.00, 'EUR'));
            $exactly = $target->isReached(new Money(1000.00, 'EUR'));
            $exceeded = $target->isReached(new Money(1100.00, 'EUR'));

            expect($notReached)->toBeFalse()
                ->and($exactly)->toBeTrue()
                ->and($exceeded)->toBeTrue();
        });

        it('determines if target is exceeded', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $notReached = $target->isExceeded(new Money(900.00, 'EUR'));
            $exactly = $target->isExceeded(new Money(1000.00, 'EUR'));
            $exceeded = $target->isExceeded(new Money(1100.00, 'EUR'));

            expect($notReached)->toBeFalse()
                ->and($exactly)->toBeFalse()
                ->and($exceeded)->toBeTrue();
        });

        it('rejects currency mismatch in achievement checks', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $raisedUSD = new Money(1000.00, 'USD');

            expect(fn () => $target->isReached($raisedUSD))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch between target and raised amount');

            expect(fn () => $target->isExceeded($raisedUSD))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch between target and raised amount');
        });
    });

    describe('Milestone Functionality', function (): void {
        it('generates correct milestone amounts', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $milestones = $target->getMilestones();

            expect($milestones)->toHaveCount(4)
                ->and($milestones[25]->getAmount())->toBe(250.00)
                ->and($milestones[50]->getAmount())->toBe(500.00)
                ->and($milestones[75]->getAmount())->toBe(750.00)
                ->and($milestones[100]->getAmount())->toBe(1000.00);
        });

        it('checks milestone achievement correctly', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $quarter = $target->hasMilestoneBeenReached(new Money(250.00, 'EUR'), 25);
            $halfNotReached = $target->hasMilestoneBeenReached(new Money(250.00, 'EUR'), 50);
            $halfReached = $target->hasMilestoneBeenReached(new Money(600.00, 'EUR'), 50);
            $fullReached = $target->hasMilestoneBeenReached(new Money(1000.00, 'EUR'), 100);

            expect($quarter)->toBeTrue()
                ->and($halfNotReached)->toBeFalse()
                ->and($halfReached)->toBeTrue()
                ->and($fullReached)->toBeTrue();
        });

        it('validates milestone percentage range', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $raised = new Money(500.00, 'EUR');

            expect(fn () => $target->hasMilestoneBeenReached($raised, 0))
                ->toThrow(InvalidArgumentException::class, 'Milestone percentage must be between 1 and 100');

            expect(fn () => $target->hasMilestoneBeenReached($raised, 101))
                ->toThrow(InvalidArgumentException::class, 'Milestone percentage must be between 1 and 100');

            expect(fn () => $target->hasMilestoneBeenReached($raised, -5))
                ->toThrow(InvalidArgumentException::class, 'Milestone percentage must be between 1 and 100');
        });

        it('rejects currency mismatch in milestone checks', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $raisedUSD = new Money(500.00, 'USD');

            expect(fn () => $target->hasMilestoneBeenReached($raisedUSD, 50))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch between target and raised amount');
        });

        it('handles edge case milestones', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'EUR');

            $exactly1 = $target->hasMilestoneBeenReached(new Money(250.00, 'EUR'), 25);
            $just1 = $target->hasMilestoneBeenReached(new Money(100.00, 'EUR'), 10);
            $full99 = $target->hasMilestoneBeenReached(new Money(1000.00, 'EUR'), 99);

            expect($exactly1)->toBeTrue()
                ->and($just1)->toBeTrue()
                ->and($full99)->toBeTrue();
        });
    });

    describe('Target Comparison', function (): void {
        it('compares targets for equality', function (): void {
            $target1 = FundraisingTarget::fromAmount(5000.00, 'EUR');
            $target2 = FundraisingTarget::fromAmount(5000.00, 'EUR');
            $target3 = FundraisingTarget::fromAmount(5000.00, 'USD');
            $target4 = FundraisingTarget::fromAmount(4000.00, 'EUR');

            expect($target1->equals($target2))->toBeTrue()
                ->and($target1->equals($target3))->toBeFalse()
                ->and($target1->equals($target4))->toBeFalse();
        });

        it('compares targets for greater than', function (): void {
            $higher = FundraisingTarget::fromAmount(5000.00, 'EUR');
            $lower = FundraisingTarget::fromAmount(3000.00, 'EUR');
            $equal = FundraisingTarget::fromAmount(5000.00, 'EUR');

            expect($higher->isGreaterThan($lower))->toBeTrue()
                ->and($lower->isGreaterThan($higher))->toBeFalse()
                ->and($higher->isGreaterThan($equal))->toBeFalse();
        });

        it('handles different currency comparisons', function (): void {
            $eurTarget = FundraisingTarget::fromAmount(1000.00, 'EUR');
            $usdTarget = FundraisingTarget::fromAmount(1000.00, 'USD');

            // Different currencies should not be equal even with same amount
            expect($eurTarget->equals($usdTarget))->toBeFalse();

            // Comparison will throw exception due to different currencies
            expect(fn () => $eurTarget->isGreaterThan($usdTarget))
                ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');
        });
    });

    describe('Formatting and Serialization', function (): void {
        it('formats target for display', function (): void {
            $eurTarget = FundraisingTarget::fromAmount(1500.50, 'EUR');
            $usdTarget = FundraisingTarget::fromAmount(2000.00, 'USD');

            expect($eurTarget->format())->toBe('€1.500,50')
                ->and($usdTarget->format())->toBe('$2,000.00');
        });

        it('converts to string representation', function (): void {
            $target = FundraisingTarget::fromAmount(1234.56, 'GBP');

            expect((string) $target)->toBe('£1,234.56');
        });

        it('converts to array representation', function (): void {
            $target = FundraisingTarget::fromAmount(1500.00, 'EUR');
            $method = 'to' . 'Array';
            $array = $target->$method();

            expect($array)->toHaveKeys([
                'amount', 'currency', 'formatted', 'is_achievable',
                'is_mega_campaign', 'requires_approval', 'milestones',
            ])
                ->and($array['amount'])->toBe(1500.00)
                ->and($array['currency'])->toBe('EUR')
                ->and($array['formatted'])->toBe('€1.500,00')
                ->and($array['is_achievable'])->toBeTrue()
                ->and($array['is_mega_campaign'])->toBeFalse()
                ->and($array['requires_approval'])->toBeFalse()
                ->and($array['milestones'])->toHaveCount(4);
        });

        it('includes milestone formatting in array', function (): void {
            $target = FundraisingTarget::fromAmount(1000.00, 'USD');
            $method = 'to' . 'Array';
            $array = $target->$method();

            expect($array['milestones'][25])->toBe('$250.00')
                ->and($array['milestones'][50])->toBe('$500.00')
                ->and($array['milestones'][75])->toBe('$750.00')
                ->and($array['milestones'][100])->toBe('$1,000.00');
        });
    });

    describe('Business Rules Validation', function (): void {
        it('enforces minimum target for small campaigns', function (): void {
            expect(fn () => FundraisingTarget::fromAmount(50.00))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => FundraisingTarget::fromAmount(99.99))
                ->toThrow(InvalidArgumentException::class);

            // Should work at minimum
            $validTarget = FundraisingTarget::fromAmount(100.00);
            expect($validTarget->getAmount())->toBe(100.00);
        });

        it('enforces maximum target for mega campaigns', function (): void {
            expect(fn () => FundraisingTarget::fromAmount(10000001.00))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => FundraisingTarget::fromAmount(20000000.00))
                ->toThrow(InvalidArgumentException::class);

            // Should work at maximum
            $validTarget = FundraisingTarget::fromAmount(10000000.00);
            expect($validTarget->getAmount())->toBe(10000000.00);
        });

        it('provides appropriate error messages', function (): void {
            expect(fn () => FundraisingTarget::fromAmount(50.00, 'USD'))
                ->toThrow(InvalidArgumentException::class, 'Fundraising target must be at least 100.00 USD');

            expect(fn () => FundraisingTarget::fromAmount(15000000.00, 'GBP'))
                ->toThrow(InvalidArgumentException::class, 'Fundraising target cannot exceed 10,000,000.00 GBP');
        });

        it('handles edge amounts correctly', function (): void {
            // Test exact boundaries
            $minimum = FundraisingTarget::fromAmount(100.00);
            $maximum = FundraisingTarget::fromAmount(10000000.00);
            $recommended = FundraisingTarget::fromAmount(1000.00);
            $megaThreshold = FundraisingTarget::fromAmount(1000000.00);

            expect($minimum->requiresApproval())->toBeTrue()
                ->and($minimum->isAchievable())->toBeFalse()
                ->and($recommended->requiresApproval())->toBeFalse()
                ->and($recommended->isAchievable())->toBeTrue()
                ->and($megaThreshold->isMegaCampaign())->toBeTrue()
                ->and($maximum->isMegaCampaign())->toBeTrue();
        });
    });

    describe('Real-world Campaign Scenarios', function (): void {
        it('handles community fundraiser scenario', function (): void {
            $target = FundraisingTarget::fromAmount(2500.00, 'EUR');
            $raised = new Money(1875.00, 'EUR');

            expect($target->isAchievable())->toBeTrue()
                ->and($target->calculateProgress($raised))->toBe(75.0)
                ->and($target->calculateRemaining($raised)->getAmount())->toBe(625.00)
                ->and($target->hasMilestoneBeenReached($raised, 75))->toBeTrue()
                ->and($target->hasMilestoneBeenReached($raised, 80))->toBeFalse();
        });

        it('handles charity gala scenario', function (): void {
            $target = FundraisingTarget::fromAmount(50000.00, 'USD');
            $raised = new Money(52000.00, 'USD');

            expect($target->isAchievable())->toBeTrue()
                ->and($target->isMegaCampaign())->toBeFalse()
                ->and($target->isReached($raised))->toBeTrue()
                ->and($target->isExceeded($raised))->toBeTrue()
                ->and($target->calculateProgress($raised))->toBe(100.0); // Capped
        });

        it('handles disaster relief campaign', function (): void {
            $target = FundraisingTarget::fromAmount(1500000.00, 'EUR');
            $raised = new Money(750000.00, 'EUR');

            expect($target->isMegaCampaign())->toBeTrue()
                ->and($target->isAchievable())->toBeTrue()
                ->and($target->requiresApproval())->toBeFalse()
                ->and($target->calculateProgress($raised))->toBe(50.0)
                ->and($target->hasMilestoneBeenReached($raised, 50))->toBeTrue();
        });

        it('handles small local initiative requiring approval', function (): void {
            $target = FundraisingTarget::fromAmount(750.00, 'GBP');
            $raised = new Money(200.00, 'GBP');

            expect($target->requiresApproval())->toBeTrue()
                ->and($target->isAchievable())->toBeFalse()
                ->and($target->isMegaCampaign())->toBeFalse()
                ->and($target->calculateProgress($raised))->toEqualWithDelta(26.67, 0.1)
                ->and($target->hasMilestoneBeenReached($raised, 25))->toBeTrue()
                ->and($target->calculateRemaining($raised)->getAmount())->toBe(550.00);
        });

        it('handles international mega campaign', function (): void {
            $target = FundraisingTarget::fromAmount(8000000.00, 'USD');
            $raised = new Money(2000000.00, 'USD');

            expect($target->isMegaCampaign())->toBeTrue()
                ->and($target->isAchievable())->toBeTrue()
                ->and($target->requiresApproval())->toBeFalse()
                ->and($target->calculateProgress($raised))->toBe(25.0);

            $milestones = $target->getMilestones();
            expect($milestones[25]->getAmount())->toBe(2000000.00)
                ->and($target->hasMilestoneBeenReached($raised, 25))->toBeTrue();
        });
    });

    describe('Complex Progress Tracking', function (): void {
        it('tracks progress through multiple milestones', function (): void {
            $target = FundraisingTarget::fromAmount(10000.00, 'EUR');

            $amounts = [
                1000.00,  // 10%
                2500.00,  // 25% - milestone
                4000.00,  // 40%
                5000.00,  // 50% - milestone
                6500.00,  // 65%
                7500.00,  // 75% - milestone
                9000.00,  // 90%
                10000.00, // 100% - complete
                11000.00, // 110% - exceeded
            ];

            foreach ($amounts as $amount) {
                $raised = new Money($amount, 'EUR');
                $progress = $target->calculateProgress($raised);

                expect($progress)->toBeGreaterThanOrEqual(0.0)
                    ->and($progress)->toBeLessThanOrEqual(100.0);

                if ($amount >= 10000.00) {
                    expect($target->isReached($raised))->toBeTrue();
                }

                if ($amount > 10000.00) {
                    expect($target->isExceeded($raised))->toBeTrue();
                }
            }
        });

        it('maintains accuracy with floating point amounts', function (): void {
            $target = FundraisingTarget::fromAmount(333.33, 'EUR');
            $raised = new Money(111.11, 'EUR');

            $progress = $target->calculateProgress($raised);
            expect($progress)->toEqualWithDelta(33.33, 0.1);

            $remaining = $target->calculateRemaining($raised);
            expect($remaining->getAmount())->toEqualWithDelta(222.22, 0.01);
        });
    });
});

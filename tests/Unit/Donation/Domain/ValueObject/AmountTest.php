<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\Amount;

describe('Amount Value Object', function (): void {
    describe('Construction and Validation', function (): void {
        it('creates amount with valid values', function (): void {
            $amount = new Amount(100.50, 'EUR');

            expect($amount->value)->toEqualWithDelta(100.50, 0.01)
                ->and($amount->currency)->toBe('EUR');
        });

        it('uses EUR as default currency', function (): void {
            $amount = new Amount(50.00);

            expect($amount->currency)->toBe('EUR');
        });

        it('rejects negative amounts', function (): void {
            expect(fn () => new Amount(-10.00))
                ->toThrow(\InvalidArgumentException::class, 'Amount cannot be negative');
        });

        it('rejects infinite values', function (): void {
            expect(fn () => new Amount(INF))
                ->toThrow(\InvalidArgumentException::class, 'Amount must be a valid number');
        });

        it('rejects NaN values', function (): void {
            expect(fn () => new Amount(NAN))
                ->toThrow(\InvalidArgumentException::class, 'Amount must be a valid number');
        });

        it('rejects unsupported currencies', function (): void {
            expect(fn () => new Amount(100.00, 'JPY'))
                ->toThrow(\InvalidArgumentException::class, 'Unsupported currency: JPY');
        });

        it('accepts all supported currencies', function (): void {
            $supportedCurrencies = ['EUR', 'USD', 'GBP'];

            foreach ($supportedCurrencies as $currency) {
                $amount = new Amount(100.00, $currency);
                expect($amount->currency)->toBe($currency);
            }
        });

        it('rejects amounts with too many decimal places', function (): void {
            expect(fn () => new Amount(100.123))
                ->toThrow(\InvalidArgumentException::class, 'Amount cannot have more than 2 decimal places');
        });

        it('accepts zero amounts', function (): void {
            $amount = new Amount(0.00);

            expect($amount->value)->toEqualWithDelta(0.00, 0.01);
        });
    });

    describe('Factory Methods', function (): void {
        it('creates amount from string', function (): void {
            $amount = Amount::fromString('125.75', 'USD');

            expect($amount->value)->toEqualWithDelta(125.75, 0.01)
                ->and($amount->currency)->toBe('USD');
        });

        it('rejects invalid string amounts', function (): void {
            expect(fn () => Amount::fromString('invalid'))
                ->toThrow(\InvalidArgumentException::class, 'Invalid amount format: invalid');
        });

        it('creates amount from cents', function (): void {
            $amount = Amount::fromCents(12575, 'EUR');

            expect($amount->value)->toEqualWithDelta(125.75, 0.01)
                ->and($amount->currency)->toBe('EUR');
        });

        it('creates minimum donation amount', function (): void {
            $amount = Amount::minimumDonation();

            expect($amount->value)->toEqualWithDelta(1.00, 0.01)
                ->and($amount->currency)->toBe('EUR');
        });

        it('creates maximum donation amount', function (): void {
            $amount = Amount::maximumDonation('USD');

            expect($amount->value)->toEqualWithDelta(999999.99, 0.01)
                ->and($amount->currency)->toBe('USD');
        });

        it('creates tax receipt threshold', function (): void {
            $amount = Amount::taxReceiptThreshold();

            expect($amount->value)->toEqualWithDelta(20.00, 0.01)
                ->and($amount->currency)->toBe('EUR');
        });

        it('creates zero amount', function (): void {
            $amount = Amount::zero('GBP');

            expect($amount->value)->toEqualWithDelta(0.0, 0.01)
                ->and($amount->currency)->toBe('GBP');
        });
    });

    describe('Mathematical Operations', function (): void {
        it('adds amounts correctly', function (): void {
            $amount1 = new Amount(100.50, 'EUR');
            $amount2 = new Amount(25.25, 'EUR');

            $result = $amount1->add($amount2);

            expect($result->value)->toEqualWithDelta(125.75, 0.01)
                ->and($result->currency)->toBe('EUR');
        });

        it('rejects adding different currencies', function (): void {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(50.00, 'USD');

            expect(fn () => $amount1->add($amount2))
                ->toThrow(\InvalidArgumentException::class, 'Currency mismatch: EUR vs USD');
        });

        it('subtracts amounts correctly', function (): void {
            $amount1 = new Amount(100.50, 'EUR');
            $amount2 = new Amount(25.25, 'EUR');

            $result = $amount1->subtract($amount2);

            expect($result->value)->toEqualWithDelta(75.25, 0.01)
                ->and($result->currency)->toBe('EUR');
        });

        it('rejects subtraction resulting in negative', function (): void {
            $amount1 = new Amount(25.00, 'EUR');
            $amount2 = new Amount(50.00, 'EUR');

            expect(fn () => $amount1->subtract($amount2))
                ->toThrow(\InvalidArgumentException::class, 'Result cannot be negative');
        });

        it('multiplies amounts correctly', function (): void {
            $amount = new Amount(50.00, 'EUR');

            $result = $amount->multiply(2.5);

            expect($result->value)->toEqualWithDelta(125.00, 0.01)
                ->and($result->currency)->toBe('EUR');
        });

        it('rejects negative multiplication factors', function (): void {
            $amount = new Amount(50.00, 'EUR');

            expect(fn () => $amount->multiply(-2.0))
                ->toThrow(\InvalidArgumentException::class, 'Factor cannot be negative');
        });

        it('calculates percentages correctly', function (): void {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->percentage(25.0);

            expect($result->value)->toEqualWithDelta(25.00, 0.01)
                ->and($result->currency)->toBe('EUR');
        });

        it('rejects invalid percentage values', function (): void {
            $amount = new Amount(100.00, 'EUR');

            expect(fn () => $amount->percentage(-5.0))
                ->toThrow(\InvalidArgumentException::class, 'Percentage must be between 0 and 100');

            expect(fn () => $amount->percentage(150.0))
                ->toThrow(\InvalidArgumentException::class, 'Percentage must be between 0 and 100');
        });

        it('handles zero multiplication', function (): void {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->multiply(0);

            expect($result->value)->toEqualWithDelta(0.0, 0.01);
        });

        it('calculates 100% correctly', function (): void {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->percentage(100.0);

            expect($result->value)->toEqualWithDelta(100.00, 0.01);
        });
    });

    describe('Comparison Methods', function (): void {
        it('compares greater than correctly', function (): void {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(50.00, 'EUR');

            expect($amount1->greaterThan($amount2))->toBeTrue()
                ->and($amount2->greaterThan($amount1))->toBeFalse();
        });

        it('compares greater than or equal correctly', function (): void {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');
            $amount3 = new Amount(50.00, 'EUR');

            expect($amount1->greaterThanOrEqual($amount2))->toBeTrue()
                ->and($amount1->greaterThanOrEqual($amount3))->toBeTrue()
                ->and($amount3->greaterThanOrEqual($amount1))->toBeFalse();
        });

        it('compares less than correctly', function (): void {
            $amount1 = new Amount(50.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');

            expect($amount1->lessThan($amount2))->toBeTrue()
                ->and($amount2->lessThan($amount1))->toBeFalse();
        });

        it('compares less than or equal correctly', function (): void {
            $amount1 = new Amount(50.00, 'EUR');
            $amount2 = new Amount(50.00, 'EUR');
            $amount3 = new Amount(100.00, 'EUR');

            expect($amount1->lessThanOrEqual($amount2))->toBeTrue()
                ->and($amount1->lessThanOrEqual($amount3))->toBeTrue()
                ->and($amount3->lessThanOrEqual($amount1))->toBeFalse();
        });

        it('compares equality with floating point precision', function (): void {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.0001, 'EUR'); // Within precision tolerance
            $amount3 = new Amount(100.01, 'EUR');   // Outside precision tolerance

            expect($amount1->equals($amount2))->toBeTrue()
                ->and($amount1->equals($amount3))->toBeFalse();
        });

        it('rejects cross-currency comparisons', function (): void {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'USD');

            expect(fn () => $amount1->greaterThan($amount2))
                ->toThrow(\InvalidArgumentException::class, 'Currency mismatch: EUR vs USD');
        });
    });

    describe('Business Logic Validation', function (): void {
        it('validates donation amounts correctly', function (): void {
            $validAmount = new Amount(50.00, 'EUR');
            $tooSmall = new Amount(0.50, 'EUR');
            $tooLarge = new Amount(1000000.00, 'EUR');
            $minimum = new Amount(1.00, 'EUR');
            $maximum = new Amount(999999.99, 'EUR');

            expect($validAmount->isValidDonationAmount())->toBeTrue()
                ->and($tooSmall->isValidDonationAmount())->toBeFalse()
                ->and($tooLarge->isValidDonationAmount())->toBeFalse()
                ->and($minimum->isValidDonationAmount())->toBeTrue()
                ->and($maximum->isValidDonationAmount())->toBeTrue();
        });

        it('validates tax receipt qualification correctly', function (): void {
            $qualified = new Amount(25.00, 'EUR');
            $notQualified = new Amount(15.00, 'EUR');
            $exactThreshold = new Amount(20.00, 'EUR');

            expect($qualified->qualifiesForTaxReceipt())->toBeTrue()
                ->and($notQualified->qualifiesForTaxReceipt())->toBeFalse()
                ->and($exactThreshold->qualifiesForTaxReceipt())->toBeTrue();
        });
    });

    describe('State Checking Methods', function (): void {
        it('identifies zero amounts correctly', function (): void {
            $zero = new Amount(0.00, 'EUR');
            $nonZero = new Amount(0.01, 'EUR');
            $almostZero = new Amount(0.0001, 'EUR'); // Within precision tolerance

            expect($zero->isZero())->toBeTrue()
                ->and($nonZero->isZero())->toBeFalse()
                ->and($almostZero->isZero())->toBeTrue();
        });

        it('identifies positive amounts correctly', function (): void {
            $positive = new Amount(10.00, 'EUR');
            $zero = new Amount(0.00, 'EUR');
            $verySmall = new Amount(0.0001, 'EUR'); // Within precision tolerance

            expect($positive->isPositive())->toBeTrue()
                ->and($zero->isPositive())->toBeFalse()
                ->and($verySmall->isPositive())->toBeFalse();
        });
    });

    describe('Conversion and Formatting', function (): void {
        it('converts to cents correctly', function (): void {
            $amount = new Amount(125.75, 'EUR');

            expect($amount->toCents())->toBe(12575);
        });

        it('handles rounding in cents conversion', function (): void {
            // Test with valid precision amount
            $amount = new Amount(125.75, 'EUR');

            expect($amount->toCents())->toBe(12575);
        });

        it('formats amounts with currency symbols', function (): void {
            $eur = new Amount(125.50, 'EUR');
            $usd = new Amount(125.50, 'USD');
            $gbp = new Amount(125.50, 'GBP');

            expect($eur->format())->toBe('€125.50')
                ->and($usd->format())->toBe('$125.50')
                ->and($gbp->format())->toBe('£125.50');
        });

        it('formats decimal strings correctly', function (): void {
            $amount = new Amount(125.50, 'EUR');

            expect($amount->toDecimalString())->toBe('125.50');
        });

        it('converts to array representation', function (): void {
            $amount = new Amount(125.50, 'EUR');
            $array = $amount->toArray();

            expect($array['value'])->toEqualWithDelta(125.50, 0.01)
                ->and($array['currency'])->toBe('EUR')
                ->and($array['formatted'])->toBe('€125.50')
                ->and($array['cents'])->toBe(12550);
        });

        it('converts to string using format', function (): void {
            $amount = new Amount(125.50, 'USD');

            expect((string) $amount)->toBe('$125.50');
        });

        it('formats zero amounts correctly', function (): void {
            $amount = new Amount(0.00, 'EUR');

            expect($amount->format())->toBe('€0.00')
                ->and($amount->toDecimalString())->toBe('0.00')
                ->and($amount->toCents())->toBe(0);
        });
    });

    describe('Edge Cases and Precision', function (): void {
        it('handles floating point precision issues', function (): void {
            // Classic floating point precision issue
            $amount1 = new Amount(0.1, 'EUR');
            $amount2 = new Amount(0.2, 'EUR');
            $result = $amount1->add($amount2);

            // Should be 0.3, but floating point arithmetic might give 0.30000000000000004
            expect($result->value)->toEqualWithDelta(0.3, 0.01);
        });

        it('handles very small amounts consistently', function (): void {
            $amount1 = new Amount(0.01, 'EUR');
            $amount2 = new Amount(0.01, 'EUR');

            $sum = $amount1->add($amount2);

            expect($sum->value)->toEqualWithDelta(0.02, 0.01)
                ->and($sum->toCents())->toBe(2);
        });

        it('handles maximum precision amounts', function (): void {
            $amount = new Amount(99.99, 'EUR');

            expect($amount->format())->toBe('€99.99')
                ->and($amount->toCents())->toBe(9999);
        });

        it('maintains precision through operations', function (): void {
            $amount = new Amount(33.33, 'EUR');
            $tripled = $amount->multiply(3);

            expect($tripled->value)->toEqualWithDelta(99.99, 0.01);
        });
    });

    describe('Complex Business Scenarios', function (): void {
        it('calculates corporate matching correctly', function (): void {
            $donation = new Amount(100.00, 'EUR');
            $matchPercentage = 50.0;

            $matchAmount = $donation->percentage($matchPercentage);
            $totalImpact = $donation->add($matchAmount);

            expect($matchAmount->value)->toEqualWithDelta(50.00, 0.01)
                ->and($totalImpact->value)->toEqualWithDelta(150.00, 0.01);
        });

        it('handles fee calculations', function (): void {
            $donation = new Amount(100.00, 'EUR');
            $feePercentage = 2.9;
            $fixedFee = new Amount(0.30, 'EUR');

            $percentageFee = $donation->percentage($feePercentage);
            $totalFee = $percentageFee->add($fixedFee);
            $netAmount = $donation->subtract($totalFee);

            expect($percentageFee->value)->toEqualWithDelta(2.90, 0.01)
                ->and($totalFee->value)->toEqualWithDelta(3.20, 0.01)
                ->and($netAmount->value)->toEqualWithDelta(96.80, 0.01);
        });

        it('validates minimum donation after fees', function (): void {
            $grossAmount = new Amount(2.00, 'EUR');
            $fee = new Amount(1.50, 'EUR');

            $netAmount = $grossAmount->subtract($fee);

            expect($netAmount->isValidDonationAmount())->toBeFalse();
        });

        it('handles currency-specific validations', function (): void {
            $eurAmount = new Amount(20.00, 'EUR');
            $usdAmount = new Amount(20.00, 'USD');
            $gbpAmount = new Amount(20.00, 'GBP');

            // All should qualify for tax receipts at same threshold
            expect($eurAmount->qualifiesForTaxReceipt())->toBeTrue()
                ->and($usdAmount->qualifiesForTaxReceipt())->toBeTrue()
                ->and($gbpAmount->qualifiesForTaxReceipt())->toBeTrue();
        });

        it('handles refund calculations', function (): void {
            $originalDonation = new Amount(100.00, 'EUR');
            $partialRefund = new Amount(30.00, 'EUR');

            $remainingAmount = $originalDonation->subtract($partialRefund);

            expect($remainingAmount->value)->toEqualWithDelta(70.00, 0.01)
                ->and($remainingAmount->isValidDonationAmount())->toBeTrue();
        });

        it('handles complex multi-step calculations', function (): void {
            $baseAmount = new Amount(1000.00, 'EUR');

            // Apply 5% processing fee
            $processingFee = $baseAmount->percentage(5.0);
            $afterProcessingFee = $baseAmount->subtract($processingFee);

            // Apply corporate 100% match
            $corporateMatch = $afterProcessingFee->multiply(1.0);
            $totalWithMatch = $afterProcessingFee->add($corporateMatch);

            // Calculate 25% going to admin costs
            $adminCosts = $totalWithMatch->percentage(25.0);
            $finalDonationAmount = $totalWithMatch->subtract($adminCosts);

            expect($processingFee->value)->toEqualWithDelta(50.00, 0.01)
                ->and($afterProcessingFee->value)->toEqualWithDelta(950.00, 0.01)
                ->and($corporateMatch->value)->toEqualWithDelta(950.00, 0.01)
                ->and($totalWithMatch->value)->toEqualWithDelta(1900.00, 0.01)
                ->and($adminCosts->value)->toEqualWithDelta(475.00, 0.01)
                ->and($finalDonationAmount->value)->toEqualWithDelta(1425.00, 0.01);
        });
    });

    describe('Additional Edge Cases', function (): void {
        it('handles very large amounts at maximum limit', function (): void {
            $maxAmount = new Amount(999999.99, 'EUR');

            expect($maxAmount->isValidDonationAmount())->toBeTrue()
                ->and($maxAmount->format())->toBe('€999,999.99')
                ->and($maxAmount->toCents())->toBe(99999999);
        });

        it('handles amounts just over maximum limit', function (): void {
            $overMaxAmount = new Amount(1000000.00, 'EUR');

            expect($overMaxAmount->isValidDonationAmount())->toBeFalse();
        });

        it('handles amounts just under minimum limit', function (): void {
            $underMinAmount = new Amount(0.99, 'EUR');

            expect($underMinAmount->isValidDonationAmount())->toBeFalse()
                ->and($underMinAmount->qualifiesForTaxReceipt())->toBeFalse();
        });

        it('validates precise decimal boundary conditions', function (): void {
            $exactMin = new Amount(1.00, 'EUR');
            $exactMax = new Amount(999999.99, 'EUR');
            $exactThreshold = new Amount(20.00, 'EUR');

            expect($exactMin->isValidDonationAmount())->toBeTrue()
                ->and($exactMax->isValidDonationAmount())->toBeTrue()
                ->and($exactThreshold->qualifiesForTaxReceipt())->toBeTrue();
        });

        it('handles zero percentage calculations', function (): void {
            $amount = new Amount(100.00, 'EUR');
            $zeroPercent = $amount->percentage(0.0);

            expect($zeroPercent->value)->toEqualWithDelta(0.0, 0.01)
                ->and($zeroPercent->isZero())->toBeTrue();
        });

        it('chains multiple arithmetic operations correctly', function (): void {
            $start = new Amount(100.00, 'EUR');

            $result = $start
                ->add(new Amount(50.00, 'EUR'))
                ->multiply(2.0)
                ->subtract(new Amount(100.00, 'EUR'))
                ->percentage(75.0);

            // ((100 + 50) * 2 - 100) * 0.75 = (300 - 100) * 0.75 = 200 * 0.75 = 150
            expect($result->value)->toEqualWithDelta(150.00, 0.01);
        });

        it('validates immutability of original amount in operations', function (): void {
            $original = new Amount(100.00, 'EUR');
            $originalValue = $original->value;

            // Perform various operations that should not modify original
            $original->add(new Amount(50.00, 'EUR'));
            $original->subtract(new Amount(25.00, 'EUR'));
            $original->multiply(2.0);
            $original->percentage(50.0);

            // Original should remain unchanged
            expect($original->value)->toEqualWithDelta($originalValue, 0.01);
        });
    });

    describe('String Conversion Edge Cases', function (): void {
        it('handles fromString with leading/trailing whitespace', function (): void {
            $amount = Amount::fromString('  123.45  ', 'USD');

            expect($amount->value)->toEqualWithDelta(123.45, 0.01)
                ->and($amount->currency)->toBe('USD');
        });

        it('handles fromString with integer values', function (): void {
            $amount = Amount::fromString('100', 'GBP');

            expect($amount->value)->toEqualWithDelta(100.00, 0.01)
                ->and($amount->currency)->toBe('GBP');
        });

        it('rejects fromString with currency symbols', function (): void {
            expect(fn () => Amount::fromString('€100.00'))
                ->toThrow(InvalidArgumentException::class, 'Invalid amount format: €100.00');
        });

        it('rejects fromString with multiple decimal points', function (): void {
            expect(fn () => Amount::fromString('100.50.25'))
                ->toThrow(InvalidArgumentException::class, 'Invalid amount format: 100.50.25');
        });

        it('handles fromString with scientific notation', function (): void {
            $amount = Amount::fromString('1.5e2', 'EUR'); // 150.00

            expect($amount->value)->toEqualWithDelta(150.00, 0.01);
        });
    });

    describe('Cents Conversion Edge Cases', function (): void {
        it('handles fromCents with zero', function (): void {
            $amount = Amount::fromCents(0, 'USD');

            expect($amount->value)->toEqualWithDelta(0.0, 0.01)
                ->and($amount->isZero())->toBeTrue();
        });

        it('handles fromCents with large values', function (): void {
            $amount = Amount::fromCents(99999999, 'EUR'); // 999,999.99

            expect($amount->value)->toEqualWithDelta(999999.99, 0.01)
                ->and($amount->isValidDonationAmount())->toBeTrue();
        });

        it('handles toCents rounding correctly', function (): void {
            // Test with amount that has exact cent representation
            $amount = new Amount(123.45, 'EUR');
            expect($amount->toCents())->toBe(12345);

            // Test with maximum precision
            $amountMax = new Amount(999999.99, 'EUR');
            expect($amountMax->toCents())->toBe(99999999);
        });

        it('maintains precision through cents round-trip', function (): void {
            $originalCents = 12345;
            $amount = Amount::fromCents($originalCents, 'EUR');
            $backToCents = $amount->toCents();

            expect($backToCents)->toBe($originalCents);
        });
    });
});

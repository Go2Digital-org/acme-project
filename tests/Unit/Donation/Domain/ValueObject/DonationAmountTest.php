<?php

declare(strict_types=1);

use Modules\Donation\Domain\ValueObject\Amount;

describe('Amount Value Object', function () {
    describe('Construction and Basic Properties', function () {
        it('can be created with valid amount and currency', function () {
            $amount = new Amount(100.50, 'EUR');

            expect($amount->value)->toBe(100.50)
                ->and($amount->currency)->toBe('EUR')
                ->and($amount->getAmount())->toBe(100.50);
        });

        it('defaults to EUR currency when not specified', function () {
            $amount = new Amount(50.00);

            expect($amount->currency)->toBe('EUR');
        });

        it('throws exception for negative amounts', function () {
            expect(fn () => new Amount(-10.50, 'EUR'))
                ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');
        });

        it('throws exception for infinite values', function () {
            expect(fn () => new Amount(INF, 'EUR'))
                ->toThrow(InvalidArgumentException::class, 'Amount must be a valid number');
        });

        it('throws exception for NaN values', function () {
            expect(fn () => new Amount(NAN, 'EUR'))
                ->toThrow(InvalidArgumentException::class, 'Amount must be a valid number');
        });

        it('throws exception for unsupported currency', function () {
            expect(fn () => new Amount(100.00, 'XYZ'))
                ->toThrow(InvalidArgumentException::class, 'Unsupported currency: XYZ');
        });

        it('supports EUR currency', function () {
            $amount = new Amount(100.00, 'EUR');
            expect($amount->currency)->toBe('EUR');
        });

        it('supports USD currency', function () {
            $amount = new Amount(100.00, 'USD');
            expect($amount->currency)->toBe('USD');
        });

        it('supports GBP currency', function () {
            $amount = new Amount(100.00, 'GBP');
            expect($amount->currency)->toBe('GBP');
        });

        it('validates precision to 2 decimal places', function () {
            $amount = new Amount(100.99, 'EUR');
            expect($amount->value)->toBe(100.99);
        });

        it('allows zero amounts', function () {
            $amount = new Amount(0.00, 'EUR');
            expect($amount->value)->toBe(0.00);
        });
    });

    describe('Static Factory Methods', function () {
        it('can create from string representation', function () {
            $amount = Amount::fromString('123.45', 'USD');

            expect($amount->value)->toBe(123.45)
                ->and($amount->currency)->toBe('USD');
        });

        it('creates from string with default EUR currency', function () {
            $amount = Amount::fromString('99.99');

            expect($amount->currency)->toBe('EUR');
        });

        it('throws exception for invalid string format', function () {
            expect(fn () => Amount::fromString('invalid', 'EUR'))
                ->toThrow(InvalidArgumentException::class, 'Invalid amount format: invalid');
        });

        it('can create from cents', function () {
            $amount = Amount::fromCents(12345, 'USD');

            expect($amount->value)->toBe(123.45)
                ->and($amount->currency)->toBe('USD');
        });

        it('creates minimum donation amount', function () {
            $amount = Amount::minimumDonation('EUR');

            expect($amount->value)->toBe(1.00)
                ->and($amount->currency)->toBe('EUR');
        });

        it('creates maximum donation amount', function () {
            $amount = Amount::maximumDonation('USD');

            expect($amount->value)->toBe(999999.99)
                ->and($amount->currency)->toBe('USD');
        });

        it('creates tax receipt threshold amount', function () {
            $amount = Amount::taxReceiptThreshold('GBP');

            expect($amount->value)->toBe(20.00)
                ->and($amount->currency)->toBe('GBP');
        });

        it('creates zero amount', function () {
            $amount = Amount::zero('EUR');

            expect($amount->value)->toBe(0.0)
                ->and($amount->currency)->toBe('EUR');
        });
    });

    describe('Mathematical Operations', function () {
        it('can add two amounts with same currency', function () {
            $amount1 = new Amount(100.50, 'EUR');
            $amount2 = new Amount(50.25, 'EUR');

            $result = $amount1->add($amount2);

            expect($result->value)->toBe(150.75)
                ->and($result->currency)->toBe('EUR');
        });

        it('throws exception when adding different currencies', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(50.00, 'USD');

            expect(fn () => $amount1->add($amount2))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch: EUR vs USD');
        });

        it('can subtract amounts with same currency', function () {
            $amount1 = new Amount(100.50, 'EUR');
            $amount2 = new Amount(25.25, 'EUR');

            $result = $amount1->subtract($amount2);

            expect($result->value)->toBe(75.25)
                ->and($result->currency)->toBe('EUR');
        });

        it('throws exception when subtracting different currencies', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(50.00, 'USD');

            expect(fn () => $amount1->subtract($amount2))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch: EUR vs USD');
        });

        it('throws exception when subtraction results in negative', function () {
            $amount1 = new Amount(50.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');

            expect(fn () => $amount1->subtract($amount2))
                ->toThrow(InvalidArgumentException::class, 'Result cannot be negative');
        });

        it('can multiply by positive factor', function () {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->multiply(2.5);

            expect($result->value)->toBe(250.00)
                ->and($result->currency)->toBe('EUR');
        });

        it('throws exception when multiplying by negative factor', function () {
            $amount = new Amount(100.00, 'EUR');

            expect(fn () => $amount->multiply(-1.5))
                ->toThrow(InvalidArgumentException::class, 'Factor cannot be negative');
        });

        it('can calculate percentage of amount', function () {
            $amount = new Amount(200.00, 'EUR');

            $result = $amount->percentage(25.0);

            expect($result->value)->toBe(50.00)
                ->and($result->currency)->toBe('EUR');
        });

        it('throws exception for negative percentage', function () {
            $amount = new Amount(100.00, 'EUR');

            expect(fn () => $amount->percentage(-10.0))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');
        });

        it('throws exception for percentage over 100', function () {
            $amount = new Amount(100.00, 'EUR');

            expect(fn () => $amount->percentage(150.0))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');
        });

        it('handles zero percentage', function () {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->percentage(0.0);

            expect($result->value)->toBe(0.00);
        });

        it('handles 100 percentage', function () {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->percentage(100.0);

            expect($result->value)->toBe(100.00);
        });
    });

    describe('Comparison Operations', function () {
        it('correctly compares greater than', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(50.00, 'EUR');

            expect($amount1->greaterThan($amount2))->toBeTrue()
                ->and($amount2->greaterThan($amount1))->toBeFalse();
        });

        it('correctly compares greater than or equal', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');
            $amount3 = new Amount(50.00, 'EUR');

            expect($amount1->greaterThanOrEqual($amount2))->toBeTrue()
                ->and($amount1->greaterThanOrEqual($amount3))->toBeTrue()
                ->and($amount3->greaterThanOrEqual($amount1))->toBeFalse();
        });

        it('correctly compares less than', function () {
            $amount1 = new Amount(50.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');

            expect($amount1->lessThan($amount2))->toBeTrue()
                ->and($amount2->lessThan($amount1))->toBeFalse();
        });

        it('correctly compares less than or equal', function () {
            $amount1 = new Amount(50.00, 'EUR');
            $amount2 = new Amount(50.00, 'EUR');
            $amount3 = new Amount(100.00, 'EUR');

            expect($amount1->lessThanOrEqual($amount2))->toBeTrue()
                ->and($amount1->lessThanOrEqual($amount3))->toBeTrue()
                ->and($amount3->lessThanOrEqual($amount1))->toBeFalse();
        });

        it('correctly compares equality', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');
            $amount3 = new Amount(100.01, 'EUR');

            expect($amount1->equals($amount2))->toBeTrue()
                ->and($amount1->equals($amount3))->toBeFalse();
        });

        it('throws exception when comparing different currencies', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'USD');

            expect(fn () => $amount1->greaterThan($amount2))
                ->toThrow(InvalidArgumentException::class, 'Currency mismatch: EUR vs USD');
        });

        it('handles floating point precision in equality', function () {
            $amount1 = new Amount(100.00, 'EUR');
            $amount2 = new Amount(100.00, 'EUR');

            expect($amount1->equals($amount2))->toBeTrue();
        });
    });

    describe('State Checking Methods', function () {
        it('correctly identifies zero amounts', function () {
            $zeroAmount = new Amount(0.00, 'EUR');
            $nonZeroAmount = new Amount(0.01, 'EUR');

            expect($zeroAmount->isZero())->toBeTrue()
                ->and($nonZeroAmount->isZero())->toBeFalse();
        });

        it('correctly identifies positive amounts', function () {
            $positiveAmount = new Amount(0.01, 'EUR');
            $zeroAmount = new Amount(0.00, 'EUR');

            expect($positiveAmount->isPositive())->toBeTrue()
                ->and($zeroAmount->isPositive())->toBeFalse();
        });

        it('validates donation amounts within range', function () {
            $validAmount = new Amount(50.00, 'EUR');
            $tooSmallAmount = new Amount(0.50, 'EUR');
            $tooLargeAmount = new Amount(1000000.00, 'EUR');

            expect($validAmount->isValidDonationAmount())->toBeTrue()
                ->and($tooSmallAmount->isValidDonationAmount())->toBeFalse()
                ->and($tooLargeAmount->isValidDonationAmount())->toBeFalse();
        });

        it('validates minimum donation amount', function () {
            $minAmount = new Amount(1.00, 'EUR');
            $belowMinAmount = new Amount(0.99, 'EUR');

            expect($minAmount->isValidDonationAmount())->toBeTrue()
                ->and($belowMinAmount->isValidDonationAmount())->toBeFalse();
        });

        it('validates maximum donation amount', function () {
            $maxAmount = new Amount(999999.99, 'EUR');
            $aboveMaxAmount = new Amount(1000000.00, 'EUR');

            expect($maxAmount->isValidDonationAmount())->toBeTrue()
                ->and($aboveMaxAmount->isValidDonationAmount())->toBeFalse();
        });

        it('checks tax receipt qualification', function () {
            $qualifyingAmount = new Amount(25.00, 'EUR');
            $nonQualifyingAmount = new Amount(15.00, 'EUR');
            $thresholdAmount = new Amount(20.00, 'EUR');

            expect($qualifyingAmount->qualifiesForTaxReceipt())->toBeTrue()
                ->and($nonQualifyingAmount->qualifiesForTaxReceipt())->toBeFalse()
                ->and($thresholdAmount->qualifiesForTaxReceipt())->toBeTrue();
        });
    });

    describe('Conversion and Formatting', function () {
        it('converts to cents correctly', function () {
            $amount = new Amount(123.45, 'EUR');

            expect($amount->toCents())->toBe(12345);
        });

        it('handles zero amount conversion to cents', function () {
            $amount = new Amount(0.00, 'EUR');

            expect($amount->toCents())->toBe(0);
        });

        it('formats EUR amounts correctly', function () {
            $amount = new Amount(1234.56, 'EUR');

            expect($amount->format())->toBe('€1,234.56');
        });

        it('formats USD amounts correctly', function () {
            $amount = new Amount(1234.56, 'USD');

            expect($amount->format())->toBe('$1,234.56');
        });

        it('formats GBP amounts correctly', function () {
            $amount = new Amount(1234.56, 'GBP');

            expect($amount->format())->toBe('£1,234.56');
        });

        it('converts to decimal string for storage', function () {
            $amount = new Amount(1234.56, 'EUR');

            expect($amount->toDecimalString())->toBe('1234.56');
        });

        it('converts to array representation', function () {
            $amount = new Amount(123.45, 'EUR');

            $array = $amount->toArray();

            expect($array)->toBeArray()
                ->and($array['value'])->toBe(123.45)
                ->and($array['currency'])->toBe('EUR')
                ->and($array['formatted'])->toBe('€123.45')
                ->and($array['cents'])->toBe(12345);
        });

        it('provides string representation', function () {
            $amount = new Amount(123.45, 'EUR');

            expect((string) $amount)->toBe('€123.45');
        });
    });

    describe('Magic Methods and Compatibility', function () {
        it('provides amount property via magic getter', function () {
            $amount = new Amount(123.45, 'EUR');

            expect($amount->amount)->toBe(123.45);
        });

        it('throws exception for invalid magic property', function () {
            $amount = new Amount(123.45, 'EUR');

            expect(fn () => $amount->invalid_property)
                ->toThrow(InvalidArgumentException::class, 'Property invalid_property does not exist');
        });
    });

    describe('Edge Cases and Precision', function () {
        it('handles very small amounts', function () {
            $amount = new Amount(0.01, 'EUR');

            expect($amount->value)->toBe(0.01)
                ->and($amount->toCents())->toBe(1);
        });

        it('handles large amounts', function () {
            $amount = new Amount(999999.99, 'EUR');

            expect($amount->value)->toBe(999999.99)
                ->and($amount->toCents())->toBe(99999999);
        });

        it('properly rounds floating point operations', function () {
            $amount1 = new Amount(10.1, 'EUR');
            $amount2 = new Amount(20.2, 'EUR');

            $result = $amount1->add($amount2);

            expect($result->value)->toBe(30.3);
        });

        it('handles multiplication rounding correctly', function () {
            $amount = new Amount(10.00, 'EUR');

            $result = $amount->multiply(0.333);

            expect($result->value)->toBe(3.33);
        });

        it('maintains precision in percentage calculations', function () {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount->percentage(33.33);

            expect($result->value)->toBe(33.33);
        });
    });

    describe('Business Rule Validation', function () {
        it('enforces minimum donation amount constant', function () {
            $minAmount = Amount::minimumDonation();

            expect($minAmount->value)->toBe(1.00);
        });

        it('enforces maximum donation amount constant', function () {
            $maxAmount = Amount::maximumDonation();

            expect($maxAmount->value)->toBe(999999.99);
        });

        it('enforces tax receipt threshold constant', function () {
            $thresholdAmount = Amount::taxReceiptThreshold();

            expect($thresholdAmount->value)->toBe(20.00);
        });

        it('validates amount is within business rules for donations', function () {
            $validAmount = new Amount(100.00, 'EUR');
            $invalidMinAmount = new Amount(0.50, 'EUR');
            $invalidMaxAmount = new Amount(1000000.00, 'EUR');

            expect($validAmount->isValidDonationAmount())->toBeTrue();
            expect($invalidMinAmount->isValidDonationAmount())->toBeFalse();
            expect($invalidMaxAmount->isValidDonationAmount())->toBeFalse();
        });
    });

    describe('Currency Symbol Mapping', function () {
        it('maps EUR to euro symbol', function () {
            $amount = new Amount(100.00, 'EUR');

            expect($amount->format())->toContain('€');
        });

        it('maps USD to dollar symbol', function () {
            $amount = new Amount(100.00, 'USD');

            expect($amount->format())->toContain('$');
        });

        it('maps GBP to pound symbol', function () {
            $amount = new Amount(100.00, 'GBP');

            expect($amount->format())->toContain('£');
        });
    });

    describe('Static Factory Method Edge Cases', function () {
        it('handles string with leading/trailing whitespace', function () {
            $amount = Amount::fromString('  123.45  ', 'EUR');

            expect($amount->value)->toBe(123.45);
        });

        it('handles integer string input', function () {
            $amount = Amount::fromString('100', 'EUR');

            expect($amount->value)->toBe(100.0);
        });

        it('handles zero cents conversion', function () {
            $amount = Amount::fromCents(0, 'EUR');

            expect($amount->value)->toBe(0.0);
        });

        it('handles large cents conversion', function () {
            $amount = Amount::fromCents(99999999, 'EUR');

            expect($amount->value)->toBe(999999.99);
        });
    });

    describe('Complex Mathematical Operations', function () {
        it('chains multiple operations correctly', function () {
            $amount = new Amount(100.00, 'EUR');

            $result = $amount
                ->add(new Amount(50.00, 'EUR'))
                ->multiply(2.0)
                ->subtract(new Amount(100.00, 'EUR'));

            expect($result->value)->toBe(200.00);
        });

        it('performs complex percentage calculations', function () {
            $amount = new Amount(1000.00, 'EUR');

            $vatAmount = $amount->percentage(21.0); // 21% VAT
            $totalWithVat = $amount->add($vatAmount);

            expect($vatAmount->value)->toBe(210.00)
                ->and($totalWithVat->value)->toBe(1210.00);
        });
    });
});

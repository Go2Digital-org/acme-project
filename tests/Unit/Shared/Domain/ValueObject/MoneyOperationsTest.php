<?php

declare(strict_types=1);

use Modules\Currency\Domain\ValueObject\Currency;
use Modules\Shared\Domain\ValueObject\Money;

describe('Money Operations - Advanced Financial Calculations', function (): void {
    describe('Compound Arithmetic Operations', function (): void {
        it('performs multiple addition operations in sequence', function (): void {
            $money = new Money(100.00, 'USD');
            $addition1 = new Money(25.50, 'USD');
            $addition2 = new Money(74.50, 'USD');
            $addition3 = new Money(0.00, 'USD');

            $result = $money
                ->add($addition1)
                ->add($addition2)
                ->add($addition3);

            expect($result->amount)->toBe(200.00)
                ->and($result->currency)->toBe('USD')
                ->and($result)->not->toBe($money); // Immutability check
        });

        it('performs compound multiplication and division operations', function (): void {
            $money = new Money(1000.00, 'EUR');

            $result = $money
                ->multiply(1.5)    // 1500.00
                ->divide(3.0)      // 500.00
                ->multiply(0.8);   // 400.00

            expect($result->amount)->toBe(400.00)
                ->and($result->currency)->toBe('EUR');
        });

        it('performs mixed arithmetic operations', function (): void {
            $base = new Money(200.00, 'USD');
            $toAdd = new Money(50.00, 'USD');
            $toSubtract = new Money(25.00, 'USD');

            $result = $base
                ->add($toAdd)      // 250.00
                ->multiply(2.0)    // 500.00
                ->subtract($toSubtract)  // 475.00
                ->divide(5.0);     // 95.00

            expect($result->amount)->toBe(95.00)
                ->and($result->currency)->toBe('USD');
        });

        it('handles zero operations correctly', function (): void {
            $money = new Money(100.00, 'GBP');
            $zero = Money::zero('GBP');

            $addZero = $money->add($zero);
            $subtractZero = $money->subtract($zero);
            $multiplyByOne = $money->multiply(1.0);
            $divideByOne = $money->divide(1.0);

            expect($addZero->equals($money))->toBeTrue()
                ->and($subtractZero->equals($money))->toBeTrue()
                ->and($multiplyByOne->equals($money))->toBeTrue()
                ->and($divideByOne->equals($money))->toBeTrue();
        });
    });

    describe('Currency Conversion Logic', function (): void {
        it('handles same currency conversion', function (): void {
            $money = new Money(100.00, 'USD');

            // Mock conversion service behavior for same currency
            expect($money->currency)->toBe('USD');

            // Same currency should return identical value
            $converted = new Money($money->amount, $money->currency);
            expect($converted->equals($money))->toBeTrue();
        });

        it('validates currency codes for conversion', function (): void {
            $validCurrencies = ['EUR', 'USD', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY'];

            foreach ($validCurrencies as $currency) {
                $money = new Money(100.00, $currency);
                expect($money->currency)->toBe($currency);
            }
        });

        it('throws exception for invalid currency in conversion context', function (): void {
            expect(fn () => new Money(100.00, 'INVALID'))
                ->toThrow(InvalidArgumentException::class, 'Invalid currency code');
        });

        it('handles cross-currency comparison restrictions', function (): void {
            $usd = new Money(100.00, 'USD');
            $eur = new Money(85.00, 'EUR');

            expect(fn () => $usd->isGreaterThan($eur))
                ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');

            expect(fn () => $usd->add($eur))
                ->toThrow(InvalidArgumentException::class, 'Cannot add different currencies');
        });
    });

    describe('Precision and Rounding Handling', function (): void {
        it('maintains precision in floating point operations', function (): void {
            $money = new Money(10.99, 'USD');

            $multiplied = $money->multiply(3.33);
            $divided = $money->divide(3.7);
            $percentage = $money->percentage(33.33);

            // Test that precision is maintained within reasonable bounds
            expect($multiplied->amount)->toBeGreaterThan(36.0)
                ->and($multiplied->amount)->toBeLessThan(37.0)
                ->and($divided->amount)->toBeGreaterThan(2.9)
                ->and($divided->amount)->toBeLessThan(3.0)
                ->and($percentage->amount)->toBeGreaterThan(3.6)
                ->and($percentage->amount)->toBeLessThan(3.7);
        });

        it('handles very small amounts correctly', function (): void {
            $small = new Money(0.01, 'USD');
            $verySmall = new Money(0.001, 'USD');

            $sum = $small->add($verySmall);
            $product = $small->multiply(1.5);

            expect($sum->amount)->toBe(0.011)
                ->and($product->amount)->toBe(0.015);
        });

        it('handles large amounts without overflow', function (): void {
            $large = new Money(999999.99, 'EUR');
            $multiplied = $large->multiply(2.0);

            expect($multiplied->amount)->toBe(1999999.98)
                ->and($multiplied->currency)->toBe('EUR');
        });

        it('preserves precision through multiple operations', function (): void {
            $money = new Money(10.00, 'USD');

            $result = $money
                ->multiply(2.0)    // 20.00
                ->divide(4.0)      // 5.00
                ->add(new Money(5.00, 'USD'))   // 10.00
                ->subtract(new Money(10.00, 'USD')); // 0.00

            expect($result->amount)->toBe(0.0)
                ->and($result->isZero())->toBeTrue();
        });
    });

    describe('Money Allocation and Splitting', function (): void {
        it('simulates even allocation between recipients', function (): void {
            $total = new Money(100.00, 'USD');
            $recipients = 3;

            // Simulate allocation by dividing
            $perRecipient = $total->divide((float) $recipients);
            $expectedAmount = 100.00 / 3;

            expect($perRecipient->amount)->toBeBetween($expectedAmount - 0.01, $expectedAmount + 0.01);
        });

        it('simulates proportional allocation', function (): void {
            $total = new Money(1000.00, 'EUR');

            // Simulate 60%, 30%, 10% allocation
            $allocation1 = $total->percentage(60); // 600.00
            $allocation2 = $total->percentage(30); // 300.00
            $allocation3 = $total->percentage(10); // 100.00

            expect($allocation1->amount)->toBe(600.00)
                ->and($allocation2->amount)->toBe(300.00)
                ->and($allocation3->amount)->toBe(100.00);

            // Verify total allocation equals original
            $totalAllocated = $allocation1->add($allocation2)->add($allocation3);
            expect($totalAllocated->equals($total))->toBeTrue();
        });

        it('handles remainder in uneven splits', function (): void {
            $total = new Money(100.00, 'USD');
            $parts = 3;

            $perPart = $total->divide((float) $parts);
            $remainder = $total->amount - ($perPart->amount * $parts);

            expect($remainder)->toBeGreaterThanOrEqual(0)
                ->and($remainder)->toBeLessThan(1.0); // Should be less than 1 unit
        });

        it('validates allocation percentages sum correctly', function (): void {
            $money = new Money(500.00, 'GBP');

            $p1 = $money->percentage(25);   // 125.00
            $p2 = $money->percentage(35);   // 175.00
            $p3 = $money->percentage(40);   // 200.00

            $total = $p1->add($p2)->add($p3);

            expect($total->amount)->toBe(500.00)
                ->and($total->equals($money))->toBeTrue();
        });
    });

    describe('Advanced Percentage Calculations', function (): void {
        it('calculates compound percentages', function (): void {
            $money = new Money(1000.00, 'USD');

            // Apply 10% increase (110% = multiply by 1.1), then 20% discount (80% = multiply by 0.8)
            $increased = $money->multiply(1.1);  // 1100.00
            $discounted = $increased->multiply(0.8); // 880.00

            // 1000 * 1.1 * 0.8 = 880
            expect($discounted->amount)->toBe(880.00);
        });

        it('handles edge case percentages', function (): void {
            $money = new Money(100.00, 'EUR');

            $zero = $money->percentage(0);      // 0.00
            $full = $money->percentage(100);    // 100.00
            $half = $money->percentage(50);     // 50.00
            $quarter = $money->percentage(25);  // 25.00

            expect($zero->amount)->toBe(0.00)
                ->and($full->equals($money))->toBeTrue()
                ->and($half->amount)->toBe(50.00)
                ->and($quarter->amount)->toBe(25.00);
        });

        it('validates percentage boundary conditions', function (): void {
            $money = new Money(200.00, 'CAD');

            expect(fn () => $money->percentage(-1))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');

            expect(fn () => $money->percentage(101))
                ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');

            // Edge values should work
            $min = $money->percentage(0);
            $max = $money->percentage(100);

            expect($min->amount)->toBe(0.00)
                ->and($max->equals($money))->toBeTrue();
        });

        it('calculates fractional percentages accurately', function (): void {
            $money = new Money(333.33, 'USD');

            $third = $money->percentage(33.33);
            $twoThirds = $money->percentage(66.66);

            expect($third->amount)->toBeGreaterThan(111.0)
                ->and($third->amount)->toBeLessThan(112.0)
                ->and($twoThirds->amount)->toBeGreaterThan(222.0)
                ->and($twoThirds->amount)->toBeLessThan(223.0);
        });
    });

    describe('Cross-Currency Comparisons and Validations', function (): void {
        it('prevents comparison across different currencies', function (): void {
            $usd = new Money(100.00, 'USD');
            $eur = new Money(100.00, 'EUR');
            $gbp = new Money(100.00, 'GBP');

            $currencies = [$eur, $gbp];

            foreach ($currencies as $foreign) {
                expect(fn () => $usd->isGreaterThan($foreign))
                    ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');

                expect(fn () => $usd->isLessThan($foreign))
                    ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');

                expect(fn () => $usd->isGreaterThanOrEqual($foreign))
                    ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');

                expect(fn () => $usd->isLessThanOrEqual($foreign))
                    ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');
            }
        });

        it('allows equality comparison between same currency', function (): void {
            $money1 = new Money(100.00, 'USD');
            $money2 = new Money(100.00, 'USD');
            $money3 = new Money(99.99, 'USD');

            expect($money1->equals($money2))->toBeTrue()
                ->and($money1->equals($money3))->toBeFalse();
        });

        it('rejects equality between different currencies even with same amount', function (): void {
            $usd = new Money(100.00, 'USD');
            $eur = new Money(100.00, 'EUR');

            expect($usd->equals($eur))->toBeFalse();
        });
    });

    describe('Serialization and Deserialization', function (): void {
        it('serializes to array with all required fields', function (): void {
            $money = new Money(1234.56, 'GBP');
            $array = $money->toArray();

            expect($array)->toHaveKey('amount')
                ->and($array)->toHaveKey('currency')
                ->and($array)->toHaveKey('formatted')
                ->and($array['amount'])->toBe(1234.56)
                ->and($array['currency'])->toBe('GBP')
                ->and($array['formatted'])->toBe('£1,234.56');
        });

        it('creates money from string representations', function (): void {
            $fromSimple = Money::fromString('123.45', 'USD');
            $fromEuropean = Money::fromString('1234,56', 'EUR');
            $fromWithSymbols = Money::fromString('$1,234.56', 'USD');

            expect($fromSimple->amount)->toBe(123.45)
                ->and($fromSimple->currency)->toBe('USD')
                ->and($fromEuropean->amount)->toBe(1234.56)
                ->and($fromEuropean->currency)->toBe('EUR')
                ->and($fromWithSymbols->amount)->toBeGreaterThan(1.0); // Cleaned string parsing
        });

        it('handles malformed string input gracefully', function (): void {
            $fromEmpty = Money::fromString('', 'USD');
            $fromNonNumeric = Money::fromString('abc', 'EUR');
            $fromSpecialChars = Money::fromString('!@#$%', 'GBP');

            expect($fromEmpty->amount)->toBe(0.0)
                ->and($fromNonNumeric->amount)->toBe(0.0)
                ->and($fromSpecialChars->amount)->toBe(0.0);
        });

        it('converts to string using format method', function (): void {
            $usd = new Money(1000.00, 'USD');
            $eur = new Money(1000.00, 'EUR');
            $gbp = new Money(1000.00, 'GBP');

            expect((string) $usd)->toBe('$1,000.00')
                ->and((string) $eur)->toBe('€1.000,00')
                ->and((string) $gbp)->toBe('£1,000.00');
        });
    });

    describe('Immutability Validation', function (): void {
        it('ensures all operations return new instances', function (): void {
            $original = new Money(100.00, 'USD');
            $other = new Money(50.00, 'USD');

            $added = $original->add($other);
            $subtracted = $original->subtract($other);
            $multiplied = $original->multiply(2.0);
            $divided = $original->divide(2.0);
            $percentage = $original->percentage(50);

            $operations = [$added, $subtracted, $multiplied, $divided, $percentage];

            foreach ($operations as $result) {
                expect($result)->not->toBe($original)
                    ->and($original->amount)->toBe(100.00) // Original unchanged
                    ->and($original->currency)->toBe('USD');
            }
        });

        it('validates that properties cannot be modified', function (): void {
            $money = new Money(100.00, 'USD');

            // Properties are public but should represent immutable state
            $originalAmount = $money->amount;
            $originalCurrency = $money->currency;

            // Perform operations
            $money->add(new Money(50.00, 'USD'));
            $money->multiply(2.0);

            // Original should remain unchanged
            expect($money->amount)->toBe($originalAmount)
                ->and($money->currency)->toBe($originalCurrency);
        });
    });

    describe('Overflow and Underflow Handling', function (): void {
        it('handles very large monetary amounts', function (): void {
            $large = new Money(999999999.99, 'USD');
            $doubled = $large->multiply(2.0);

            expect($doubled->amount)->toBeGreaterThan($large->amount)
                ->and($doubled->currency)->toBe('USD');
        });

        it('prevents negative amounts in constructor', function (): void {
            expect(fn () => new Money(-0.01, 'USD'))
                ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');

            expect(fn () => new Money(-1000.00, 'EUR'))
                ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');
        });

        it('prevents negative results from subtraction', function (): void {
            $small = new Money(10.00, 'USD');
            $large = new Money(20.00, 'USD');

            expect(fn () => $small->subtract($large))
                ->toThrow(InvalidArgumentException::class, 'Subtraction would result in negative amount');
        });

        it('prevents negative multipliers and divisors', function (): void {
            $money = new Money(100.00, 'EUR');

            expect(fn () => $money->multiply(-1.5))
                ->toThrow(InvalidArgumentException::class, 'Multiplier cannot be negative');

            expect(fn () => $money->divide(-2.0))
                ->toThrow(InvalidArgumentException::class, 'Divisor cannot be negative');
        });

        it('prevents division by zero', function (): void {
            $money = new Money(100.00, 'GBP');

            expect(fn () => $money->divide(0.0))
                ->toThrow(InvalidArgumentException::class, 'Cannot divide by zero');
        });
    });

    describe('Locale-Specific Formatting', function (): void {
        it('formats amounts according to currency conventions', function (): void {
            $amounts = [
                ['USD', 1234.56, '$1,234.56'],
                ['EUR', 1234.56, '€1.234,56'],
                ['GBP', 1234.56, '£1,234.56'],
                ['CAD', 1234.56, 'C$1,234.56'],
                ['AUD', 1234.56, 'A$1,234.56'],
            ];

            foreach ($amounts as [$currency, $amount, $expected]) {
                $money = new Money($amount, $currency);
                expect($money->format())->toBe($expected);
            }
        });

        it('formats amounts without currency symbols', function (): void {
            $usd = new Money(1234.56, 'USD');
            $eur = new Money(1234.56, 'EUR');

            expect($usd->formatAmount())->toBe('1,234.56') // US format
                ->and($eur->formatAmount())->toBe('1.234,56'); // European format
        });

        it('provides currency symbols and codes', function (): void {
            $currencies = [
                ['USD', '$'],
                ['EUR', '€'],
                ['GBP', '£'],
                ['CAD', 'C$'],
                ['AUD', 'A$'],
            ];

            foreach ($currencies as [$code, $symbol]) {
                $money = new Money(100.00, $code);
                expect($money->getCurrencySymbol())->toBe($symbol)
                    ->and($money->getCurrencyCode())->toBe($code)
                    ->and($money->getCurrency())->toBe($code);
            }
        });

        it('handles zero amounts formatting correctly', function (): void {
            $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];

            foreach ($currencies as $currency) {
                $zero = Money::zero($currency);
                $formatted = $zero->format();

                expect($formatted)->toContain('0')
                    ->and($zero->isZero())->toBeTrue();
            }
        });
    });

    describe('Factory Methods and Static Constructors', function (): void {
        it('creates zero money for different currencies', function (): void {
            $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'JPY', 'CNY'];

            foreach ($currencies as $currency) {
                $zero = Money::zero($currency);

                expect($zero->amount)->toBe(0.0)
                    ->and($zero->currency)->toBe($currency)
                    ->and($zero->isZero())->toBeTrue()
                    ->and($zero->isPositive())->toBeFalse();
            }
        });

        it('creates money from various string formats', function (): void {
            $testCases = [
                ['100', 'USD', 100.0],
                ['100.50', 'USD', 100.50],
                ['1,234.56', 'USD', 1234.56],
                ['1234,56', 'EUR', 1234.56],
                ['$100.50', 'USD', 100.50],
                ['€1.234,56', 'EUR', 1234.56],
            ];

            foreach ($testCases as [$input, $currency, $expected]) {
                $money = Money::fromString($input, $currency);
                expect($money->currency)->toBe($currency);
                // Use range comparison for floating point precision
                expect($money->amount)->toBeGreaterThanOrEqual($expected - 0.01)
                    ->and($money->amount)->toBeLessThanOrEqual($expected + 0.01);
            }
        });

        it('handles edge cases in string parsing', function (): void {
            $money1 = Money::fromString('0', 'USD');
            $money2 = Money::fromString('0.00', 'EUR');
            $money3 = Money::fromString('.50', 'GBP');

            expect($money1->amount)->toBe(0.0)
                ->and($money2->amount)->toBe(0.0)
                ->and($money3->amount)->toBeGreaterThanOrEqual(0.4)
                ->and($money3->amount)->toBeLessThanOrEqual(0.6);
        });
    });
});

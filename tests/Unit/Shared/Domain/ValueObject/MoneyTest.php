<?php

declare(strict_types=1);

use Modules\Shared\Domain\ValueObject\Money;

describe('Money Value Object', function (): void {
    it('creates money with valid amount and currency', function (): void {
        $money = new Money(100.50, 'USD');

        expect($money->amount)->toBe(100.50)
            ->and($money->currency)->toBe('USD');
    });

    it('creates money with zero amount', function (): void {
        $money = new Money(0, 'EUR');

        expect($money->amount)->toBe(0.0)
            ->and($money->isZero())->toBeTrue()
            ->and($money->isPositive())->toBeFalse();
    });

    it('rejects negative amount', function (): void {
        expect(fn () => new Money(-50.25, 'GBP'))
            ->toThrow(InvalidArgumentException::class, 'Amount cannot be negative');
    });

    it('validates positive money', function (): void {
        $money = new Money(100, 'USD');

        expect($money->isPositive())->toBeTrue()
            ->and($money->isZero())->toBeFalse();
    });

    it('adds money with same currency', function (): void {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(50.25, 'USD');

        $result = $money1->add($money2);

        expect($result->amount)->toBe(150.75)
            ->and($result->currency)->toBe('USD')
            ->and($result)->not->toBe($money1)
            ->and($result)->not->toBe($money2);
    });

    it('subtracts money with same currency', function (): void {
        $money1 = new Money(100.50, 'USD');
        $money2 = new Money(30.25, 'USD');

        $result = $money1->subtract($money2);

        expect($result->amount)->toBe(70.25)
            ->and($result->currency)->toBe('USD');
    });

    it('subtracts to negative throws error', function (): void {
        $money1 = new Money(30.25, 'USD');
        $money2 = new Money(100.50, 'USD');

        expect(fn () => $money1->subtract($money2))
            ->toThrow(InvalidArgumentException::class, 'Subtraction would result in negative amount');
    });

    it('multiplies money by scalar', function (): void {
        $money = new Money(50.00, 'EUR');

        $result = $money->multiply(3);

        expect($result->amount)->toBe(150.00)
            ->and($result->currency)->toBe('EUR');
    });

    it('throws exception when multiplying by negative', function (): void {
        $money = new Money(100.00, 'EUR');

        expect(fn () => $money->multiply(-2))
            ->toThrow(InvalidArgumentException::class, 'Multiplier cannot be negative');
    });

    it('throws exception when adding different currencies', function (): void {
        $money1 = new Money(100.00, 'USD');
        $money2 = new Money(50.00, 'EUR');

        expect(fn () => $money1->add($money2))
            ->toThrow(InvalidArgumentException::class, 'Cannot add different currencies');
    });

    it('throws exception when subtracting different currencies', function (): void {
        $money1 = new Money(100.00, 'USD');
        $money2 = new Money(50.00, 'EUR');

        expect(fn () => $money1->subtract($money2))
            ->toThrow(InvalidArgumentException::class, 'Cannot subtract different currencies');
    });

    it('compares equality of money objects', function (): void {
        $money1 = new Money(100.00, 'USD');
        $money2 = new Money(100.00, 'USD');
        $money3 = new Money(100.00, 'EUR');
        $money4 = new Money(50.00, 'USD');

        expect($money1->equals($money2))->toBeTrue()
            ->and($money1->equals($money3))->toBeFalse()
            ->and($money1->equals($money4))->toBeFalse();
    });

    it('compares money amounts', function (): void {
        $money1 = new Money(100.00, 'USD');
        $money2 = new Money(50.00, 'USD');
        $money3 = new Money(100.00, 'USD');

        expect($money1->isGreaterThan($money2))->toBeTrue()
            ->and($money1->isGreaterThanOrEqual($money2))->toBeTrue()
            ->and($money1->isGreaterThanOrEqual($money3))->toBeTrue()
            ->and($money2->isLessThan($money1))->toBeTrue()
            ->and($money2->isLessThanOrEqual($money1))->toBeTrue()
            ->and($money3->isLessThanOrEqual($money1))->toBeTrue();
    });

    it('throws exception when comparing different currencies', function (): void {
        $money1 = new Money(100.00, 'USD');
        $money2 = new Money(50.00, 'EUR');

        expect(fn () => $money1->isGreaterThan($money2))
            ->toThrow(InvalidArgumentException::class, 'Cannot compare different currencies');
    });

    it('formats money for display', function (): void {
        $money = new Money(1234.56, 'USD');

        expect($money->format())->toBe('$1,234.56')
            ->and($money->formatAmount())->toBe('1,234.56');
    });

    it('formats money for different currencies', function (): void {
        $usd = new Money(1000.00, 'USD');
        $eur = new Money(1000.00, 'EUR');
        $gbp = new Money(1000.00, 'GBP');
        $cad = new Money(1000.00, 'CAD');
        $aud = new Money(1000.00, 'AUD');

        expect($usd->format())->toBe('$1,000.00')
            ->and($eur->format())->toBe('€1.000,00')
            ->and($gbp->format())->toBe('£1,000.00')
            ->and($cad->format())->toBe('C$1,000.00')
            ->and($aud->format())->toBe('A$1,000.00');
    });

    it('formats amount without currency symbol', function (): void {
        $usd = new Money(1234.56, 'USD');
        $eur = new Money(1234.56, 'EUR');

        expect($usd->formatAmount())->toBe('1,234.56')
            ->and($eur->formatAmount())->toBe('1.234,56');
    });

    it('gets currency symbol', function (): void {
        expect((new Money(100, 'USD'))->getCurrencySymbol())->toBe('$')
            ->and((new Money(100, 'EUR'))->getCurrencySymbol())->toBe('€')
            ->and((new Money(100, 'GBP'))->getCurrencySymbol())->toBe('£')
            ->and((new Money(100, 'CAD'))->getCurrencySymbol())->toBe('C$')
            ->and((new Money(100, 'AUD'))->getCurrencySymbol())->toBe('A$');
    });

    it('creates from string amount', function (): void {
        $money1 = Money::fromString('100.50', 'USD');
        $money2 = Money::fromString('1234,56', 'EUR');  // European format with comma as decimal separator
        $money3 = Money::fromString('1234.56', 'USD');  // Simple format without thousands separator

        expect($money1->amount)->toBe(100.50)
            ->and($money2->amount)->toBe(1234.56)
            ->and($money3->amount)->toBe(1234.56);
    });

    it('creates zero money', function (): void {
        $zero = Money::zero('USD');

        expect($zero->amount)->toBe(0.0)
            ->and($zero->currency)->toBe('USD')
            ->and($zero->isZero())->toBeTrue();
    });

    it('creates immutable objects', function (): void {
        $original = new Money(100.00, 'USD');
        $added = $original->add(new Money(50.00, 'USD'));
        $subtracted = $original->subtract(new Money(25.00, 'USD'));
        $multiplied = $original->multiply(2);

        expect($original->amount)->toBe(100.00)
            ->and($added->amount)->toBe(150.00)
            ->and($subtracted->amount)->toBe(75.00)
            ->and($multiplied->amount)->toBe(200.00);
    });

    it('validates currency codes', function (): void {
        expect(fn () => new Money(100, 'INVALID'))
            ->toThrow(InvalidArgumentException::class, 'Invalid currency code');

        $validCurrencies = ['EUR', 'USD', 'GBP', 'CAD', 'AUD'];

        foreach ($validCurrencies as $currency) {
            $money = new Money(100, $currency);
            expect($money->currency)->toBe($currency);
        }
    });

    it('converts to array', function (): void {
        $money = new Money(100.50, 'GBP');
        $result = $money->toArray();

        expect($result['amount'])->toBe(100.50)
            ->and($result['currency'])->toBe('GBP')
            ->and($result['formatted'])->toBe('£100.50');
    });

    it('calculates percentage', function (): void {
        $money = new Money(100.00, 'USD');

        $percent10 = $money->percentage(10);
        $percent25 = $money->percentage(25);
        $percent50 = $money->percentage(50);

        expect($percent10->amount)->toBe(10.00)
            ->and($percent25->amount)->toBe(25.00)
            ->and($percent50->amount)->toBe(50.00);
    });

    it('validates percentage range', function (): void {
        $money = new Money(100.00, 'USD');

        expect(fn () => $money->percentage(-10))
            ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');

        expect(fn () => $money->percentage(150))
            ->toThrow(InvalidArgumentException::class, 'Percentage must be between 0 and 100');
    });

    it('handles floating point precision correctly', function (): void {
        $money1 = new Money(0.1, 'USD');
        $money2 = new Money(0.2, 'USD');
        $result = $money1->add($money2);

        // This tests that we handle floating point arithmetic correctly
        expect($result->amount)->toBeGreaterThanOrEqual(0.29)
            ->and($result->amount)->toBeLessThanOrEqual(0.31);
    });

    it('creates Money from European format string', function (): void {
        $money = Money::fromString('1234,56', 'EUR');

        expect($money->amount)->toBe(1234.56);
    });

    it('percentage calculation is chainable', function (): void {
        $money = new Money(1000.00, 'USD');

        // Calculate 20% of 50% (should be 10% of original)
        $result = $money->percentage(50)->percentage(20);

        expect($result->amount)->toBe(100.00);
    });

    it('maintains precision through operations', function (): void {
        $money = new Money(10.99, 'USD');

        $doubled = $money->multiply(2);
        $added = $money->add(new Money(0.01, 'USD'));

        expect($doubled->amount)->toBe(21.98)
            ->and($added->amount)->toBe(11.00);
    });

    it('handles zero comparisons correctly', function (): void {
        $zero = Money::zero('USD');
        $positive = new Money(0.01, 'USD');

        expect($zero->isZero())->toBeTrue()
            ->and($positive->isZero())->toBeFalse()
            ->and($zero->isPositive())->toBeFalse()
            ->and($positive->isPositive())->toBeTrue()
            ->and($zero->isLessThan($positive))->toBeTrue()
            ->and($positive->isGreaterThan($zero))->toBeTrue();
    });
});

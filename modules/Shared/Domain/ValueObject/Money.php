<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

class Money implements Stringable
{
    public function __construct(
        public float $amount,
        public string $currency = 'EUR',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (! in_array($currency, ['EUR', 'USD', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY'], true)) {
            throw new InvalidArgumentException('Invalid currency code');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }

        return new self(
            $this->amount + $other->amount,
            $this->currency,
        );
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot subtract different currencies');
        }

        $result = $this->amount - $other->amount;

        if ($result < 0) {
            throw new InvalidArgumentException('Subtraction would result in negative amount');
        }

        return new self($result, $this->currency);
    }

    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        return new self(
            $this->amount * $factor,
            $this->currency,
        );
    }

    public function divide(float $divisor): self
    {
        if ($divisor === 0.0) {
            throw new InvalidArgumentException('Cannot divide by zero');
        }

        if ($divisor < 0) {
            throw new InvalidArgumentException('Divisor cannot be negative');
        }

        return new self(
            $this->amount / $divisor,
            $this->currency,
        );
    }

    public function percentage(float $percentage): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }

        return $this->multiply($percentage / 100);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function greaterThan(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }

        return $this->amount > $other->amount;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->greaterThan($other);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }

        return $this->amount >= $other->amount;
    }

    public function lessThan(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }

        return $this->amount < $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        return $this->lessThan($other);
    }

    public function isLessThanOrEqual(self $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }

        return $this->amount <= $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0.0;
    }

    /**
     * Format money with appropriate symbol and formatting for the currency.
     */
    public function format(): string
    {
        return match ($this->currency) {
            'EUR' => '€' . number_format($this->amount, 2, ',', '.'),
            'USD' => '$' . number_format($this->amount, 2, '.', ','),
            'GBP' => '£' . number_format($this->amount, 2, '.', ','),
            'CAD' => 'C$' . number_format($this->amount, 2, '.', ','),
            'AUD' => 'A$' . number_format($this->amount, 2, '.', ','),
            default => $this->currency . ' ' . number_format($this->amount, 2, '.', ','),
        };
    }

    /**
     * Format for display purposes without currency symbols.
     */
    public function formatAmount(): string
    {
        return match ($this->currency) {
            'EUR' => number_format($this->amount, 2, ',', '.'),
            default => number_format($this->amount, 2, '.', ','),
        };
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        return match ($this->currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => $this->currency,
        };
    }

    /**
     * Get currency code.
     */
    public function getCurrencyCode(): string
    {
        return $this->currency;
    }

    /**
     * Get the amount value.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the currency code (alias for getCurrencyCode).
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get formatted amount with currency symbol (alias for format).
     */
    public function getFormattedAmount(): string
    {
        return $this->format();
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Create Money from string amount.
     */
    public static function fromString(string $amount, string $currency = 'EUR'): self
    {
        $cleaned = preg_replace('/[^\d.,]/', '', $amount);

        if (empty($cleaned)) {
            return new self(0.0, $currency);
        }

        // Handle European decimal separator
        $cleanAmount = $currency === 'EUR' ? self::parseEuropeanAmount($cleaned) : self::parseNonEuropeanAmount($cleaned);

        return new self($cleanAmount, $currency);
    }

    private static function parseEuropeanAmount(string $cleaned): float
    {
        // Only comma - it's a decimal separator
        if (str_contains($cleaned, ',') && ! str_contains($cleaned, '.')) {
            return (float) str_replace(',', '.', $cleaned);
        }

        // Both dot and comma - dot is thousands, comma is decimal
        if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
            $cleaned = str_replace('.', '', $cleaned);

            return (float) str_replace(',', '.', $cleaned);
        }

        // Only dot or neither - treat normally
        return (float) $cleaned;
    }

    private static function parseNonEuropeanAmount(string $cleaned): float
    {
        // If we have both comma and dot, assume comma is thousands separator
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace(',', '', $cleaned);

            return (float) $cleaned;
        }

        // If only comma exists in non-EUR currency, treat as thousands separator
        if (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '', $cleaned);
        }

        return (float) $cleaned;
    }

    /**
     * Create zero amount for the given currency.
     */
    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0.0, $currency);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }
}

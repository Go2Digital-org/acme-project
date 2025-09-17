<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Amount Value Object for financial calculations and payment validation.
 *
 * Handles monetary amounts with currency validation, formatting, and business rules.
 */
class Amount implements Stringable
{
    private const PRECISION = 2;

    private const MIN_DONATION_AMOUNT = 1.00;

    private const MAX_DONATION_AMOUNT = 999999.99;

    private const TAX_RECEIPT_THRESHOLD = 20.00;

    /**
     * @param  float  $value  The monetary amount
     * @param  string  $currency  ISO currency code
     */
    public function __construct(
        public float $value,
        public string $currency = 'EUR',
    ) {
        $this->validateAmount($value);
        $this->validateCurrency($currency);
    }

    /**
     * Get the amount value.
     */
    public function getAmount(): float
    {
        return $this->value;
    }

    /**
     * Get the amount property (for compatibility).
     */
    public function __get(string $name): mixed
    {
        if ($name === 'amount') {
            return $this->value;
        }
        throw new InvalidArgumentException("Property {$name} does not exist");
    }

    /**
     * Create amount from string representation.
     */
    public static function fromString(string $amount, string $currency = 'EUR'): self
    {
        $numericAmount = filter_var($amount, FILTER_VALIDATE_FLOAT);

        if ($numericAmount === false) {
            throw new InvalidArgumentException("Invalid amount format: {$amount}");
        }

        return new self($numericAmount, $currency);
    }

    /**
     * Create amount from cents (minor units).
     */
    public static function fromCents(int $cents, string $currency = 'EUR'): self
    {
        return new self($cents / 100.0, $currency);
    }

    /**
     * Add another amount to this one.
     */
    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        $result = round($this->value + $other->value, self::PRECISION);

        return new self($result, $this->currency);
    }

    /**
     * Subtract another amount from this one.
     */
    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        $result = round($this->value - $other->value, self::PRECISION);

        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result, $this->currency);
    }

    /**
     * Multiply amount by a factor.
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Factor cannot be negative');
        }

        $result = round($this->value * $factor, self::PRECISION);

        return new self($result, $this->currency);
    }

    /**
     * Calculate percentage of the amount.
     */
    public function percentage(float $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }

        return $this->multiply($percent / 100);
    }

    /**
     * Check if amount is valid for donations.
     */
    public function isValidDonationAmount(): bool
    {
        return $this->value >= self::MIN_DONATION_AMOUNT
            && $this->value <= self::MAX_DONATION_AMOUNT;
    }

    /**
     * Check if amount qualifies for tax receipt.
     */
    public function qualifiesForTaxReceipt(): bool
    {
        return $this->value >= self::TAX_RECEIPT_THRESHOLD;
    }

    /**
     * Check if this amount is greater than another.
     */
    public function greaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->value > $other->value;
    }

    /**
     * Check if this amount is greater than or equal to another.
     */
    public function greaterThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->value >= $other->value;
    }

    /**
     * Check if this amount is less than another.
     */
    public function lessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->value < $other->value;
    }

    /**
     * Check if this amount is less than or equal to another.
     */
    public function lessThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->value <= $other->value;
    }

    /**
     * Check if this amount equals another.
     */
    public function equals(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return abs($this->value - $other->value) < 0.001; // Account for floating point precision
    }

    /**
     * Check if amount is zero.
     */
    public function isZero(): bool
    {
        return abs($this->value) < 0.001;
    }

    /**
     * Check if amount is positive.
     */
    public function isPositive(): bool
    {
        return $this->value > 0.001;
    }

    /**
     * Get amount in cents (minor units).
     */
    public function toCents(): int
    {
        return (int) round($this->value * 100);
    }

    /**
     * Format amount for display.
     */
    public function format(): string
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol . number_format($this->value, self::PRECISION);
    }

    /**
     * Format amount for API/database storage.
     */
    public function toDecimalString(): string
    {
        return number_format($this->value, self::PRECISION, '.', '');
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'currency' => $this->currency,
            'formatted' => $this->format(),
            'cents' => $this->toCents(),
        ];
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Create minimum donation amount.
     */
    public static function minimumDonation(string $currency = 'EUR'): self
    {
        return new self(self::MIN_DONATION_AMOUNT, $currency);
    }

    /**
     * Create maximum donation amount.
     */
    public static function maximumDonation(string $currency = 'EUR'): self
    {
        return new self(self::MAX_DONATION_AMOUNT, $currency);
    }

    /**
     * Create tax receipt threshold amount.
     */
    public static function taxReceiptThreshold(string $currency = 'EUR'): self
    {
        return new self(self::TAX_RECEIPT_THRESHOLD, $currency);
    }

    /**
     * Create zero amount.
     */
    public static function zero(string $currency = 'EUR'): self
    {
        return new self(0.0, $currency);
    }

    /**
     * Validate amount value.
     */
    private function validateAmount(float $value): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (is_infinite($value) || is_nan($value)) {
            throw new InvalidArgumentException('Amount must be a valid number');
        }

        // Check precision - round to precision and compare
        $rounded = round($value, self::PRECISION);

        if (abs($value - $rounded) > 0.001) {
            throw new InvalidArgumentException('Amount cannot have more than ' . self::PRECISION . ' decimal places');
        }
    }

    /**
     * Validate currency code.
     */
    private function validateCurrency(string $currency): void
    {
        $supportedCurrencies = ['EUR', 'USD', 'GBP'];

        if (! in_array($currency, $supportedCurrencies, true)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    /**
     * Ensure two amounts have the same currency.
     */
    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}",
            );
        }
    }

    /**
     * Get currency symbol for formatting.
     */
    private function getCurrencySymbol(): string
    {
        return match ($this->currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $this->currency . ' ',
        };
    }
}

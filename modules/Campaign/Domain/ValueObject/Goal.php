<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Shared\Domain\ValueObject\Money;
use Stringable;

/**
 * Campaign fundraising goal value object
 */
class Goal implements Stringable
{
    public function __construct(
        public readonly Money $targetAmount,
        public readonly Money $currentAmount
    ) {
        if ($targetAmount->amount <= 0) {
            throw new InvalidArgumentException('Goal target amount must be positive');
        }

        if ($currentAmount->amount < 0) {
            throw new InvalidArgumentException('Goal current amount cannot be negative');
        }

        if ($targetAmount->currency !== $currentAmount->currency) {
            throw new InvalidArgumentException('Goal target and current amounts must have the same currency');
        }
    }

    public static function create(float $targetAmount, float $currentAmount = 0.0, string $currency = 'USD'): self
    {
        return new self(
            new Money($targetAmount, $currency),
            new Money($currentAmount, $currency)
        );
    }

    public function getProgressPercentage(): float
    {
        if ($this->targetAmount->amount === 0.0) {
            return 0.0;
        }

        return min(100.0, ($this->currentAmount->amount / $this->targetAmount->amount) * 100.0);
    }

    public function getRemainingAmount(): Money
    {
        $remainingAmount = max(0.0, $this->targetAmount->amount - $this->currentAmount->amount);

        return new Money($remainingAmount, $this->targetAmount->currency);
    }

    public function hasReachedTarget(): bool
    {
        return $this->currentAmount->amount >= $this->targetAmount->amount;
    }

    public function addAmount(Money $amount): self
    {
        if ($amount->currency !== $this->currentAmount->currency) {
            throw new InvalidArgumentException('Cannot add amount with different currency to goal');
        }

        $newCurrentAmount = $this->currentAmount->add($amount);

        return new self($this->targetAmount, $newCurrentAmount);
    }

    public function equals(Goal $other): bool
    {
        return $this->targetAmount->equals($other->targetAmount)
            && $this->currentAmount->equals($other->currentAmount);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s of %s (%s%%)',
            $this->currentAmount->format(),
            $this->targetAmount->format(),
            number_format($this->getProgressPercentage(), 1)
        );
    }
}

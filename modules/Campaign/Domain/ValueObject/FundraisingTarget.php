<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Shared\Domain\ValueObject\Money;
use Stringable;

/**
 * FundraisingTarget Value Object for campaign target amount validation.
 *
 * Handles monetary targets with business rules specific to fundraising campaigns.
 */
class FundraisingTarget implements Stringable
{
    private const MIN_TARGET = 100.00;

    private const MAX_TARGET = 10000000.00;

    private const RECOMMENDED_MIN_TARGET = 1000.00;

    private const MEGA_CAMPAIGN_THRESHOLD = 1000000.00;

    public function __construct(
        private readonly Money $amount,
    ) {
        $this->validateTargetAmount();
    }

    /**
     * Create from Money value object.
     */
    public static function fromMoney(Money $money): self
    {
        return new self($money);
    }

    /**
     * Create from amount and currency.
     */
    public static function fromAmount(float $amount, string $currency = 'EUR'): self
    {
        return new self(new Money($amount, $currency));
    }

    /**
     * Create minimum target for campaigns.
     */
    public static function minimum(string $currency = 'EUR'): self
    {
        return new self(new Money(self::MIN_TARGET, $currency));
    }

    /**
     * Create maximum target for campaigns.
     */
    public static function maximum(string $currency = 'EUR'): self
    {
        return new self(new Money(self::MAX_TARGET, $currency));
    }

    /**
     * Create recommended minimum target.
     */
    public static function recommendedMinimum(string $currency = 'EUR'): self
    {
        return new self(new Money(self::RECOMMENDED_MIN_TARGET, $currency));
    }

    /**
     * Get the Money value object.
     */
    public function getMoney(): Money
    {
        return $this->amount;
    }

    /**
     * Get the raw amount value.
     */
    public function getAmount(): float
    {
        return $this->amount->getAmount();
    }

    /**
     * Get the currency.
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }

    /**
     * Check if target is achievable (above recommended minimum).
     */
    public function isAchievable(): bool
    {
        return $this->amount->getAmount() >= self::RECOMMENDED_MIN_TARGET;
    }

    /**
     * Check if target qualifies as mega campaign.
     */
    public function isMegaCampaign(): bool
    {
        return $this->amount->getAmount() >= self::MEGA_CAMPAIGN_THRESHOLD;
    }

    /**
     * Check if target is above minimum but below recommended.
     */
    public function requiresApproval(): bool
    {
        $amount = $this->amount->getAmount();

        return $amount >= self::MIN_TARGET && $amount < self::RECOMMENDED_MIN_TARGET;
    }

    /**
     * Calculate percentage progress towards target.
     */
    public function calculateProgress(Money $raised): float
    {
        if ($this->amount->getCurrency() !== $raised->getCurrency()) {
            throw new InvalidArgumentException('Currency mismatch between target and raised amount');
        }

        if ($this->amount->isZero()) {
            return 0.0;
        }

        return min(($raised->getAmount() / $this->amount->getAmount()) * 100, 100.0);
    }

    /**
     * Calculate remaining amount to reach target.
     */
    public function calculateRemaining(Money $raised): Money
    {
        if ($this->amount->getCurrency() !== $raised->getCurrency()) {
            throw new InvalidArgumentException('Currency mismatch between target and raised amount');
        }

        if ($raised->isGreaterThanOrEqual($this->amount)) {
            return Money::zero($this->amount->getCurrency());
        }

        return $this->amount->subtract($raised);
    }

    /**
     * Check if target has been reached.
     */
    public function isReached(Money $raised): bool
    {
        if ($this->amount->getCurrency() !== $raised->getCurrency()) {
            throw new InvalidArgumentException('Currency mismatch between target and raised amount');
        }

        return $raised->isGreaterThanOrEqual($this->amount);
    }

    /**
     * Check if target is exceeded.
     */
    public function isExceeded(Money $raised): bool
    {
        if ($this->amount->getCurrency() !== $raised->getCurrency()) {
            throw new InvalidArgumentException('Currency mismatch between target and raised amount');
        }

        return $raised->greaterThan($this->amount);
    }

    /**
     * Calculate milestone amounts (25%, 50%, 75%, 100%).
     *
     * @return array<int, Money>
     */
    public function getMilestones(): array
    {
        return [
            25 => $this->amount->percentage(25),
            50 => $this->amount->percentage(50),
            75 => $this->amount->percentage(75),
            100 => $this->amount,
        ];
    }

    /**
     * Check if a raised amount has reached a specific milestone.
     */
    public function hasMilestoneBeenReached(Money $raised, int $percentage): bool
    {
        if ($this->amount->getCurrency() !== $raised->getCurrency()) {
            throw new InvalidArgumentException('Currency mismatch between target and raised amount');
        }

        if ($percentage <= 0 || $percentage > 100) {
            throw new InvalidArgumentException('Milestone percentage must be between 1 and 100');
        }

        $milestoneAmount = $this->amount->percentage((float) $percentage);

        return $raised->isGreaterThanOrEqual($milestoneAmount);
    }

    /**
     * Compare with another target.
     */
    public function equals(self $other): bool
    {
        return $this->amount->equals($other->amount);
    }

    /**
     * Check if this target is greater than another.
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->amount->greaterThan($other->amount);
    }

    /**
     * Format target for display.
     */
    public function format(): string
    {
        return $this->amount->format();
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'formatted' => $this->amount->format(),
            'is_achievable' => $this->isAchievable(),
            'is_mega_campaign' => $this->isMegaCampaign(),
            'requires_approval' => $this->requiresApproval(),
            'milestones' => array_map(
                fn (Money $milestone): string => $milestone->format(),
                $this->getMilestones(),
            ),
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
     * Validate the target amount against business rules.
     */
    private function validateTargetAmount(): void
    {
        $amount = $this->amount->getAmount();

        if ($amount < self::MIN_TARGET) {
            throw new InvalidArgumentException(
                sprintf(
                    'Fundraising target must be at least %s %s',
                    number_format(self::MIN_TARGET, 2),
                    $this->amount->getCurrency(),
                ),
            );
        }

        if ($amount > self::MAX_TARGET) {
            throw new InvalidArgumentException(
                sprintf(
                    'Fundraising target cannot exceed %s %s',
                    number_format(self::MAX_TARGET, 2),
                    $this->amount->getCurrency(),
                ),
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Specification;

use DB;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user has sufficient funds for a donation.
 *
 * A user has sufficient funds when:
 * - Donation amount is above minimum threshold
 * - Donation amount is below maximum limits
 * - User has available balance (if using corporate account)
 * - User hasn't exceeded daily/monthly donation limits
 * - Amount is valid for the selected payment method
 */
final class HasSufficientFundsSpecification extends CompositeSpecification
{
    private const MIN_DONATION_AMOUNT = 0.01;

    private const MAX_DONATION_AMOUNT = 50000.00;

    private const MAX_DAILY_DONATION_AMOUNT = 10000.00;

    private const MAX_MONTHLY_DONATION_AMOUNT = 100000.00;

    private string $reason = '';

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! is_array($candidate) || ! isset($candidate['user'], $candidate['amount'])) {
            $this->reason = 'Invalid input. Expected array with user and amount.';

            return false;
        }

        $user = $candidate['user'];
        $amount = $candidate['amount'];
        $paymentMethod = $candidate['payment_method'] ?? null;

        if (! $user instanceof UserInterface) {
            $this->reason = 'Invalid user provided.';

            return false;
        }

        if (! is_numeric($amount) || $amount <= 0) {
            $this->reason = 'Invalid donation amount provided.';

            return false;
        }

        $amount = (float) $amount;

        // Check minimum donation amount
        if ($amount < self::MIN_DONATION_AMOUNT) {
            $this->reason = sprintf(
                'Donation amount must be at least $%.2f.',
                self::MIN_DONATION_AMOUNT
            );

            return false;
        }

        // Check maximum donation amount
        if ($amount > self::MAX_DONATION_AMOUNT) {
            $this->reason = sprintf(
                'Donation amount cannot exceed $%.2f.',
                self::MAX_DONATION_AMOUNT
            );

            return false;
        }

        // Check daily donation limits
        $dailyTotal = $this->getUserDailyDonationTotal($user);
        if (($dailyTotal + $amount) > self::MAX_DAILY_DONATION_AMOUNT) {
            $remaining = self::MAX_DAILY_DONATION_AMOUNT - $dailyTotal;
            $this->reason = sprintf(
                'Daily donation limit exceeded. Remaining daily limit: $%.2f.',
                max(0, $remaining)
            );

            return false;
        }

        // Check monthly donation limits
        $monthlyTotal = $this->getUserMonthlyDonationTotal($user);
        if (($monthlyTotal + $amount) > self::MAX_MONTHLY_DONATION_AMOUNT) {
            $remaining = self::MAX_MONTHLY_DONATION_AMOUNT - $monthlyTotal;
            $this->reason = sprintf(
                'Monthly donation limit exceeded. Remaining monthly limit: $%.2f.',
                max(0, $remaining)
            );

            return false;
        }

        // Check payment method specific limits
        if ($paymentMethod && ! $this->isValidAmountForPaymentMethod($amount, $paymentMethod)) {
            $this->reason = $this->getPaymentMethodLimitReason($paymentMethod);

            return false;
        }

        // Check corporate account balance if using corporate payment method
        if ($paymentMethod === 'corporate_account' && ! $this->hasSufficientCorporateBalance($user, $amount)) {
            $this->reason = 'Insufficient corporate account balance.';

            return false;
        }

        // Check user's personal spending limits (if configured)
        if (! $this->isWithinPersonalLimits($user, $amount)) {
            $this->reason = 'Amount exceeds personal spending limits set by user or organization.';

            return false;
        }

        // Check organization donation policy
        if (! $this->compliesWithOrganizationPolicy($user, $amount)) {
            $this->reason = 'Donation does not comply with organization donation policy.';

            return false;
        }

        $this->reason = '';

        return true;
    }

    /**
     * Get the reason why the specification was not satisfied.
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Get user's total donations for today.
     */
    private function getUserDailyDonationTotal(UserInterface $user): float
    {
        return (float) DB::table('donations')
            ->where('user_id', $user->getId())
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get user's total donations for this month.
     */
    private function getUserMonthlyDonationTotal(UserInterface $user): float
    {
        return (float) DB::table('donations')
            ->where('user_id', $user->getId())
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Check if amount is valid for the selected payment method.
     */
    private function isValidAmountForPaymentMethod(float $amount, string $paymentMethod): bool
    {
        return match ($paymentMethod) {
            'card', 'stripe' => $amount >= 0.50 && $amount <= 50000.00,
            'paypal' => $amount >= 1.00 && $amount <= 10000.00,
            'bank_transfer' => $amount >= 10.00 && $amount <= 50000.00,
            'ideal', 'bancontact' => $amount >= 1.00 && $amount <= 50000.00,
            'sofort' => $amount >= 1.00 && $amount <= 5000.00,
            'corporate_account' => $amount >= 0.01 && $amount <= 50000.00,
            default => true,
        };
    }

    /**
     * Get payment method specific limit reason.
     */
    private function getPaymentMethodLimitReason(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card', 'stripe' => 'Card payments must be between $0.50 and $50,000.',
            'paypal' => 'PayPal payments must be between $1.00 and $10,000.',
            'bank_transfer' => 'Bank transfers must be between $10.00 and $50,000.',
            'ideal', 'bancontact' => 'iDEAL/Bancontact payments must be between $1.00 and $50,000.',
            'sofort' => 'SOFORT payments must be between $1.00 and $5,000.',
            default => 'Invalid amount for selected payment method.',
        };
    }

    /**
     * Check if user has sufficient corporate account balance.
     */
    private function hasSufficientCorporateBalance(UserInterface $user, float $amount): bool
    {
        // This would typically query a corporate account balance system
        // For now, using a simplified approach
        $organization = $user->getOrganization();

        if (! $organization) {
            return false;
        }

        // Get organization's available donation budget
        $budget = $organization->metadata['donation_budget'] ?? 0;
        $usedBudget = $this->getOrganizationUsedBudget($organization);
        $availableBudget = $budget - $usedBudget;

        return $availableBudget >= $amount;
    }

    /**
     * Check if amount is within user's personal limits.
     */
    private function isWithinPersonalLimits(UserInterface $user, float $amount): bool
    {
        // Check user's personal donation limits (if set)
        $userSettings = $user->getSettings();
        $personalDailyLimit = $userSettings['daily_donation_limit'] ?? null;
        $personalMonthlyLimit = $userSettings['monthly_donation_limit'] ?? null;

        if ($personalDailyLimit) {
            $dailyTotal = $this->getUserDailyDonationTotal($user);
            if (($dailyTotal + $amount) > $personalDailyLimit) {
                return false;
            }
        }

        if ($personalMonthlyLimit) {
            $monthlyTotal = $this->getUserMonthlyDonationTotal($user);
            if (($monthlyTotal + $amount) > $personalMonthlyLimit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if donation complies with organization policy.
     */
    private function compliesWithOrganizationPolicy(UserInterface $user, float $amount): bool
    {
        $organization = $user->getOrganization();

        if (! $organization) {
            return true; // External users not subject to organization policies
        }

        $policy = $organization->metadata['donation_policy'] ?? [];

        // Check minimum amount per organization policy
        $minAmount = $policy['min_amount'] ?? 0;
        if ($amount < $minAmount) {
            return false;
        }

        // Check maximum amount per organization policy
        $maxAmount = $policy['max_amount'] ?? self::MAX_DONATION_AMOUNT;

        return $amount <= $maxAmount;
    }

    /**
     * Get organization's used donation budget.
     */
    private function getOrganizationUsedBudget(mixed $organization): float
    {
        return (float) DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organization->id)
            ->whereYear('donations.created_at', now()->year)
            ->where('donations.status', 'completed')
            ->sum('donations.amount');
    }
}

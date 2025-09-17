<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

enum PaymentMethod: string
{
    case CARD = 'card';
    case IDEAL = 'ideal';
    case BANCONTACT = 'bancontact';
    case SOFORT = 'sofort';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case CORPORATE_ACCOUNT = 'corporate_account';

    /**
     * Get the type value (for compatibility).
     */
    public function getType(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CARD => 'Credit/Debit Card',
            self::IDEAL => 'iDEAL',
            self::BANCONTACT => 'Bancontact',
            self::SOFORT => 'Sofort',
            self::STRIPE => 'Credit/Debit Card (Stripe)',
            self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CORPORATE_ACCOUNT => 'Corporate Account',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CARD => 'heroicon-o-credit-card',
            self::IDEAL => 'heroicon-o-credit-card',
            self::BANCONTACT => 'heroicon-o-credit-card',
            self::SOFORT => 'heroicon-o-credit-card',
            self::STRIPE => 'heroicon-o-credit-card',
            self::PAYPAL => 'heroicon-o-banknotes',
            self::BANK_TRANSFER => 'heroicon-o-building-library',
            self::CORPORATE_ACCOUNT => 'heroicon-o-building-office-2',
        };
    }

    public function requiresProcessing(): bool
    {
        return match ($this) {
            self::CARD, self::IDEAL, self::BANCONTACT, self::SOFORT, self::STRIPE, self::PAYPAL => true,
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => false,
        };
    }

    public function getGateway(): ?string
    {
        return match ($this) {
            self::CARD, self::IDEAL, self::BANCONTACT, self::SOFORT => 'mollie',
            self::STRIPE => 'stripe',
            self::PAYPAL => 'paypal',
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => null,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CARD => 'primary',
            self::IDEAL => 'success',
            self::BANCONTACT => 'warning',
            self::SOFORT => 'info',
            self::STRIPE => 'primary',
            self::PAYPAL => 'warning',
            self::BANK_TRANSFER => 'gray',
            self::CORPORATE_ACCOUNT => 'secondary',
        };
    }

    public function getTailwindBadgeClasses(): string
    {
        return match ($this) {
            self::CARD => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            self::IDEAL => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            self::BANCONTACT => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            self::SOFORT => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-400',
            self::STRIPE => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            self::PAYPAL => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            self::BANK_TRANSFER => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
            self::CORPORATE_ACCOUNT => 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        };
    }

    public function isInstant(): bool
    {
        return match ($this) {
            self::CARD, self::IDEAL, self::BANCONTACT, self::SOFORT, self::STRIPE, self::PAYPAL => true,
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => false,
        };
    }

    public function isOnline(): bool
    {
        return match ($this) {
            self::CARD, self::IDEAL, self::BANCONTACT, self::SOFORT, self::STRIPE, self::PAYPAL => true,
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => false,
        };
    }

    public function requiresWebhook(): bool
    {
        return $this->requiresProcessing() && $this->isOnline();
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CARD => 'Pay securely with your credit or debit card',
            self::IDEAL => 'Pay with your bank account using iDEAL (Netherlands)',
            self::BANCONTACT => 'Pay with your bank account using Bancontact (Belgium)',
            self::SOFORT => 'Instant bank transfer via Sofort',
            self::STRIPE => 'Pay securely with your credit or debit card via Stripe',
            self::PAYPAL => 'Pay with your PayPal account or card',
            self::BANK_TRANSFER => 'Transfer money directly from your bank account',
            self::CORPORATE_ACCOUNT => 'Donation processed through corporate account',
        };
    }

    public function getProcessingTime(): string
    {
        return match ($this) {
            self::CARD, self::IDEAL, self::BANCONTACT, self::SOFORT, self::STRIPE, self::PAYPAL => 'Instant',
            self::BANK_TRANSFER => '1-3 business days',
            self::CORPORATE_ACCOUNT => 'Next business day',
        };
    }

    public function supportsCurrency(string $currency): bool
    {
        return match ($this) {
            self::CARD, self::STRIPE => in_array(strtoupper($currency), ['USD', 'EUR', 'GBP', 'CAD', 'AUD'], true),
            self::IDEAL => strtoupper($currency) === 'EUR',
            self::BANCONTACT => strtoupper($currency) === 'EUR',
            self::SOFORT => in_array(strtoupper($currency), ['EUR', 'GBP'], true),
            self::PAYPAL => in_array(strtoupper($currency), ['USD', 'EUR', 'GBP', 'CAD', 'AUD'], true),
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => true, // Manual processing can handle any currency
        };
    }

    public function getMinimumAmount(string $currency = 'USD'): float
    {
        $minimums = match ($this) {
            self::CARD, self::STRIPE => ['USD' => 0.50, 'EUR' => 0.50, 'GBP' => 0.30],
            self::IDEAL, self::BANCONTACT => ['EUR' => 0.01],
            self::SOFORT => ['EUR' => 0.50, 'GBP' => 0.50],
            self::PAYPAL => ['USD' => 1.00, 'EUR' => 1.00, 'GBP' => 1.00],
            self::BANK_TRANSFER, self::CORPORATE_ACCOUNT => ['USD' => 0.01, 'EUR' => 0.01, 'GBP' => 0.01],
        };

        return $minimums[strtoupper($currency)] ?? 0.01;
    }

    /**
     * Get available payment methods for a given currency.
     */
    /** @return array<array-key, mixed> */
    public static function getAvailableForCurrency(string $currency): array
    {
        $allMethods = self::cases();

        return array_filter($allMethods, fn (self $method): bool => $method->supportsCurrency($currency));
    }

    public static function fromString(string $method): self
    {
        return self::from($method);
    }

    public static function tryFromString(string $method): ?self
    {
        return self::tryFrom($method);
    }
}

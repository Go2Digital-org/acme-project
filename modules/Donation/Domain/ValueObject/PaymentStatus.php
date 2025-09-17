<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

/**
 * Payment Status Enumeration.
 *
 * Represents the various states a payment can be in across all gateways.
 * Normalized statuses that map to gateway-specific statuses.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case REQUIRES_ACTION = 'requires_action';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case SUCCEEDED = 'succeeded'; // Alias for COMPLETED

    /**
     * Check if payment is in a successful state.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if payment is in a final state (no further processing).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::REQUIRES_ACTION,
        ], true);
    }

    /**
     * Check if payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::PARTIALLY_REFUNDED,
        ], true);
    }

    /**
     * Check if payment requires user action (3DS, approval, etc).
     */
    public function requiresUserAction(): bool
    {
        return $this === self::REQUIRES_ACTION;
    }

    /**
     * Get human-readable description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Payment is pending processing',
            self::PROCESSING => 'Payment is being processed',
            self::REQUIRES_ACTION => 'Payment requires additional action',
            self::COMPLETED, self::SUCCEEDED => 'Payment completed successfully',
            self::FAILED => 'Payment failed',
            self::CANCELLED => 'Payment was cancelled',
            self::REFUNDED => 'Payment was refunded',
            self::PARTIALLY_REFUNDED => 'Payment was partially refunded',
        };
    }

    /**
     * Map from Stripe status.
     */
    public static function fromStripeStatus(string $stripeStatus): self
    {
        return match ($stripeStatus) {
            'requires_payment_method' => self::PENDING,
            'requires_confirmation' => self::PENDING,
            'requires_action' => self::REQUIRES_ACTION,
            'processing' => self::PROCESSING,
            'requires_capture' => self::PROCESSING,
            'succeeded' => self::COMPLETED,
            'canceled' => self::CANCELLED,
            default => self::FAILED,
        };
    }

    /**
     * Map from PayPal status.
     */
    public static function fromPayPalStatus(string $paypalStatus): self
    {
        return match (strtoupper($paypalStatus)) {
            'CREATED' => self::PENDING,
            'SAVED' => self::PENDING,
            'APPROVED' => self::REQUIRES_ACTION,
            'COMPLETED' => self::COMPLETED,
            'CANCELLED' => self::CANCELLED,
            'VOIDED' => self::CANCELLED,
            'FAILED' => self::FAILED,
            'DENIED' => self::FAILED,
            default => self::FAILED,
        };
    }
}

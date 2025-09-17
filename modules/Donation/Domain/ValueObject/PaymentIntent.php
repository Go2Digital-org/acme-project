<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

use Modules\Shared\Domain\ValueObject\Money;

/**
 * Payment Intent Value Object.
 *
 * Represents all information needed to create a payment intent
 * across different payment gateways.
 */
final readonly class PaymentIntent
{
    /**
     * @param  array<array-key, mixed>  $metadata
     */
    public function __construct(
        public int $donationId,
        public int $campaignId,
        public int $employeeId,
        public Money $amount,
        public PaymentMethod $paymentMethod,
        public string $description,
        public string $returnUrl,
        public string $cancelUrl,
        public array $metadata = [],
        public bool $captureMethod = true,
        public ?string $customerId = null,
        public ?string $paymentMethodId = null,
    ) {}

    /**
     * Get amount in smallest currency unit (cents for USD).
     */
    public function getAmountInCents(): int
    {
        return (int) ($this->amount->amount * 100);
    }

    /**
     * Get currency code.
     */
    public function getCurrency(): string
    {
        return $this->amount->currency;
    }

    /**
     * Check if payment should be captured immediately.
     */
    public function shouldCaptureImmediately(): bool
    {
        return $this->captureMethod;
    }

    /**
     * Get formatted description for gateway.
     */
    public function getFormattedDescription(): string
    {
        return sprintf(
            'Donation to %s - %s',
            $this->description,
            $this->amount->format(),
        );
    }

    /**
     * Get metadata with donation context.
     */
    /** @return array<array-key, mixed> */
    public function getEnrichedMetadata(): array
    {
        return array_merge($this->metadata, [
            'donation_id' => (string) $this->donationId,
            'campaign_id' => (string) $this->campaignId,
            'user_id' => (string) $this->employeeId,
            'amount' => (string) $this->amount->amount,
            'currency' => $this->amount->currency,
            'payment_method' => $this->paymentMethod->value,
        ]);
    }
}

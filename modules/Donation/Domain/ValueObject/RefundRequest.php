<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

/**
 * Refund Request Value Object.
 *
 * Represents all information needed to process a payment refund
 * across different payment gateways.
 */
final readonly class RefundRequest
{
    public function __construct(
        public string $transactionId,
        public float $amount,
        public string $currency,
        public ?string $reason = null,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
    ) {}

    /**
     * Get amount in smallest currency unit (cents for USD).
     */
    public function getAmountInCents(): int
    {
        // Handle floating point precision issues by rounding to 2 decimal places first
        $amountCents = round($this->amount * 100, 2);

        // Then truncate any remaining decimal places
        return (int) $amountCents;
    }

    /**
     * Get formatted reason for gateway.
     */
    public function getFormattedReason(): string
    {
        return $this->reason ?? 'Donation refund requested';
    }

    /**
     * Get enriched metadata with refund context.
     */
    /** @return array<array-key, mixed> */
    public function getEnrichedMetadata(): array
    {
        return array_merge($this->metadata ?? [], [
            'refund_amount' => (string) $this->amount,
            'refund_currency' => $this->currency,
            'refund_reason' => $this->reason ?? 'requested',
            'refund_timestamp' => now()->format('Y-m-d\TH:i:s.v\Z'),
        ]);
    }
}

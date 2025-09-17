<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\ValueObject;

use DateTimeImmutable;

/**
 * Payment Result Value Object.
 *
 * Represents the result of a payment operation across all gateways.
 * Provides consistent interface regardless of gateway implementation.
 */
class PaymentResult
{
    /**
     * @param  array<string, mixed>  $gatewayData
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        public bool $successful,
        public ?string $transactionId = null,
        public ?string $intentId = null,
        public ?string $clientSecret = null,
        public ?PaymentStatus $status = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public array $gatewayData = [],
        public array $metadata = [],
        public ?DateTimeImmutable $processedAt = null,
    ) {}

    /**
     * Create successful payment result.
     *
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data = []): self
    {
        return new self(
            successful: true,
            transactionId: $data['transaction_id'] ?? null,
            intentId: $data['intent_id'] ?? null,
            clientSecret: $data['client_secret'] ?? null,
            status: isset($data['status'])
                ? ($data['status'] instanceof PaymentStatus
                    ? $data['status']
                    : PaymentStatus::from($data['status']))
                : null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            gatewayData: is_array($data['gateway_data'] ?? null)
                ? $data['gateway_data']
                : (array) ($data['gateway_data'] ?? []),
            metadata: is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : (array) ($data['metadata'] ?? []),
            processedAt: $data['processed_at'] ?? new DateTimeImmutable,
        );
    }

    /**
     * Create failed payment result.
     *
     * @param  array<string, mixed>  $gatewayData
     */
    public static function failure(
        string $errorMessage,
        ?string $errorCode = null,
        array $gatewayData = [],
    ): self {
        return new self(
            successful: false,
            status: PaymentStatus::FAILED,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            gatewayData: $gatewayData,
            processedAt: new DateTimeImmutable,
        );
    }

    /**
     * Create pending payment result.
     *
     * @param  array<string, mixed>  $data
     */
    public static function pending(array $data = []): self
    {
        return new self(
            successful: false, // Not successful until completed
            transactionId: $data['transaction_id'] ?? null,
            intentId: $data['intent_id'] ?? null,
            clientSecret: $data['client_secret'] ?? null,
            status: PaymentStatus::PENDING,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            gatewayData: is_array($data['gateway_data'] ?? null)
                ? $data['gateway_data']
                : (array) ($data['gateway_data'] ?? []),
            metadata: is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : (array) ($data['metadata'] ?? []),
            processedAt: new DateTimeImmutable,
        );
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Check if payment is pending further action.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment requires additional action (3DS, etc).
     */
    public function requiresAction(): bool
    {
        return $this->status === PaymentStatus::REQUIRES_ACTION;
    }

    /**
     * Check if payment failed.
     */
    public function hasFailed(): bool
    {
        return ! $this->successful && $this->status === PaymentStatus::FAILED;
    }

    /**
     * Get the transaction ID if available.
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * Get the payment intent ID if available.
     */
    public function getIntentId(): ?string
    {
        return $this->intentId;
    }

    /**
     * Get client secret for frontend processing.
     */
    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    /**
     * Get error message if payment failed.
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage ?? 'Payment failed';
    }

    /**
     * Get error code if available.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get amount processed.
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Get currency of processed amount.
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Get gateway-specific data.
     */
    /** @return array<array-key, mixed> */
    public function getGatewayData(): array
    {
        return $this->gatewayData;
    }

    /**
     * Get metadata associated with payment.
     */
    /** @return array<array-key, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get when payment was processed.
     */
    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    /**
     * Create a new result with a matching amount validation.
     */
    public function withMatchingAmount(float $expectedAmount): self
    {
        $matches = $this->amount !== null && abs($this->amount - $expectedAmount) < 0.01;

        $result = clone $this;
        $result->metadata = array_merge($this->metadata, [
            'amount_matches' => $matches,
            'expected_amount' => $expectedAmount,
            'actual_amount' => $this->amount,
        ]);

        return $result;
    }

    /**
     * Convert to array for serialization.
     */
    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'transaction_id' => $this->transactionId,
            'intent_id' => $this->intentId,
            'client_secret' => $this->clientSecret,
            'status' => $this->status?->value,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_data' => $this->gatewayData,
            'metadata' => $this->metadata,
            'processed_at' => $this->processedAt?->format('c'),
        ];
    }
}

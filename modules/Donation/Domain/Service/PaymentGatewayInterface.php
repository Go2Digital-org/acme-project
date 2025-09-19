<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Service;

use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\RefundRequest;

/**
 * Payment Gateway Interface (Port).
 *
 * Defines the contract for all payment gateway implementations.
 * Enables Strategy pattern for flexible gateway switching.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment intent for processing.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult;

    /**
     * Capture/confirm a payment intent.
     */
    public function capturePayment(string $intentId): PaymentResult;

    /**
     * Process a refund for a completed transaction.
     */
    public function refundPayment(RefundRequest $refundRequest): PaymentResult;

    /**
     * Retrieve transaction details from gateway.
     */
    public function getTransaction(string $transactionId): PaymentResult;

    /**
     * Process webhook notifications from gateway.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, string $signature): void;

    /**
     * Get the gateway name/identifier.
     */
    public function getName(): string;

    /**
     * Check if gateway supports specific payment method.
     */
    public function supports(string $paymentMethod): bool;

    /**
     * Get supported currencies for this gateway.
     *
     * @return array<int, string>
     */
    public function getSupportedCurrencies(): array;

    /**
     * Validate gateway configuration.
     */
    public function validateConfiguration(): bool;
}

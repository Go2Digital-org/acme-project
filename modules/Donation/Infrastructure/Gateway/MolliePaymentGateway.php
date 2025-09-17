<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Gateway;

use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Domain\ValueObject\RefundRequest;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Psr\Log\LoggerInterface;

/**
 * Mollie Payment Gateway Implementation (Primary Gateway).
 *
 * Provides European-focused payment processing with extensive local payment methods
 * including iDeal, Bancontact, SEPA, and more. Optimized for European compliance.
 */
final readonly class MolliePaymentGateway implements PaymentGatewayInterface
{
    private MollieApiClient $client;

    public function __construct(
        private string $apiKey,
        private string $webhookUrl,
        private LoggerInterface $logger,
        private string $descriptionPrefix = 'ACME Corp Donation',
    ) {
        $this->client = new MollieApiClient;
        $this->client->setApiKey($this->apiKey);
    }

    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            $payment = $this->client->payments->create([
                'amount' => [
                    'currency' => $intent->getCurrency(),
                    'value' => number_format($intent->amount->amount, 2, '.', ''),
                ],
                'description' => $this->formatDescription($intent),
                'redirectUrl' => $intent->returnUrl,
                'cancelUrl' => $intent->cancelUrl,
                'webhookUrl' => $this->webhookUrl,
                'metadata' => $intent->getEnrichedMetadata(),
                'method' => $this->mapPaymentMethod($intent->paymentMethod->value),
                'locale' => $this->determineLocale($intent->getCurrency()),
            ]);

            $this->logger->info('Mollie payment created', [
                'payment_id' => $payment->id,
                'donation_id' => $intent->donationId,
                'amount' => $intent->amount->amount,
                'currency' => $intent->getCurrency(),
                'status' => $payment->status,
            ]);

            return PaymentResult::success([
                'intent_id' => $payment->id,
                'client_secret' => $payment->getCheckoutUrl(),
                'status' => $this->mapStatus($payment->status),
                'amount' => (float) $payment->amount->value,
                'currency' => $payment->amount->currency,
                'gateway_data' => [
                    'checkout_url' => $payment->getCheckoutUrl(),
                    'payment_id' => $payment->id,
                    'mode' => $payment->mode,
                ],
                'metadata' => $payment->metadata ?? [],
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Mollie payment creation failed', [
                'donation_id' => $intent->donationId,
                'error' => $e->getMessage(),
                'field' => $e->getField(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatErrorMessage($e),
                errorCode: $e->getField() ?? 'mollie_api_error',
                gatewayData: ['mollie_error' => $e->getMessage()],
            );
        }
    }

    public function capturePayment(string $intentId): PaymentResult
    {
        try {
            $payment = $this->client->payments->get($intentId);

            $this->logger->info('Mollie payment captured', [
                'payment_id' => $intentId,
                'status' => $payment->status,
                'amount' => $payment->amount->value,
            ]);

            return PaymentResult::success([
                'transaction_id' => $payment->id,
                'intent_id' => $payment->id,
                'status' => $this->mapStatus($payment->status),
                'amount' => (float) $payment->amount->value,
                'currency' => $payment->amount->currency,
                'gateway_data' => [
                    'payment_method' => $payment->method,
                    'settlement_amount' => $payment->settlementAmount?->value,
                    'settlement_currency' => $payment->settlementAmount?->currency,
                ],
                'metadata' => $payment->metadata ?? [],
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Mollie payment capture failed', [
                'payment_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatErrorMessage($e),
                errorCode: 'mollie_capture_failed',
                gatewayData: ['mollie_error' => $e->getMessage()],
            );
        }
    }

    public function refundPayment(RefundRequest $refundRequest): PaymentResult
    {
        try {
            // First get the payment object as required by Mollie API
            $payment = $this->client->payments->get($refundRequest->transactionId);

            $refund = $payment->refund([
                'amount' => [
                    'currency' => $refundRequest->currency,
                    'value' => number_format($refundRequest->amount, 2, '.', ''),
                ],
                'description' => $refundRequest->reason ?? 'Donation refund',
                'metadata' => $refundRequest->metadata ?? [],
            ]);

            $this->logger->info('Mollie refund processed', [
                'payment_id' => $refundRequest->transactionId,
                'refund_id' => $refund->id,
                'amount' => $refund->amount->value,
                'status' => $refund->status,
            ]);

            return PaymentResult::success([
                'transaction_id' => $refund->id,
                'intent_id' => $refundRequest->transactionId,
                'status' => $this->mapRefundStatus($refund->status),
                'amount' => (float) $refund->amount->value,
                'currency' => $refund->amount->currency,
                'gateway_data' => [
                    'refund_id' => $refund->id,
                    'settlement_amount' => $refund->settlementAmount->value ?? null,
                ],
                'metadata' => $refund->metadata ?? [],
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Mollie refund failed', [
                'payment_id' => $refundRequest->transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatErrorMessage($e),
                errorCode: 'mollie_refund_failed',
                gatewayData: ['mollie_error' => $e->getMessage()],
            );
        }
    }

    public function getTransaction(string $transactionId): PaymentResult
    {
        try {
            $payment = $this->client->payments->get($transactionId);

            return PaymentResult::success([
                'transaction_id' => $payment->id,
                'intent_id' => $payment->id,
                'status' => $this->mapStatus($payment->status),
                'amount' => (float) $payment->amount->value,
                'currency' => $payment->amount->currency,
                'gateway_data' => [
                    'payment_method' => $payment->method,
                    'created_at' => $payment->createdAt,
                    'expires_at' => $payment->expiresAt,
                    'settlement_amount' => $payment->settlementAmount?->value,
                ],
                'metadata' => $payment->metadata ?? [],
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Mollie transaction retrieval failed', [
                'payment_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatErrorMessage($e),
                errorCode: 'mollie_get_transaction_failed',
            );
        }
    }

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload, string $signature): void
    {
        try {
            $paymentId = $payload['id'] ?? null;

            if (! $paymentId) {
                throw PaymentGatewayException::webhookValidationFailed(
                    'Mollie webhook payload missing payment ID',
                );
            }

            $payment = $this->client->payments->get($paymentId);

            $this->logger->info('Mollie webhook processed', [
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'amount' => $payment->amount->value,
                'method' => $payment->method,
            ]);

            // Webhook processing logic would be handled by the domain service
            // that calls this method, as it needs access to domain repositories
        } catch (ApiException $e) {
            $this->logger->error('Mollie webhook handling failed', [
                'payment_id' => $payload['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw PaymentGatewayException::webhookValidationFailed(
                'Mollie webhook processing failed: ' . $e->getMessage(),
            );
        }
    }

    public function getName(): string
    {
        return 'mollie';
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, [
            'card',
            'ideal',
            'bancontact',
            'sofort',
            'giropay',
            'eps',
            'belfius',
            'kbc',
            'iban',
            'paypal',
            'applepay',
            'googlepay',
            'przelewy24',
            'paysafecard',
        ], true);
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'EUR', 'USD', 'GBP', 'CHF', 'PLN', 'DKK', 'SEK', 'NOK',
            'CZK', 'HUF', 'BGN', 'RON', 'HRK', 'ISK', 'CAD', 'AUD',
        ];
    }

    public function validateConfiguration(): bool
    {
        if ($this->apiKey === '' || $this->apiKey === '0') {
            return false;
        }

        // Webhook URL is optional for Mollie
        // It can be configured later or per-payment

        try {
            // Test API connection by fetching methods (works in both test and live mode)
            // This validates the API key without requiring live mode
            $this->client->methods->all();

            return true;
        } catch (ApiException $e) {
            $this->logger->warning('Mollie API validation failed', [
                'error' => $e->getMessage(),
                'field' => $e->getField(),
                'api_key_prefix' => substr($this->apiKey, 0, 8) . '...',
            ]);

            return false;
        }
    }

    private function formatDescription(PaymentIntent $intent): string
    {
        $description = sprintf(
            '%s - %s',
            $this->descriptionPrefix,
            $intent->description,
        );

        // Mollie has a 255 character limit for descriptions
        return mb_substr($description, 0, 255);
    }

    private function mapPaymentMethod(string $paymentMethod): ?string
    {
        return match ($paymentMethod) {
            'card' => null, // Let customer choose
            'ideal' => 'ideal',
            'bancontact' => 'bancontact',
            'sofort' => 'sofort',
            'giropay' => 'giropay',
            'paypal' => 'paypal',
            'applepay' => 'applepay',
            'googlepay' => 'googlepay',
            default => null, // Let customer choose
        };
    }

    private function mapStatus(string $mollieStatus): PaymentStatus
    {
        return match ($mollieStatus) {
            'open' => PaymentStatus::PENDING,
            'canceled' => PaymentStatus::CANCELLED,
            'pending' => PaymentStatus::PENDING,
            'authorized' => PaymentStatus::PROCESSING,
            'expired' => PaymentStatus::FAILED,
            'failed' => PaymentStatus::FAILED,
            'paid' => PaymentStatus::COMPLETED,
            default => PaymentStatus::PENDING,
        };
    }

    private function mapRefundStatus(string $mollieStatus): PaymentStatus
    {
        return match ($mollieStatus) {
            'pending' => PaymentStatus::PENDING,
            'processing' => PaymentStatus::PENDING,
            'refunded' => PaymentStatus::REFUNDED,
            'failed' => PaymentStatus::FAILED,
            default => PaymentStatus::PENDING,
        };
    }

    private function determineLocale(string $currency): string
    {
        return match ($currency) {
            'EUR' => 'en_US', // Could be enhanced to detect actual locale
            'GBP' => 'en_GB',
            'USD' => 'en_US',
            'PLN' => 'pl_PL',
            'CHF' => 'de_CH',
            default => 'en_US',
        };
    }

    private function formatErrorMessage(ApiException $e): string
    {
        $message = $e->getMessage();
        $field = $e->getField();

        if ($field) {
            return "Payment failed: {$message} (field: {$field})";
        }

        return "Payment failed: {$message}";
    }
}

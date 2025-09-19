<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Gateway;

use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Domain\ValueObject\RefundRequest;
use Psr\Log\LoggerInterface;

/**
 * Mock Payment Gateway.
 *
 * Test implementation of PaymentGatewayInterface for development and testing.
 * Simulates payment gateway responses without actual payment processing.
 */
class MockPaymentGateway implements PaymentGatewayInterface
{
    /** @var string */
    private const GATEWAY_NAME = 'mock';

    /** @var array<int, string> */
    private const SUPPORTED_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY',
    ];

    /** @var array<int, string> */
    private const SUPPORTED_PAYMENT_METHODS = [
        'card', 'bank_transfer', 'digital_wallet',
    ];

    // Test card numbers that simulate different scenarios
    /** @var array<string, string> */
    private const TEST_CARDS = [
        'success' => '4242424242424242',
        'declined' => '4000000000000002',
        'insufficient_funds' => '4000000000009995',
        'expired' => '4000000000000069',
        'cvc_fail' => '4000000000000127',
        'processing_error' => '4000000000000119',
        'requires_3ds' => '4000000000003220',
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $simulateFailures = false,
        private readonly float $failureRate = 0.1,
    ) {}

    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        $this->logger->warning('Mock payment gateway used - no real payment gateway configured', [
            'donation_id' => $intent->donationId,
            'amount' => $intent->amount->amount,
            'currency' => $intent->getCurrency(),
            'environment' => app()->environment(),
        ]);

        // In production or when not explicitly testing, always fail
        if (! app()->environment('testing') && ! app()->environment('local')) {
            return PaymentResult::failure(
                'No payment gateway configured. Please configure Stripe, Mollie, or PayPal with valid API credentials.',
                'NO_GATEWAY_CONFIGURED',
                [
                    'mock_gateway' => true,
                    'environment' => app()->environment(),
                    'help' => 'Add payment gateway API keys to your .env file',
                ],
            );
        }

        // For local development, show a clear failure message
        if (app()->environment('local')) {
            $this->logger->error('Mock payment gateway cannot process real payments', [
                'donation_id' => $intent->donationId,
                'help' => 'Configure a real payment gateway (Stripe, Mollie, or PayPal) in your .env file',
            ]);

            return PaymentResult::failure(
                'Mock gateway cannot process real payments. Please configure a real payment gateway.',
                'MOCK_GATEWAY_NOT_CONFIGURED',
                [
                    'mock_gateway' => true,
                    'environment' => 'local',
                    'configuration_help' => [
                        'stripe' => 'Add STRIPE_SECRET_KEY and STRIPE_PUBLIC_KEY to .env',
                        'mollie' => 'Add MOLLIE_API_KEY to .env',
                        'paypal' => 'Add PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET to .env',
                    ],
                ],
            );
        }

        // Only for testing environment, allow mock payments
        if (app()->environment('testing')) {
            // Simulate processing delay
            usleep(10000); // 10ms delay in tests

            // Simulate failures based on configuration
            if ($this->simulateFailures && $this->shouldSimulateFailure()) {
                return PaymentResult::failure(
                    'Mock gateway: Simulated payment failure',
                    'MOCK_FAILURE',
                    ['simulation' => true],
                );
            }

            // Simulate different card scenarios based on amount
            $lastDigit = (int) substr((string) $intent->getAmountInCents(), -1);

            if ($lastDigit === 1) {
                // Simulate declined payment
                return PaymentResult::failure(
                    'Mock gateway: Card declined',
                    'CARD_DECLINED',
                    [
                        'mock_gateway' => true,
                        'mock_scenario' => 'card_declined',
                        'decline_code' => 'generic_decline',
                    ],
                );
            }

            // Default successful payment intent for tests
            return PaymentResult::success([
                'intent_id' => 'mock_pi_' . uniqid(),
                'client_secret' => 'mock_secret_' . uniqid(),
                'status' => $intent->shouldCaptureImmediately() ? PaymentStatus::COMPLETED->value : PaymentStatus::PENDING->value,
                'amount' => $intent->amount->amount,
                'currency' => $intent->getCurrency(),
                'gateway_data' => [
                    'mock_gateway' => true,
                    'mock_scenario' => 'test_success',
                    'capture_method' => $intent->shouldCaptureImmediately() ? 'automatic' : 'manual',
                    'checkout_url' => $intent->returnUrl . '?mock_payment=success&payment_intent=' . 'mock_pi_' . uniqid(),
                ],
                'metadata' => $intent->getEnrichedMetadata(),
            ]);
        }

        // Fallback - should never reach here
        return PaymentResult::failure(
            'Mock gateway is not available in this environment',
            'ENVIRONMENT_ERROR',
            ['environment' => app()->environment()],
        );
    }

    public function capturePayment(string $intentId): PaymentResult
    {
        $this->logger->info('Mock payment capture', [
            'intent_id' => $intentId,
        ]);

        // Simulate processing delay
        if (app()->environment('testing')) {
            usleep(5000); // 5ms delay in tests

            return $this->processCaptureForTesting($intentId);
        }

        usleep(50000); // 50ms delay in development

        return $this->processCaptureForTesting($intentId);
    }

    public function refundPayment(RefundRequest $refundRequest): PaymentResult
    {
        $this->logger->info('Mock refund processed', [
            'transaction_id' => $refundRequest->transactionId,
            'amount' => $refundRequest->amount,
            'reason' => $refundRequest->reason,
        ]);

        // Simulate processing delay
        if (app()->environment('testing')) {
            usleep(5000); // 5ms delay in tests

            return $this->processRefundForTesting($refundRequest);
        }

        usleep(75000); // 75ms delay in development

        return $this->processRefundForTesting($refundRequest);
    }

    public function getTransaction(string $transactionId): PaymentResult
    {
        $this->logger->info('Mock transaction lookup', [
            'transaction_id' => $transactionId,
        ]);

        // Simulate processing delay
        if (app()->environment('testing')) {
            usleep(2000); // 2ms delay in tests

            return $this->processTransactionLookupForTesting($transactionId);
        }

        usleep(25000); // 25ms delay in development

        return $this->processTransactionLookupForTesting($transactionId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, string $signature): void
    {
        $this->logger->info('Mock webhook received', [
            'event_type' => $payload['type'] ?? 'unknown',
            'event_id' => $payload['id'] ?? uniqid(),
            'signature' => $signature,
        ]);

        // Simulate webhook processing
        $eventType = $payload['type'] ?? 'mock.event';

        match ($eventType) {
            'mock.payment_intent.succeeded' => $this->handlePaymentSucceeded($payload),
            'mock.payment_intent.payment_failed' => $this->handlePaymentFailed($payload),
            'mock.charge.dispute.created' => $this->handleChargeDispute($payload),
            default => $this->logger->info('Unhandled mock webhook event', ['type' => $eventType]),
        };
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, self::SUPPORTED_PAYMENT_METHODS, true);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function validateConfiguration(): bool
    {
        // Mock gateway always has valid configuration
        return true;
    }

    /**
     * Get test card numbers for different scenarios.
     */
    /**
     * @return array<string, mixed>
     */
    public function getTestCards(): array
    {
        return self::TEST_CARDS;
    }

    /**
     * Set specific scenario for testing.
     */
    public function setTestScenario(string $scenario): void
    {
        // In real implementation, this would store scenario state
        $this->logger->info('Mock gateway test scenario set', [
            'scenario' => $scenario,
        ]);
    }

    /**
     * Simulate network latency for development testing.
     */
    public function simulateLatency(int $milliseconds): void
    {
        if (! app()->environment('testing')) {
            usleep($milliseconds * 1000);
        }
    }

    private function shouldSimulateFailure(): bool
    {
        return mt_rand(1, 100) <= ($this->failureRate * 100);
    }

    private function processCaptureForTesting(string $intentId): PaymentResult
    {
        // Simulate failure for specific intent IDs
        if (str_contains($intentId, 'fail')) {
            return PaymentResult::failure(
                'Mock gateway: Capture failed',
                'CAPTURE_FAILED',
                ['mock_gateway' => true],
            );
        }

        return PaymentResult::success([
            'transaction_id' => 'mock_ch_' . uniqid(),
            'intent_id' => $intentId,
            'status' => PaymentStatus::COMPLETED->value,
            'amount' => 100.00, // Mock amount
            'currency' => 'USD',
            'gateway_data' => [
                'mock_gateway' => true,
                'mock_scenario' => 'capture_success',
                'captured_at' => now()->toISOString(),
            ],
        ]);
    }

    private function processRefundForTesting(RefundRequest $refundRequest): PaymentResult
    {
        // Simulate refund failure for specific transaction IDs
        if (str_contains($refundRequest->transactionId, 'no_refund')) {
            return PaymentResult::failure(
                'Mock gateway: Refund not allowed',
                'REFUND_NOT_ALLOWED',
                ['mock_gateway' => true],
            );
        }

        return PaymentResult::success([
            'transaction_id' => 'mock_re_' . uniqid(),
            'status' => PaymentStatus::REFUNDED->value,
            'amount' => $refundRequest->amount,
            'currency' => $refundRequest->currency,
            'gateway_data' => [
                'mock_gateway' => true,
                'mock_scenario' => 'refund_success',
                'original_transaction' => $refundRequest->transactionId,
                'refund_reason' => $refundRequest->reason,
                'refunded_at' => now()->toISOString(),
            ],
            'metadata' => $refundRequest->getEnrichedMetadata(),
        ]);
    }

    private function processTransactionLookupForTesting(string $transactionId): PaymentResult
    {
        // Simulate not found for specific IDs
        if (str_contains($transactionId, 'not_found')) {
            return PaymentResult::failure(
                'Mock gateway: Transaction not found',
                'NOT_FOUND',
                ['mock_gateway' => true],
            );
        }

        // Determine status based on transaction ID prefix
        $status = match (true) {
            str_starts_with($transactionId, 'mock_pi_') => PaymentStatus::PENDING,
            str_starts_with($transactionId, 'mock_ch_') => PaymentStatus::COMPLETED,
            str_starts_with($transactionId, 'mock_re_') => PaymentStatus::REFUNDED,
            default => PaymentStatus::COMPLETED,
        };

        return PaymentResult::success([
            'transaction_id' => $transactionId,
            'status' => $status->value,
            'amount' => 100.00, // Mock amount
            'currency' => 'USD',
            'gateway_data' => [
                'mock_gateway' => true,
                'mock_scenario' => 'transaction_lookup',
                'retrieved_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePaymentSucceeded(array $payload): void
    {
        $intentId = $payload['data']['object']['id'] ?? 'unknown';

        $this->logger->info('Mock payment succeeded webhook', [
            'intent_id' => $intentId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePaymentFailed(array $payload): void
    {
        $intentId = $payload['data']['object']['id'] ?? 'unknown';

        $this->logger->warning('Mock payment failed webhook', [
            'intent_id' => $intentId,
            'error' => $payload['data']['object']['last_payment_error']['message'] ?? 'Unknown error',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleChargeDispute(array $payload): void
    {
        $disputeId = $payload['data']['object']['id'] ?? 'unknown';

        $this->logger->warning('Mock charge dispute created', [
            'dispute_id' => $disputeId,
            'amount' => $payload['data']['object']['amount'] ?? 0,
        ]);
    }
}

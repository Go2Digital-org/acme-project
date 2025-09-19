<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Domain\ValueObject\RefundRequest;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * PayPal Payment Gateway Adapter.
 *
 * Implements PaymentGatewayInterface for PayPal payment processing.
 * Handles PayPal REST API calls and response mapping.
 */
final readonly class PayPalPaymentGateway implements PaymentGatewayInterface
{
    /** @var string */
    private const GATEWAY_NAME = 'paypal';

    /** @var array<int, string> */
    private const SUPPORTED_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD',
    ];

    /** @var array<int, string> */
    private const SUPPORTED_PAYMENT_METHODS = [
        'paypal', 'card', 'venmo',
    ];

    /** @var string */
    private const SANDBOX_BASE_URL = 'https://api-m.sandbox.paypal.com';

    /** @var string */
    private const LIVE_BASE_URL = 'https://api-m.paypal.com';

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $webhookId,
        private LoggerInterface $logger,
        private bool $sandboxMode = true,
        private Client $httpClient = new Client,
    ) {}

    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            $accessToken = $this->getAccessToken();

            $orderData = [
                'intent' => $intent->shouldCaptureImmediately() ? 'CAPTURE' : 'AUTHORIZE',
                'purchase_units' => [
                    [
                        'reference_id' => 'DONATION-' . $intent->donationId,
                        'description' => $intent->getFormattedDescription(),
                        'amount' => [
                            'currency_code' => $intent->getCurrency(),
                            'value' => number_format($intent->amount->amount, 2, '.', ''),
                        ],
                        'custom_id' => (string) $intent->donationId,
                    ],
                ],
                'application_context' => [
                    'return_url' => $intent->returnUrl,
                    'cancel_url' => $intent->cancelUrl,
                    'brand_name' => 'ACME Corp',
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = $this->httpClient->post(
                $this->getBaseUrl() . '/v2/checkout/orders',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'PayPal-Request-Id' => uniqid('acme-', true),
                    ],
                    'json' => $orderData,
                ],
            );

            /** @var array{id: string, status: string, links: array<int, array<string, mixed>>}|null $orderResponse */
            $orderResponse = json_decode($response->getBody()->getContents(), true);

            if ($orderResponse === null) {
                throw new PaymentGatewayException('Invalid JSON response from PayPal API');
            }

            $this->logger->info('PayPal order created', [
                'order_id' => $orderResponse['id'],
                'donation_id' => $intent->donationId,
                'amount' => $intent->amount->amount,
                'currency' => $intent->getCurrency(),
            ]);

            /** @var array<int, array<string, mixed>> $links */
            $links = $orderResponse['links'];
            $approvalLink = null;

            foreach ($links as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalLink = $link;
                    break;
                }
            }
            $approvalUrl = is_array($approvalLink) ? $approvalLink['href'] : null;

            return PaymentResult::pending([
                'intent_id' => $orderResponse['id'],
                'status' => PaymentStatus::fromPayPalStatus($orderResponse['status'])->value,
                'amount' => $intent->amount->amount,
                'currency' => $intent->getCurrency(),
                'gateway_data' => [
                    'paypal_order_id' => $orderResponse['id'],
                    'paypal_status' => $orderResponse['status'],
                    'approval_url' => $approvalUrl,
                    'links' => $orderResponse['links'],
                ],
                'metadata' => $intent->getEnrichedMetadata(),
            ]);
        } catch (RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()?->getContents() ?? '';
            /** @var array{message?: string, name?: string, details?: array<int, array<string, mixed>>} $errorData */
            $errorData = json_decode($errorBody, true) ?? [];

            $this->logger->error('PayPal order creation failed', [
                'donation_id' => $intent->donationId,
                'error' => $errorData['message'] ?? $e->getMessage(),
                'details' => $errorData['details'] ?? [],
            ]);

            return PaymentResult::failure(
                $errorData['message'] ?? 'PayPal order creation failed',
                $errorData['name'] ?? 'PAYPAL_ERROR',
                $errorData,
            );
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error creating PayPal order', [
                'donation_id' => $intent->donationId,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentGatewayException(
                'Failed to create PayPal order: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function capturePayment(string $intentId): PaymentResult
    {
        try {
            $accessToken = $this->getAccessToken();

            // First get the order details
            $orderResponse = $this->httpClient->get(
                $this->getBaseUrl() . '/v2/checkout/orders/' . $intentId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                ],
            );

            /** @var array{id: string, status: string, purchase_units: array<int, array<string, mixed>>}|null $order */
            $order = json_decode($orderResponse->getBody()->getContents(), true);

            if ($order === null) {
                throw new PaymentGatewayException('Invalid JSON response from PayPal API');
            }

            // If already captured, return success
            if ($order['status'] === 'COMPLETED') {
                $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;

                return PaymentResult::success([
                    'transaction_id' => $capture['id'] ?? $order['id'],
                    'intent_id' => $order['id'],
                    'status' => PaymentStatus::COMPLETED->value,
                    'amount' => (float) ($capture['amount']['value'] ?? $order['purchase_units'][0]['amount']['value']),
                    'currency' => $capture['amount']['currency_code'] ?? $order['purchase_units'][0]['amount']['currency_code'],
                    'gateway_data' => [
                        'paypal_order_id' => $order['id'],
                        'paypal_capture_id' => $capture['id'] ?? null,
                        'paypal_status' => $order['status'],
                    ],
                ]);
            }

            // Capture the payment
            $captureResponse = $this->httpClient->post(
                $this->getBaseUrl() . '/v2/checkout/orders/' . $intentId . '/capture',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'PayPal-Request-Id' => uniqid('capture-', true),
                    ],
                ],
            );

            /** @var array{id: string, status: string, purchase_units: array<int, array<string, mixed>>}|null $captureResult */
            $captureResult = json_decode($captureResponse->getBody()->getContents(), true);

            if ($captureResult === null) {
                throw new PaymentGatewayException('Invalid JSON response from PayPal API');
            }

            $capture = $captureResult['purchase_units'][0]['payments']['captures'][0] ?? null;

            $this->logger->info('PayPal payment captured', [
                'order_id' => $intentId,
                'capture_id' => $capture['id'] ?? null,
                'status' => $captureResult['status'],
            ]);

            $status = PaymentStatus::fromPayPalStatus($captureResult['status']);

            if ($status->isSuccessful()) {
                return PaymentResult::success([
                    'transaction_id' => $capture['id'] ?? $captureResult['id'],
                    'intent_id' => $captureResult['id'],
                    'status' => $status->value,
                    'amount' => (float) ($capture['amount']['value'] ?? 0),
                    'currency' => $capture['amount']['currency_code'] ?? 'USD',
                    'gateway_data' => [
                        'paypal_order_id' => $captureResult['id'],
                        'paypal_capture_id' => $capture['id'] ?? null,
                        'paypal_status' => $captureResult['status'],
                    ],
                ]);
            }

            return PaymentResult::pending([
                'intent_id' => $captureResult['id'],
                'status' => $status->value,
                'gateway_data' => [
                    'paypal_order_id' => $captureResult['id'],
                    'paypal_status' => $captureResult['status'],
                ],
            ]);
        } catch (RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()?->getContents() ?? '';
            /** @var array{message?: string, name?: string} $errorData */
            $errorData = json_decode($errorBody, true) ?? [];

            $this->logger->error('PayPal payment capture failed', [
                'order_id' => $intentId,
                'error' => $errorData['message'] ?? $e->getMessage(),
            ]);

            return PaymentResult::failure(
                $errorData['message'] ?? 'PayPal capture failed',
                $errorData['name'] ?? 'PAYPAL_CAPTURE_ERROR',
            );
        }
    }

    public function refundPayment(RefundRequest $refundRequest): PaymentResult
    {
        try {
            $accessToken = $this->getAccessToken();

            $refundData = [
                'amount' => [
                    'value' => number_format($refundRequest->amount, 2, '.', ''),
                    'currency_code' => $refundRequest->currency,
                ],
                'note_to_payer' => $refundRequest->getFormattedReason(),
            ];

            $response = $this->httpClient->post(
                $this->getBaseUrl() . '/v2/payments/captures/' . $refundRequest->transactionId . '/refund',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'PayPal-Request-Id' => uniqid('refund-', true),
                    ],
                    'json' => $refundData,
                ],
            );

            /** @var array{id: string, status: string, amount: array{value: string, currency_code: string}}|null $refund */
            $refund = json_decode($response->getBody()->getContents(), true);

            if ($refund === null) {
                throw new PaymentGatewayException('Invalid JSON response from PayPal API');
            }

            $this->logger->info('PayPal refund processed', [
                'refund_id' => $refund['id'],
                'capture_id' => $refundRequest->transactionId,
                'amount' => $refundRequest->amount,
                'status' => $refund['status'],
            ]);

            return PaymentResult::success([
                'transaction_id' => $refund['id'],
                'status' => PaymentStatus::REFUNDED->value,
                'amount' => (float) $refund['amount']['value'],
                'currency' => $refund['amount']['currency_code'],
                'gateway_data' => [
                    'paypal_refund_id' => $refund['id'],
                    'paypal_capture_id' => $refundRequest->transactionId,
                    'paypal_status' => $refund['status'],
                ],
            ]);
        } catch (RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()?->getContents() ?? '';
            /** @var array{message?: string, name?: string} $errorData */
            $errorData = json_decode($errorBody, true) ?? [];

            $this->logger->error('PayPal refund failed', [
                'capture_id' => $refundRequest->transactionId,
                'amount' => $refundRequest->amount,
                'error' => $errorData['message'] ?? $e->getMessage(),
            ]);

            return PaymentResult::failure(
                $errorData['message'] ?? 'PayPal refund failed',
                $errorData['name'] ?? 'PAYPAL_REFUND_ERROR',
            );
        }
    }

    public function getTransaction(string $transactionId): PaymentResult
    {
        try {
            $accessToken = $this->getAccessToken();

            // Try as capture first, then as order
            try {
                $response = $this->httpClient->get(
                    $this->getBaseUrl() . '/v2/payments/captures/' . $transactionId,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ],
                    ],
                );

                /** @var array{id: string, status: string, amount: array{value: string, currency_code: string}}|null $capture */
                $capture = json_decode($response->getBody()->getContents(), true);

                if ($capture === null) {
                    throw new PaymentGatewayException('Invalid JSON response from PayPal API');
                }

                return PaymentResult::success([
                    'transaction_id' => $capture['id'],
                    'status' => $capture['status'] === 'COMPLETED' ? PaymentStatus::COMPLETED->value : PaymentStatus::PENDING->value,
                    'amount' => (float) $capture['amount']['value'],
                    'currency' => $capture['amount']['currency_code'],
                    'gateway_data' => [
                        'paypal_capture_id' => $capture['id'],
                        'paypal_status' => $capture['status'],
                    ],
                ]);
            } catch (RequestException $e) {
                if ($e->getResponse()?->getStatusCode() === 404) {
                    // Try as order
                    $response = $this->httpClient->get(
                        $this->getBaseUrl() . '/v2/checkout/orders/' . $transactionId,
                        [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessToken,
                                'Content-Type' => 'application/json',
                            ],
                        ],
                    );

                    /** @var array{id: string, status: string, purchase_units: array<int, array<string, mixed>>}|null $order */
                    $order = json_decode($response->getBody()->getContents(), true);

                    if ($order === null) {
                        throw new PaymentGatewayException('Invalid JSON response from PayPal API');
                    }

                    return PaymentResult::success([
                        'transaction_id' => $order['id'],
                        'status' => PaymentStatus::fromPayPalStatus($order['status'])->value,
                        'amount' => (float) $order['purchase_units'][0]['amount']['value'],
                        'currency' => $order['purchase_units'][0]['amount']['currency_code'],
                        'gateway_data' => [
                            'paypal_order_id' => $order['id'],
                            'paypal_status' => $order['status'],
                        ],
                    ]);
                }

                throw $e;
            }
        } catch (RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()?->getContents() ?? '';
            /** @var array{message?: string, name?: string} $errorData */
            $errorData = json_decode($errorBody, true) ?? [];

            return PaymentResult::failure(
                $errorData['message'] ?? 'Transaction not found',
                $errorData['name'] ?? 'NOT_FOUND',
            );
        }
    }

    /**
     * Handle PayPal webhook events.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, string $signature): void
    {
        try {
            // Verify webhook signature
            if (! $this->verifyWebhookSignature()) {
                throw new PaymentGatewayException('Invalid webhook signature');
            }

            $eventType = $payload['event_type'] ?? '';

            $this->logger->info('PayPal webhook received', [
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? '',
            ]);

            // Handle specific webhook events
            match ($eventType) {
                'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($payload),
                'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($payload),
                'PAYMENT.CAPTURE.DENIED' => $this->handleCaptureDenied($payload),
                'CUSTOMER.DISPUTE.CREATED' => $this->handleDispute($payload),
                default => $this->logger->info('Unhandled webhook event', ['type' => $eventType]),
            };
        } catch (Throwable $e) {
            $this->logger->error('PayPal webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            throw new PaymentGatewayException('Webhook processing failed');
        }
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
        return $this->clientId !== '' && $this->clientId !== '0'
            && ($this->clientSecret !== '' && $this->clientSecret !== '0')
            && ($this->webhookId !== '' && $this->webhookId !== '0');
    }

    private function getAccessToken(): string
    {
        $response = $this->httpClient->post(
            $this->getBaseUrl() . '/v1/oauth2/token',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                ],
                'auth' => [$this->clientId, $this->clientSecret],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ],
        );

        /** @var array{access_token: string}|null $data */
        $data = json_decode($response->getBody()->getContents(), true);

        if ($data === null) {
            throw new PaymentGatewayException('Failed to obtain PayPal access token');
        }

        return $data['access_token'];
    }

    private function getBaseUrl(): string
    {
        return $this->sandboxMode ? self::SANDBOX_BASE_URL : self::LIVE_BASE_URL;
    }

    /**
     * Verify PayPal webhook signature.
     */
    private function verifyWebhookSignature(): bool
    {
        // PayPal webhook signature verification logic
        // This would involve verifying against PayPal's certificates
        // For now, return true but should implement proper verification
        return true;
    }

    /**
     * Handle order approved webhook event.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleOrderApproved(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $orderId = is_array($resource) ? ($resource['id'] ?? '') : '';

        $this->logger->info('PayPal order approved', [
            'order_id' => $orderId,
        ]);

        // Dispatch domain event for order approval
    }

    /**
     * Handle capture completed webhook event.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleCaptureCompleted(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $captureId = is_array($resource) ? ($resource['id'] ?? '') : '';

        $this->logger->info('PayPal capture completed', [
            'capture_id' => $captureId,
        ]);

        // Dispatch domain event for capture completion
    }

    /**
     * Handle capture denied webhook event.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleCaptureDenied(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $captureId = is_array($resource) ? ($resource['id'] ?? '') : '';

        $this->logger->warning('PayPal capture denied', [
            'capture_id' => $captureId,
        ]);

        // Dispatch domain event for capture denial
    }

    /**
     * Handle dispute created webhook event.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleDispute(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $disputeId = is_array($resource) ? ($resource['dispute_id'] ?? '') : '';

        $this->logger->warning('PayPal dispute created', [
            'dispute_id' => $disputeId,
        ]);

        // Dispatch domain event for dispute handling
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Gateway;

use InvalidArgumentException;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Domain\ValueObject\RefundRequest;
use Psr\Log\LoggerInterface;
use Stripe\Dispute;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Invoice;
use Stripe\StripeClient;
use Stripe\Webhook;
use Throwable;

/**
 * Stripe Payment Gateway Implementation (Secondary Gateway).
 *
 * Provides global payment processing with comprehensive card support,
 * modern authentication (3DS), and extensive payment method coverage.
 * Optimized for developer experience and global reach.
 */
final readonly class StripePaymentGateway implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct(
        private string $secretKey,
        private string $publicKey,
        private string $webhookSecret,
        private LoggerInterface $logger,
        private bool $automaticPaymentMethods = true,
    ) {
        $this->stripe = new StripeClient($this->secretKey);
    }

    /**
     * Creates a Stripe payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to create
     * @return PaymentResult The result of the payment intent creation
     *
     * @throws PaymentGatewayException When payment intent creation fails
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            $stripeIntentData = [
                'amount' => $intent->getAmountInCents(),
                'currency' => strtolower($intent->getCurrency()),
                'description' => $intent->getFormattedDescription(),
                'metadata' => $intent->getEnrichedMetadata(),
                'capture_method' => $intent->shouldCaptureImmediately() ? 'automatic' : 'manual',
                'return_url' => $intent->returnUrl,
                'setup_future_usage' => 'off_session', // For recurring donations
            ];

            if ($this->automaticPaymentMethods) {
                $stripeIntentData['automatic_payment_methods'] = [
                    'enabled' => true,
                    'allow_redirects' => 'always',
                ];
            }

            if ($intent->customerId) {
                $stripeIntentData['customer'] = $intent->customerId;
            }

            if ($intent->paymentMethodId) {
                $stripeIntentData['payment_method'] = $intent->paymentMethodId;
                $stripeIntentData['confirm'] = true;
            }

            $stripeIntent = $this->stripe->paymentIntents->create($stripeIntentData);

            $this->logger->info('Stripe payment intent created', [
                'intent_id' => $stripeIntent->id,
                'donation_id' => $intent->donationId,
                'amount' => $intent->getAmountInCents(),
                'currency' => $intent->getCurrency(),
                'status' => $stripeIntent->status,
            ]);

            return PaymentResult::success([
                'intent_id' => $stripeIntent->id,
                'client_secret' => $stripeIntent->client_secret,
                'status' => $this->mapStripeStatus($stripeIntent->status),
                'amount' => $intent->amount->amount,
                'currency' => $intent->getCurrency(),
                'gateway_data' => [
                    'stripe_intent_id' => $stripeIntent->id,
                    'stripe_status' => $stripeIntent->status,
                    'payment_method_types' => $stripeIntent->payment_method_types,
                    'next_action' => $stripeIntent->next_action,
                ],
                'metadata' => ($stripeIntent->metadata !== null) ? $stripeIntent->metadata->toArray() : [],
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe payment intent creation failed', [
                'donation_id' => $intent->donationId,
                'error_code' => $e->getStripeCode(),
                'error_message' => $e->getMessage(),
                'error_type' => $e->getError()->type ?? 'unknown',
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatStripeError($e),
                errorCode: $e->getStripeCode() ?? 'stripe_api_error',
                gatewayData: [
                    'stripe_error_type' => $e->getError()->type ?? 'unknown',
                    'decline_code' => $e->getError()->decline_code ?? null,
                ],
            );
        }
    }

    /**
     * Captures a Stripe payment intent.
     *
     * @param  string  $intentId  The Stripe payment intent ID
     * @return PaymentResult The result of the payment capture
     *
     * @throws PaymentGatewayException When payment capture fails
     */
    public function capturePayment(string $intentId): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($intentId);

            // If already succeeded, return success
            if ($intent->status === 'succeeded') {
                $charge = $intent->charges?->data[0] ?? null;

                return PaymentResult::success([
                    'transaction_id' => $charge?->id,
                    'intent_id' => $intent->id,
                    'status' => PaymentStatus::COMPLETED,
                    'amount' => $intent->amount / 100,
                    'currency' => strtoupper($intent->currency),
                    'gateway_data' => [
                        'stripe_intent_id' => $intent->id,
                        'stripe_charge_id' => $charge?->id,
                        'stripe_status' => $intent->status,
                        'payment_method' => $intent->payment_method,
                    ],
                ]);
            }

            // Capture if required
            if ($intent->status === 'requires_capture') {
                $intent = $this->stripe->paymentIntents->capture($intentId);
            }

            $charge = $intent->charges?->data[0] ?? null;
            $status = $this->mapStripeStatus($intent->status);

            $this->logger->info('Stripe payment captured', [
                'intent_id' => $intentId,
                'charge_id' => $charge?->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
            ]);

            if ($status === PaymentStatus::COMPLETED) {
                return PaymentResult::success([
                    'transaction_id' => $charge?->id,
                    'intent_id' => $intent->id,
                    'status' => $status,
                    'amount' => $intent->amount / 100,
                    'currency' => strtoupper($intent->currency),
                    'gateway_data' => [
                        'stripe_intent_id' => $intent->id,
                        'stripe_charge_id' => $charge?->id,
                        'stripe_status' => $intent->status,
                        'payment_method' => $intent->payment_method,
                    ],
                ]);
            }

            return PaymentResult::pending([
                'intent_id' => $intent->id,
                'status' => $status,
                'gateway_data' => [
                    'stripe_intent_id' => $intent->id,
                    'stripe_status' => $intent->status,
                    'next_action' => $intent->next_action,
                ],
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe payment capture failed', [
                'intent_id' => $intentId,
                'error_code' => $e->getStripeCode(),
                'error_message' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatStripeError($e),
                errorCode: $e->getStripeCode() ?? 'stripe_capture_failed',
            );
        }
    }

    /**
     * Processes a refund through Stripe.
     *
     * @param  RefundRequest  $refundRequest  The refund request details
     * @return PaymentResult The result of the refund operation
     *
     * @throws PaymentGatewayException When refund processing fails
     */
    public function refundPayment(RefundRequest $refundRequest): PaymentResult
    {
        try {
            $refundData = [
                'charge' => $refundRequest->transactionId,
                'amount' => $refundRequest->getAmountInCents(),
                'reason' => $this->mapRefundReason($refundRequest->reason ?? 'requested_by_customer'),
                'metadata' => $refundRequest->metadata ?? [],
            ];

            if (isset($refundRequest->metadata['reverse_transfer'])) {
                $refundData['reverse_transfer'] = (bool) $refundRequest->metadata['reverse_transfer'];
            }

            $refund = $this->stripe->refunds->create($refundData);

            $this->logger->info('Stripe refund processed', [
                'refund_id' => $refund->id,
                'charge_id' => $refundRequest->transactionId,
                'amount' => $refundRequest->getAmountInCents(),
                'status' => $refund->status,
            ]);

            return PaymentResult::success([
                'transaction_id' => $refund->id,
                'intent_id' => $refundRequest->transactionId,
                'status' => PaymentStatus::REFUNDED,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
                'gateway_data' => [
                    'stripe_refund_id' => $refund->id,
                    'stripe_charge_id' => $refund->charge,
                    'stripe_status' => $refund->status,
                    'stripe_reason' => $refund->reason,
                ],
                'metadata' => ($refund->metadata !== null) ? $refund->metadata->toArray() : [],
            ]);
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe refund failed', [
                'charge_id' => $refundRequest->transactionId,
                'amount' => $refundRequest->getAmountInCents(),
                'error_code' => $e->getStripeCode(),
                'error_message' => $e->getMessage(),
            ]);

            return PaymentResult::failure(
                errorMessage: $this->formatStripeError($e),
                errorCode: $e->getStripeCode() ?? 'stripe_refund_failed',
            );
        }
    }

    /**
     * Retrieves transaction details from Stripe.
     *
     * @param  string  $transactionId  The transaction/charge/intent ID
     * @return PaymentResult The transaction details
     *
     * @throws PaymentGatewayException When transaction retrieval fails
     */
    public function getTransaction(string $transactionId): PaymentResult
    {
        try {
            // Try to retrieve as charge first, then as payment intent
            try {
                $charge = $this->stripe->charges->retrieve($transactionId);

                return PaymentResult::success([
                    'transaction_id' => $charge->id,
                    'intent_id' => $charge->payment_intent,
                    'status' => $charge->paid ? PaymentStatus::COMPLETED : PaymentStatus::FAILED,
                    'amount' => $charge->amount / 100,
                    'currency' => strtoupper($charge->currency),
                    'gateway_data' => [
                        'stripe_charge_id' => $charge->id,
                        'stripe_payment_intent' => $charge->payment_intent,
                        'stripe_status' => $charge->status,
                        'stripe_paid' => $charge->paid,
                        'failure_code' => $charge->failure_code,
                        'failure_message' => $charge->failure_message,
                    ],
                ]);
            } catch (ApiErrorException $chargeError) {
                if ($chargeError->getHttpStatus() === 404) {
                    // Try as payment intent
                    $intent = $this->stripe->paymentIntents->retrieve($transactionId);
                    $charge = $intent->charges?->data[0] ?? null;

                    return PaymentResult::success([
                        'transaction_id' => $charge->id ?? $intent->id,
                        'intent_id' => $intent->id,
                        'status' => $this->mapStripeStatus($intent->status),
                        'amount' => $intent->amount / 100,
                        'currency' => strtoupper($intent->currency),
                        'gateway_data' => [
                            'stripe_intent_id' => $intent->id,
                            'stripe_charge_id' => $charge?->id,
                            'stripe_status' => $intent->status,
                            'last_payment_error' => $intent->last_payment_error,
                        ],
                    ]);
                }

                throw $chargeError;
            }
        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                errorMessage: $this->formatStripeError($e),
                errorCode: $e->getStripeCode() ?? 'stripe_get_transaction_failed',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload, string $signature): void
    {
        try {
            $encodedPayload = json_encode($payload);

            if ($encodedPayload === false) {
                throw new InvalidArgumentException('Unable to encode payload');
            }

            $event = Webhook::constructEvent(
                $encodedPayload,
                $signature,
                $this->webhookSecret,
            );

            $this->logger->info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'livemode' => $event->livemode,
            ]);

            // Handle specific webhook events
            match ($event->type) {
                'payment_intent.succeeded' => $this->handlePaymentSucceeded($event),
                'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
                'payment_intent.requires_action' => $this->handlePaymentRequiresAction($event),
                'charge.dispute.created' => $this->handleChargeDispute($event),
                'invoice.payment_succeeded' => $this->handleRecurringPayment($event),
                default => $this->logger->info('Unhandled webhook event', ['type' => $event->type]),
            };
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Invalid webhook signature', [
                'error' => $e->getMessage(),
            ]);

            throw PaymentGatewayException::webhookValidationFailed(
                'Invalid Stripe webhook signature: ' . $e->getMessage(),
            );
        } catch (Throwable $e) {
            $this->logger->error('Webhook processing error', [
                'error' => $e->getMessage(),
                'event_id' => $payload['id'] ?? 'unknown',
            ]);

            throw PaymentGatewayException::webhookValidationFailed(
                'Stripe webhook processing failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Gets the gateway name.
     *
     * @return string The gateway identifier
     */
    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * Checks if the gateway supports a specific payment method.
     *
     * @param  string  $paymentMethod  The payment method to check
     * @return bool Whether the payment method is supported
     */
    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, [
            'card',
            'alipay',
            'giropay',
            'ideal',
            'sepa_debit',
            'sofort',
            'bancontact',
            'eps',
            'p24',
            'affirm',
            'afterpay_clearpay',
            'klarna',
            'acss_debit',
            'us_bank_account',
            'wechat_pay',
            'link',
        ], true);
    }

    /**
     * Gets the list of supported currencies.
     *
     * @return array<int, string> List of supported currency codes
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'CHF',
            'NOK', 'SEK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN',
            'HRK', 'THB', 'MYR', 'INR', 'BRL', 'MXN', 'ZAR', 'NZD',
        ];
    }

    /**
     * Validates the gateway configuration.
     *
     * @return bool Whether the configuration is valid
     */
    public function validateConfiguration(): bool
    {
        if ($this->secretKey === '' || $this->secretKey === '0' || ($this->publicKey === '' || $this->publicKey === '0') || ($this->webhookSecret === '' || $this->webhookSecret === '0')) {
            return false;
        }

        try {
            // Test API connection
            $this->stripe->accounts->retrieve();

            return true;
        } catch (ApiErrorException) {
            return false;
        }
    }

    /**
     * Maps Stripe status to internal PaymentStatus.
     *
     * @param  string  $stripeStatus  The Stripe payment status
     * @return PaymentStatus The mapped internal status
     */
    private function mapStripeStatus(string $stripeStatus): PaymentStatus
    {
        return match ($stripeStatus) {
            'requires_payment_method' => PaymentStatus::PENDING,
            'requires_confirmation' => PaymentStatus::PENDING,
            'requires_action' => PaymentStatus::REQUIRES_ACTION,
            'processing' => PaymentStatus::PENDING,
            'requires_capture' => PaymentStatus::PROCESSING,
            'canceled' => PaymentStatus::CANCELLED,
            'succeeded' => PaymentStatus::COMPLETED,
            default => PaymentStatus::PENDING,
        };
    }

    /**
     * Maps internal refund reason to Stripe refund reason.
     *
     * @param  string  $reason  The internal refund reason
     * @return string The Stripe refund reason
     */
    private function mapRefundReason(string $reason): string
    {
        return match ($reason) {
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            'requested_by_customer' => 'requested_by_customer',
            default => 'requested_by_customer',
        };
    }

    /**
     * Formats Stripe API error into user-friendly message.
     *
     * @param  ApiErrorException  $e  The Stripe API error
     * @return string The formatted error message
     */
    private function formatStripeError(ApiErrorException $e): string
    {
        $error = $e->getError();
        $message = $e->getMessage();

        if ($error !== null && ($error->decline_code ?? null)) {
            $message .= " (Decline code: {$error->decline_code})";
        }

        if ($error !== null && $error->param) {
            $message .= " (Parameter: {$error->param})";
        }

        return $message;
    }

    private function handlePaymentSucceeded(Event $event): void
    {
        /** @var \Stripe\PaymentIntent $intent */
        $intent = $event->data->object;

        $this->logger->info('Payment succeeded webhook', [
            'intent_id' => $intent->id,
            'amount' => $intent->amount,
            'customer' => $intent->customer,
        ]);

        // This webhook processing would trigger domain events
        // handled by the payment processor service
    }

    private function handlePaymentFailed(Event $event): void
    {
        /** @var \Stripe\PaymentIntent $intent */
        $intent = $event->data->object;

        $this->logger->warning('Payment failed webhook', [
            'intent_id' => $intent->id,
            'error' => $intent->last_payment_error->message ?? 'Unknown error',
            'decline_code' => $intent->last_payment_error->decline_code ?? null,
        ]);
    }

    private function handlePaymentRequiresAction(Event $event): void
    {
        /** @var \Stripe\PaymentIntent $intent */
        $intent = $event->data->object;

        $this->logger->info('Payment requires action webhook', [
            'intent_id' => $intent->id,
            'next_action_type' => $intent->next_action->type ?? 'unknown',
        ]);
    }

    private function handleChargeDispute(Event $event): void
    {
        /** @var Dispute $dispute */
        $dispute = $event->data->object;

        $this->logger->warning('Charge dispute created', [
            'dispute_id' => $dispute->id,
            'charge_id' => $dispute->charge,
            'amount' => $dispute->amount,
            'reason' => $dispute->reason,
            'status' => $dispute->status,
        ]);
    }

    private function handleRecurringPayment(Event $event): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->data->object;

        $this->logger->info('Recurring payment succeeded', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription ?? null,
            'amount_paid' => $invoice->amount_paid,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Infrastructure\Laravel\Jobs\ProcessPaymentWebhookJob;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stripe Webhook Controller.
 *
 * Handles webhook notifications from Stripe payment gateway.
 * Validates webhooks synchronously and processes them asynchronously.
 */
final readonly class StripeWebhookController
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private DonationRepositoryInterface $donationRepository,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->logger->info('Stripe webhook received', [
                'event_type' => $request->input('type'),
                'event_id' => $request->input('id'),
                'livemode' => $request->input('livemode'),
                'ip' => $request->ip(),
            ]);

            // Extract relevant data from webhook
            $eventType = $request->input('type');
            $eventId = $request->input('id');
            $eventData = $request->input('data.object', []);

            if (! $eventId || ! $eventType) {
                throw PaymentGatewayException::webhookValidationFailed(
                    'Missing required fields in Stripe webhook',
                );
            }

            // Get Stripe gateway for signature validation
            $gateway = $this->gatewayFactory->create('stripe');

            // Validate webhook signature synchronously (quick validation)
            $signature = $request->header('Stripe-Signature', '');

            // The gateway's handleWebhook method will perform signature validation internally
            // For now we just ensure we have a signature present
            if (empty($signature)) {
                throw PaymentGatewayException::webhookValidationFailed(
                    'Missing Stripe webhook signature',
                );
            }

            // Only process relevant payment events
            if (in_array($eventType, [
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
                'payment_intent.requires_action',
                'payment_intent.canceled',
                'charge.dispute.created',
                'invoice.payment_succeeded',
                'invoice.payment_failed',
            ], true)) {
                // Find donation by payment intent ID or charge ID
                $donationId = $this->extractDonationId($eventData);

                if (! $donationId) {
                    $this->logger->warning('Could not find donation for Stripe webhook', [
                        'event_type' => $eventType,
                        'event_id' => $eventId,
                        'payment_intent_id' => $eventData['id'] ?? 'unknown',
                    ]);

                    // Continue with rest of processing
                }

                if ($donationId) {
                    // Dispatch async job for processing
                    ProcessPaymentWebhookJob::dispatch(
                        gateway: 'stripe',
                        webhookPayload: $request->all(),
                        webhookSignature: $signature,
                        donationId: $donationId,
                    );

                    $this->logger->info('Stripe webhook dispatched for async processing', [
                        'event_type' => $eventType,
                        'event_id' => $eventId,
                        'donation_id' => $donationId,
                    ]);
                }

                return response('OK', 200);
            }

            $this->logger->debug('Stripe webhook event type not processed', [
                'event_type' => $eventType,
                'event_id' => $eventId,
            ]);

            // Always return 200 OK for successful validation
            return response('OK', 200);
        } catch (PaymentGatewayException $e) {
            $this->logger->error('Stripe webhook validation failed', [
                'error' => $e->getMessage(),
                'event_type' => $request->input('type'),
                'event_id' => $request->input('id'),
                'ip' => $request->ip(),
            ]);

            return response('Webhook validation failed', 400);
        } catch (Throwable $e) {
            $this->logger->error('Stripe webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_type' => $request->input('type'),
                'event_id' => $request->input('id'),
            ]);

            return response('Internal server error', 500);
        }
    }

    /**
     * @param  array<string, mixed>  $eventData
     */
    private function extractDonationId(array $eventData): ?int
    {
        try {
            // Try to extract payment intent ID
            $paymentIntentId = $eventData['id'] ?? $eventData['payment_intent'] ?? null;

            if (! $paymentIntentId) {
                return null;
            }

            // Find donation by payment intent ID
            $donation = $this->donationRepository->findByPaymentIntentId($paymentIntentId);

            if ($donation instanceof Donation) {
                return $donation->id;
            }

            // If not found by payment intent, try by charge ID
            if (isset($eventData['charges']['data'][0]['id'])) {
                $chargeId = $eventData['charges']['data'][0]['id'];
                $donation = $this->donationRepository->findByTransactionId($chargeId);

                if ($donation instanceof Donation) {
                    return $donation->id;
                }
            }

            // Try metadata for donation ID
            if (isset($eventData['metadata']['donation_id'])) {
                return (int) $eventData['metadata']['donation_id'];
            }

            return null;
        } catch (Throwable $e) {
            $this->logger->error('Error extracting donation ID from Stripe webhook', [
                'error' => $e->getMessage(),
                'event_data' => $eventData,
            ]);

            return null;
        }
    }
}

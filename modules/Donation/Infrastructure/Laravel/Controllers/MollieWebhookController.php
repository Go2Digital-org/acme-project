<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Donation\Application\Service\PaymentProcessorService;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Mollie Webhook Controller.
 *
 * Handles webhook notifications from Mollie payment gateway.
 * Processes payment status updates and triggers domain events.
 */
final readonly class MollieWebhookController
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private PaymentProcessorService $paymentProcessor,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->logger->info('Mollie webhook received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
            ]);

            // Get Mollie gateway instance
            $gateway = $this->gatewayFactory->create('mollie');

            // Handle the webhook (signature validation happens inside gateway)
            $gateway->handleWebhook(
                payload: $request->all(),
                signature: $request->header('X-Mollie-Signature', ''),
            );

            // Extract payment ID from webhook
            $paymentId = $request->input('id');

            if (! $paymentId) {
                throw PaymentGatewayException::webhookValidationFailed(
                    'Missing payment ID in Mollie webhook',
                );
            }

            // Process the payment update
            $this->paymentProcessor->processWebhookNotification(
                gatewayName: 'mollie',
                externalId: $paymentId,
                payload: $request->all(),
            );

            $this->logger->info('Mollie webhook processed successfully', [
                'payment_id' => $paymentId,
            ]);

            return response('OK', 200);
        } catch (PaymentGatewayException $e) {
            $this->logger->error('Mollie webhook validation failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response('Webhook validation failed', 400);
        } catch (Throwable $e) {
            $this->logger->error('Mollie webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response('Internal server error', 500);
        }
    }
}

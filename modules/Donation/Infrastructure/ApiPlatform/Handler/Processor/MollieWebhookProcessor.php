<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Modules\Donation\Application\Service\PaymentProcessorService;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Mollie Webhook Processor for API Platform.
 *
 * Handles webhook notifications from Mollie payment gateway.
 * Processes payment status updates and triggers domain events.
 *
 * @implements ProcessorInterface<object, Response>
 */
final readonly class MollieWebhookProcessor implements ProcessorInterface
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private PaymentProcessorService $paymentProcessor,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Response {
        if (! isset($context['request'])) {
            throw new InvalidArgumentException('Request context is required');
        }

        /** @var Request $request */
        $request = $context['request'];

        // Parse JSON payload
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            throw PaymentGatewayException::webhookValidationFailed('Invalid JSON payload');
        }

        try {
            $this->logger->info('Mollie webhook received via API Platform', [
                'payload' => $payload,
                'headers' => $request->headers->all(),
                'ip' => $request->getClientIp(),
            ]);

            // Get Mollie gateway instance
            $gateway = $this->gatewayFactory->create('mollie');

            // Handle the webhook (signature validation happens inside gateway)
            $signature = $request->headers->get('X-Mollie-Signature', '');
            $gateway->handleWebhook(
                payload: $payload,
                signature: $signature ?? '',
            );

            // Extract payment ID from webhook
            $paymentId = $payload['id'] ?? null;

            if (! $paymentId) {
                throw PaymentGatewayException::webhookValidationFailed(
                    'Missing payment ID in Mollie webhook',
                );
            }

            // Process the payment update
            $this->paymentProcessor->processWebhookNotification(
                gatewayName: 'mollie',
                externalId: $paymentId,
                payload: $payload,
            );

            $this->logger->info('Mollie webhook processed successfully via API Platform', [
                'payment_id' => $paymentId,
            ]);

            return new Response('OK', 200);
        } catch (PaymentGatewayException $e) {
            $this->logger->error('Mollie webhook validation failed via API Platform', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return new Response('Webhook validation failed', 400);
        } catch (Throwable $e) {
            $this->logger->error('Mollie webhook processing error via API Platform', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return new Response('Internal server error', 500);
        }
    }
}

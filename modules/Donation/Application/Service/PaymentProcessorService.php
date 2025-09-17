<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Payment Processor Service.
 *
 * Orchestrates payment processing across multiple gateways with comprehensive
 * error handling, retry logic, and audit trail.
 */
final readonly class PaymentProcessorService
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private DonationRepositoryInterface $donationRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Process webhook notification from payment gateway.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhookNotification(
        string $gatewayName,
        string $externalId,
        array $payload,
    ): void {
        try {
            $this->logger->info('Processing webhook notification', [
                'gateway' => $gatewayName,
                'external_id' => $externalId,
                'event_type' => $payload['type'] ?? 'unknown',
            ]);

            // Get payment status from gateway
            $gateway = $this->gatewayFactory->create($gatewayName);
            $transactionResult = $gateway->getTransaction($externalId);

            // Find donation by payment intent ID
            $donation = $this->donationRepository->findByPaymentIntentId($externalId);

            if (! $donation instanceof Donation) {
                $this->logger->warning('Donation not found for payment', [
                    'payment_id' => $externalId,
                    'gateway' => $gatewayName,
                ]);

                return;
            }

            // Update donation status based on payment result
            $paymentStatus = $transactionResult->status;
            if (! $paymentStatus instanceof PaymentStatus) {
                $this->logger->warning('No status in transaction result', [
                    'payment_id' => $externalId,
                    'gateway' => $gatewayName,
                ]);

                return;
            }

            // Map PaymentStatus to DonationStatus
            $donationStatus = match ($paymentStatus) {
                PaymentStatus::PENDING => DonationStatus::PENDING,
                PaymentStatus::PROCESSING => DonationStatus::PROCESSING,
                PaymentStatus::REQUIRES_ACTION => DonationStatus::PROCESSING,
                PaymentStatus::COMPLETED => DonationStatus::COMPLETED,
                PaymentStatus::FAILED => DonationStatus::FAILED,
                PaymentStatus::CANCELLED => DonationStatus::CANCELLED,
                PaymentStatus::REFUNDED => DonationStatus::REFUNDED,
                PaymentStatus::PARTIALLY_REFUNDED => DonationStatus::REFUNDED,
            };

            $previousStatus = $donation->status;
            $donation->status = $donationStatus;

            // Set additional fields based on status
            if ($donationStatus === DonationStatus::COMPLETED) {
                $donation->completed_at = now();
                $donation->payment_gateway = $gatewayName;
                $donation->transaction_id = $externalId;
            }

            if ($donationStatus === DonationStatus::FAILED) {
                $donation->failed_at = now();
                $donation->failure_reason = $transactionResult->getErrorMessage();
            }

            if ($donationStatus === DonationStatus::CANCELLED) {
                $donation->cancelled_at = now();
            }

            $donation->save();

            $this->logger->info('Donation status updated via webhook', [
                'donation_id' => $donation->id,
                'previous_status' => $previousStatus->value,
                'new_status' => $donationStatus->value,
                'payment_id' => $externalId,
                'gateway' => $gatewayName,
            ]);

            $this->logger->info('Webhook processed successfully', [
                'gateway' => $gatewayName,
                'external_id' => $externalId,
                'status' => $transactionResult->isSuccessful() ? 'success' : 'failed',
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Webhook processing failed', [
                'gateway' => $gatewayName,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Application\Command\CompleteDonationCommand;
use Modules\Donation\Application\Command\CompleteDonationCommandHandler;
use Modules\Donation\Application\Command\FailDonationCommand;
use Modules\Donation\Application\Command\FailDonationCommandHandler;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;

final class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        private readonly string $gateway,
        /** @var array<string, mixed> */
        private readonly array $webhookPayload,
        private readonly string $webhookSignature,
        private readonly int $donationId,
    ) {
        $this->onQueue('payments');
    }

    public function handle(
        PaymentGatewayFactory $gatewayFactory,
        DonationRepositoryInterface $donationRepository,
        FailDonationCommandHandler $failDonationHandler,
        CompleteDonationCommandHandler $completeDonationHandler,
    ): void {
        Log::info('Processing payment webhook', [
            'gateway' => $this->gateway,
            'donation_id' => $this->donationId,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            // Verify webhook signature
            $paymentGateway = $gatewayFactory->create($this->gateway);

            if (! $this->verifyWebhookSignature($paymentGateway)) {
                Log::error('Invalid webhook signature', [
                    'gateway' => $this->gateway,
                    'donation_id' => $this->donationId,
                ]);
                $this->fail('Invalid webhook signature');

                return;
            }

            // Get donation
            $donation = $donationRepository->findById($this->donationId);

            if (! $donation instanceof Donation) {
                Log::error('Donation not found for webhook processing', [
                    'donation_id' => $this->donationId,
                ]);
                $this->fail('Donation not found');

                return;
            }

            // Process webhook based on event type
            $eventType = $this->getEventType();

            match ($eventType) {
                'payment_succeeded', 'payment.succeeded', 'payment.confirmed' => $this->handlePaymentSuccess($donation, $completeDonationHandler),
                'payment_failed', 'payment.failed', 'payment.cancelled' => $this->handlePaymentFailure($failDonationHandler),
                'payment_refunded', 'payment.refunded' => $this->handlePaymentRefund($donation),
                default => Log::warning('Unhandled webhook event type', [
                    'event_type' => $eventType,
                    'gateway' => $this->gateway,
                    'donation_id' => $this->donationId,
                ]),
            };

            Log::info('Payment webhook processed successfully', [
                'gateway' => $this->gateway,
                'donation_id' => $this->donationId,
                'event_type' => $eventType,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to process payment webhook', [
                'gateway' => $this->gateway,
                'donation_id' => $this->donationId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Payment webhook job failed permanently', [
            'gateway' => $this->gateway,
            'donation_id' => $this->donationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

    }

    private function verifyWebhookSignature(PaymentGatewayInterface $paymentGateway): bool
    {
        try {
            $paymentGateway->handleWebhook($this->webhookPayload, $this->webhookSignature);

            return true;
        } catch (Exception $exception) {
            Log::error('Error verifying webhook signature', [
                'gateway' => $this->gateway,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function getEventType(): string
    {
        return match ($this->gateway) {
            'stripe' => $this->webhookPayload['type'] ?? 'unknown',
            'mollie' => $this->webhookPayload['type'] ?? 'unknown',
            'paypal' => $this->webhookPayload['event_type'] ?? 'unknown',
            default => 'unknown',
        };
    }

    private function handlePaymentSuccess(
        Donation $donation,
        CompleteDonationCommandHandler $completeDonationHandler,
    ): void {
        $transactionId = $this->extractTransactionId();

        if (! $transactionId) {
            Log::error('No transaction ID found in successful payment webhook', [
                'gateway' => $this->gateway,
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        // Use the injected CompleteDonationCommandHandler
        $command = new CompleteDonationCommand(
            $this->donationId,
            $transactionId,
        );

        $completeDonationHandler->handle($command);

        // Dispatch notification job
        SendPaymentConfirmationJob::dispatch($donation->id)->onQueue('notifications');
    }

    private function handlePaymentFailure(
        FailDonationCommandHandler $handler,
    ): void {
        $failureReason = $this->extractFailureReason();

        $command = new FailDonationCommand(
            $this->donationId,
            $failureReason,
        );

        $handler->handle($command);
    }

    private function handlePaymentRefund(Donation $donation): void
    {
        $refundAmount = $this->extractRefundAmount();

        // Dispatch refund processing job
        RefundProcessingJob::dispatch(
            $donation->id,
            $refundAmount,
            $this->extractFailureReason(),
            $this->webhookPayload,
            true, // webhook initiated
        )->onQueue('payments');
    }

    private function extractTransactionId(): ?string
    {
        return match ($this->gateway) {
            'stripe' => $this->webhookPayload['data']['object']['charges']['data'][0]['id'] ?? null,
            'mollie' => $this->webhookPayload['id'] ?? null,
            'paypal' => $this->webhookPayload['resource']['id'] ?? null,
            default => null,
        };
    }

    private function extractFailureReason(): string
    {
        return match ($this->gateway) {
            'stripe' => $this->webhookPayload['data']['object']['last_payment_error']['message'] ?? 'Payment failed',
            'mollie' => $this->webhookPayload['details']['failureReason'] ?? 'Payment failed',
            'paypal' => $this->webhookPayload['resource']['reason_code'] ?? 'Payment failed',
            default => 'Payment failed',
        };
    }

    private function extractRefundAmount(): float
    {
        return match ($this->gateway) {
            'stripe' => (float) ($this->webhookPayload['data']['object']['amount_refunded'] ?? 0) / 100,
            'mollie' => (float) ($this->webhookPayload['amount']['value'] ?? 0),
            'paypal' => (float) ($this->webhookPayload['resource']['amount']['value'] ?? 0),
            default => 0.0,
        };
    }
}

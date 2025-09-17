<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Modules\Donation\Domain\Model\Donation;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Donation Notification Service.
 *
 * Handles all donation-related notifications and confirmations.
 */
class DonationNotificationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send donation confirmation email.
     */
    public function sendDonationConfirmation(Donation $donation): void
    {
        try {
            $employee = $donation->user;
            $campaign = $donation->campaign;

            if ($employee === null) {
                $this->logger->warning('Cannot send donation confirmation: employee is null', [
                    'donation_id' => $donation->id,
                    'user_id' => $donation->user_id,
                ]);

                return;
            }

            // Log the confirmation
            $this->logger->info('Sending donation confirmation', [
                'donation_id' => $donation->id,
                'user_id' => $donation->user_id,
                'amount' => $donation->amount,
                'campaign' => $campaign->title ?? 'Unknown Campaign',
            ]);

            // TODO: Implement actual email sending

            // For now, just log
            $this->logger->info('Donation confirmation sent', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send donation confirmation', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment failure notification.
     */
    public function sendPaymentFailureNotification(Donation $donation, string $errorMessage): void
    {
        try {
            $employee = $donation->user;

            if ($employee === null) {
                $this->logger->warning('Cannot send payment failure notification: employee is null', [
                    'donation_id' => $donation->id,
                    'user_id' => $donation->user_id,
                    'error' => $errorMessage,
                ]);

                return;
            }

            $this->logger->warning('Sending payment failure notification', [
                'donation_id' => $donation->id,
                'user_id' => $donation->user_id,
                'error' => $errorMessage,
            ]);

            // TODO: Implement actual email sending

            // For now, just log
            $this->logger->info('Payment failure notification sent', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send payment failure notification', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send refund confirmation.
     */
    public function sendRefundConfirmation(Donation $donation, float $amount): void
    {
        try {
            $employee = $donation->user;

            if ($employee === null) {
                $this->logger->warning('Cannot send refund confirmation: employee is null', [
                    'donation_id' => $donation->id,
                    'user_id' => $donation->user_id,
                    'refund_amount' => $amount,
                ]);

                return;
            }

            $this->logger->info('Sending refund confirmation', [
                'donation_id' => $donation->id,
                'user_id' => $donation->user_id,
                'refund_amount' => $amount,
            ]);

            // TODO: Implement actual email sending

            // For now, just log
            $this->logger->info('Refund confirmation sent', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send refund confirmation', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Donation\Application\Event\DonationStatusUpdatedEvent;
use Modules\Donation\Domain\Exception\DonationNotFoundException;
use Modules\Donation\Domain\Exception\InvalidPaymentStatusException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

final readonly class UpdatePaymentStatusCommandHandler
{
    public function __construct(
        private DonationRepositoryInterface $donationRepository
    ) {}

    /**
     * @throws DonationNotFoundException
     * @throws InvalidPaymentStatusException
     * @throws Exception
     */
    public function handle(UpdatePaymentStatusCommand $command): void
    {
        $donation = $this->donationRepository->findById($command->donationId);

        if (! $donation instanceof Donation) {
            throw new DonationNotFoundException(
                "Donation with ID {$command->donationId} not found"
            );
        }

        // Validate status transition
        if (! $this->isValidStatusTransition($donation->status->value, $command->status)) {
            throw new InvalidPaymentStatusException(
                "Cannot change donation status from {$donation->status->value} to {$command->status}"
            );
        }

        DB::transaction(function () use ($donation, $command) {
            $updateData = [
                'status' => $command->status,
                'updated_at' => now(),
            ];

            // Add status-specific fields
            match ($command->status) {
                'processing' => $updateData['processed_at'] = now(),
                'completed' => $updateData['completed_at'] = now(),
                'failed' => $updateData['failed_at'] = now(),
                default => null,
            };

            // Add optional fields if provided
            if ($command->externalTransactionId) {
                $updateData['external_transaction_id'] = $command->externalTransactionId;
            }

            if ($command->gatewayResponse) {
                $updateData['gateway_response'] = $command->gatewayResponse;
            }

            if ($command->failureReason && $command->status === 'failed') {
                $updateData['failure_reason'] = $command->failureReason;
            }

            if ($command->processingFee !== null) {
                $updateData['processing_fee'] = $command->processingFee;
            }

            // Update donation
            $this->donationRepository->updateById($donation->id, $updateData);

            // Dispatch domain event
            Event::dispatch(new DonationStatusUpdatedEvent(
                donation: $donation,
                previousStatus: $donation->status->value,
                newStatus: $command->status,
                updatedAt: now()
            ));
        });
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['processing', 'failed', 'cancelled'],
            'processing' => ['completed', 'failed'],
            'completed' => ['refunded', 'partially_refunded'],
            'failed' => ['pending', 'processing'], // Allow retry
            'cancelled' => [], // Terminal state
            'refunded' => [], // Terminal state
            'partially_refunded' => ['refunded'], // Can be fully refunded
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}

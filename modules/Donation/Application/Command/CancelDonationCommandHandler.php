<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Donation\Application\Event\DonationCancelledEvent;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class CancelDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof CancelDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            $donation = $this->repository->findById($command->donationId);

            if (! $donation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            if (! $donation->canBeCancelled()) {
                throw DonationException::cannotBeCancelled($donation);
            }

            // Validate permissions if user is provided
            if ($command->userId !== null && $donation->user_id !== $command->userId) {
                throw DonationException::unauthorizedAccess($donation);
            }

            // Cancel the donation using domain logic
            $donation->cancel($command->reason);

            // Refresh model
            $cancelledDonation = $this->repository->findById($command->donationId);

            if (! $cancelledDonation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Dispatch domain event
            event(new DonationCancelledEvent(
                donationId: $donation->id,
                campaignId: $donation->campaign_id,
                userId: $donation->user_id,
                amount: $donation->amount,
                currency: $donation->currency,
                cancelledByEmployeeId: $command->userId ?? $donation->user_id ?? 1, // Default to system if no user
            ));

            return $cancelledDonation;
        });
    }
}

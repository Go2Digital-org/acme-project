<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Donation\Application\Event\DonationFailedEvent;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class FailDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof FailDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            $donation = $this->repository->findById($command->donationId);

            if (! $donation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Fail the donation using domain logic
            $donation->fail($command->failureReason);

            // Refresh model
            $failedDonation = $this->repository->findById($command->donationId);

            if (! $failedDonation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Dispatch domain event
            event(new DonationFailedEvent(
                donationId: $donation->id,
                campaignId: $donation->campaign_id,
                userId: $donation->user_id,
                amount: $donation->amount,
                currency: $donation->currency,
                failureReason: $command->failureReason,
            ));

            return $failedDonation;
        });
    }
}

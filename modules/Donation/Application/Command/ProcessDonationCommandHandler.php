<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Donation\Application\Event\DonationProcessingEvent;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class ProcessDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof ProcessDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            $donation = $this->repository->findById($command->donationId);

            if (! $donation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            if (! $donation->canBeProcessed()) {
                throw DonationException::cannotProcess($donation);
            }

            // Process the donation using domain logic
            $donation->process($command->transactionId);

            // Update gateway response if provided
            if ($command->gatewayResponseId !== null) {
                $this->repository->updateById($command->donationId, [
                    'gateway_response_id' => $command->gatewayResponseId,
                ]);
            }

            // Refresh model
            $processedDonation = $this->repository->findById($command->donationId);

            if (! $processedDonation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Dispatch domain event
            event(new DonationProcessingEvent(
                donationId: $donation->id,
                campaignId: $donation->campaign_id,
                userId: $donation->user_id,
                amount: $donation->amount,
                currency: $donation->currency,
                transactionId: $command->transactionId,
            ));

            return $processedDonation;
        });
    }
}

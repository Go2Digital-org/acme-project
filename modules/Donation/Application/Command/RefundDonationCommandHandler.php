<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Event\DonationRefundedEvent;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class RefundDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof RefundDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            $donation = $this->donationRepository->findById($command->donationId);

            if (! $donation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            if (! $donation->canBeRefunded()) {
                throw DonationException::cannotBeRefunded($donation);
            }

            // Refund the donation using domain logic
            $donation->refund($command->refundReason);

            // Update campaign by removing donation amount
            $campaign = $this->campaignRepository->findById($donation->campaign_id);

            if ($campaign instanceof Campaign) {
                // Subtract the donation amount from campaign
                $campaign->current_amount -= $donation->amount;
                $campaign->save();
            }

            // Refresh model
            $refundedDonation = $this->donationRepository->findById($command->donationId);

            if (! $refundedDonation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Dispatch domain event
            event(new DonationRefundedEvent(
                donationId: $donation->id,
                campaignId: $donation->campaign_id,
                userId: $donation->user_id,
                amount: $donation->amount,
                currency: $donation->currency,
                refundReason: $command->refundReason,
                processedByEmployeeId: $command->processedByEmployeeId,
            ));

            return $refundedDonation;
        });
    }
}

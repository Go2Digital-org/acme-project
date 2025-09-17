<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\Shared\Domain\ValueObject\Money;

class CompleteDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof CompleteDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            $donation = $this->donationRepository->findById($command->donationId);

            if (! $donation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            if (! $donation->canBeProcessed()) {
                throw DonationException::cannotBeCompleted($donation);
            }

            // Complete the donation using domain logic
            $donation->complete();

            // Update gateway response if provided
            if ($command->gatewayResponseId !== null) {
                $this->donationRepository->updateById($command->donationId, [
                    'gateway_response_id' => $command->gatewayResponseId,
                    'completed_at' => now(),
                ]);
            }

            // Update campaign with donation amount
            $campaign = $this->campaignRepository->findById($donation->campaign_id);

            if ($campaign instanceof Campaign) {
                $campaign->addDonation(new Money($donation->amount, $donation->currency));
                $campaign->save();
            }

            // Refresh donation model
            $completedDonation = $this->donationRepository->findById($command->donationId);

            if (! $completedDonation instanceof Donation) {
                throw DonationException::notFound($command->donationId);
            }

            // Dispatch domain event
            event(new DonationCompletedEvent(
                donationId: $donation->id,
                campaignId: $donation->campaign_id,
                userId: $donation->user_id,
                amount: $donation->amount,
                currency: $donation->currency,
            ));

            return $completedDonation;
        });
    }
}

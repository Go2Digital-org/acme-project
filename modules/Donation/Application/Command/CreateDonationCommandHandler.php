<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Event\DonationCreatedEvent;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\Shared\Domain\ValueObject\DonationStatus;

class CreateDonationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function handle(CommandInterface $command): Donation
    {
        if (! $command instanceof CreateDonationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Donation {
            // Validate campaign exists and can accept donations
            $campaign = $this->campaignRepository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            if (! $campaign->canAcceptDonation()) {
                throw CampaignException::cannotAcceptDonation($campaign);
            }

            // Create donation
            $donation = $this->donationRepository->create([
                'campaign_id' => $command->campaignId,
                'user_id' => $command->userId,
                'amount' => $command->amount,
                'currency' => $command->currency,
                'payment_method' => $command->paymentMethod,
                'payment_gateway' => $command->paymentGateway,
                'status' => DonationStatus::PENDING,
                'anonymous' => $command->anonymous,
                'recurring' => $command->recurring,
                'recurring_frequency' => $command->recurringFrequency,
                'notes' => $command->notes,
                'donated_at' => now(),
            ]);

            // Dispatch domain event
            event(new DonationCreatedEvent(
                donationId: $donation->id,
                campaignId: $command->campaignId,
                userId: $command->userId,
                amount: $command->amount,
                currency: $command->currency,
                anonymous: $command->anonymous,
            ));

            return $donation;
        });
    }
}

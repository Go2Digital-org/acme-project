<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class RejectCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof RejectCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Campaign {
            $campaign = $this->repository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate rejecter permissions (must be super_admin)
            $rejecter = User::find($command->rejecterId);
            if (! $rejecter || ! $rejecter->hasRole('super_admin')) {
                throw CampaignException::unauthorizedAccess($campaign);
            }

            $previousStatus = $campaign->status;

            // Validate campaign is in pending approval status
            if ($campaign->status !== CampaignStatus::PENDING_APPROVAL) {
                throw CampaignException::invalidStatusTransition(
                    $campaign->status,
                    CampaignStatus::REJECTED
                );
            }

            // Reject campaign
            $this->repository->updateById($command->campaignId, [
                'status' => CampaignStatus::REJECTED->value,
                'rejected_by' => $command->rejecterId,
                'rejected_at' => now(),
                'rejection_reason' => $command->rejectionReason,
                // Clear any approval data
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // Reload campaign with updated data
            $updatedCampaign = $this->repository->findById($command->campaignId);

            if (! $updatedCampaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Dispatch domain events
            Event::dispatch(new CampaignStatusChangedEvent(
                campaign: $updatedCampaign,
                previousStatus: $previousStatus,
                newStatus: CampaignStatus::REJECTED,
                changedByUserId: $command->rejecterId,
                reason: $command->rejectionReason
            ));

            Event::dispatch(new CampaignRejectedEvent(
                campaign: $updatedCampaign,
                rejectedByUserId: $command->rejecterId,
                reason: $command->rejectionReason
            ));

            return $updatedCampaign;
        });
    }
}

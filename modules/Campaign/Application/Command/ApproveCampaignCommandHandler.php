<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class ApproveCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof ApproveCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Campaign {
            $campaign = $this->repository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate approver permissions (must be super_admin)
            $approver = User::find($command->approverId);
            if (! $approver || ! $approver->hasRole('super_admin')) {
                throw CampaignException::unauthorizedAccess($campaign);
            }

            $previousStatus = $campaign->status;

            // Validate campaign is in pending approval status
            if ($campaign->status !== CampaignStatus::PENDING_APPROVAL) {
                throw CampaignException::invalidStatusTransition(
                    $campaign->status,
                    CampaignStatus::ACTIVE
                );
            }

            // Approve campaign and set to active
            $this->repository->updateById($command->campaignId, [
                'status' => CampaignStatus::ACTIVE->value,
                'approved_by' => $command->approverId,
                'approved_at' => now(),
                // Clear any rejection data if it was previously rejected
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
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
                newStatus: CampaignStatus::ACTIVE,
                changedByUserId: $command->approverId
            ));

            Event::dispatch(new CampaignApprovedEvent(
                campaign: $updatedCampaign,
                approvedByUserId: $command->approverId,
                notes: $command->notes ?? null
            ));

            return $updatedCampaign;
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Event\CampaignSubmittedForApprovalEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class SubmitForApprovalCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof SubmitForApprovalCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Campaign {
            $campaign = $this->repository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate employee permissions
            if ($campaign->user_id !== $command->employeeId) {
                throw CampaignException::unauthorizedAccess($campaign);
            }

            $previousStatus = $campaign->status;

            // Validate campaign can be submitted for approval
            if ($campaign->status !== CampaignStatus::DRAFT && $campaign->status !== CampaignStatus::REJECTED) {
                throw CampaignException::invalidStatusTransition(
                    $campaign->status,
                    CampaignStatus::PENDING_APPROVAL
                );
            }

            // Validate business rules before submission
            $campaign->validateDateRange();
            $campaign->validateGoalAmount();

            // Submit for approval
            $this->repository->updateById($command->campaignId, [
                'status' => CampaignStatus::PENDING_APPROVAL->value,
                'submitted_for_approval_at' => now(),
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
                newStatus: CampaignStatus::PENDING_APPROVAL,
                changedByUserId: $command->employeeId
            ));

            Event::dispatch(new CampaignSubmittedForApprovalEvent(
                campaign: $updatedCampaign,
                submitterId: $command->employeeId
            ));

            return $updatedCampaign;
        });
    }
}

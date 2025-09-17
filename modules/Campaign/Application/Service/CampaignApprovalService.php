<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Modules\Campaign\Application\Command\ApproveCampaignCommand;
use Modules\Campaign\Application\Command\ApproveCampaignCommandHandler;
use Modules\Campaign\Application\Command\RejectCampaignCommand;
use Modules\Campaign\Application\Command\RejectCampaignCommandHandler;
use Modules\Campaign\Application\Command\SubmitForApprovalCommand;
use Modules\Campaign\Application\Command\SubmitForApprovalCommandHandler;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Specification\CampaignApprovalSpecification;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\User\Domain\Specification\UserPermissionSpecification;
use Modules\User\Infrastructure\Laravel\Models\User;

class CampaignApprovalService
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
        private readonly SubmitForApprovalCommandHandler $submitHandler,
        private readonly ApproveCampaignCommandHandler $approveHandler,
        private readonly RejectCampaignCommandHandler $rejectHandler,
    ) {}

    /**
     * Submit a campaign for approval
     */
    public function submitForApproval(Campaign $campaign, User $submitter): Campaign
    {
        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $submitter->id,
        );

        return $this->submitHandler->handle($command);
    }

    /**
     * Approve a campaign
     */
    public function approve(Campaign $campaign, User $approver): Campaign
    {
        $command = new ApproveCampaignCommand(
            campaignId: $campaign->id,
            approverId: $approver->id,
        );

        return $this->approveHandler->handle($command);
    }

    /**
     * Reject a campaign
     */
    public function reject(Campaign $campaign, User $rejecter, string $reason): Campaign
    {
        $command = new RejectCampaignCommand(
            campaignId: $campaign->id,
            rejecterId: $rejecter->id,
            rejectionReason: $reason,
        );

        return $this->rejectHandler->handle($command);
    }

    /**
     * Check if a user can approve campaigns using specification pattern
     */
    public function canApproveCampaigns(User $user): bool
    {
        $approvalSpec = UserPermissionSpecification::canApproveCampaign();

        return $approvalSpec->isSatisfiedBy($user);
    }

    /**
     * Check if a campaign can be submitted for approval
     */
    public function canSubmitForApproval(Campaign $campaign): bool
    {
        return $campaign->status === CampaignStatus::DRAFT ||
               $campaign->status === CampaignStatus::REJECTED;
    }

    /**
     * Check if a campaign can be approved using specification pattern
     */
    public function canApprove(Campaign $campaign): bool
    {
        $campaignApprovalSpec = new CampaignApprovalSpecification;

        return $campaignApprovalSpec->isSatisfiedBy($campaign);
    }

    /**
     * Check if a campaign can be rejected
     * Note: For now, keep simple logic since rejection doesn't have complex business rules
     */
    public function canReject(Campaign $campaign): bool
    {
        return $campaign->status === CampaignStatus::PENDING_APPROVAL;
    }

    /**
     * Check if a user can approve a specific campaign (combines user and campaign specs)
     */
    public function canUserApproveCampaign(User $user, Campaign $campaign): bool
    {
        $userSpec = UserPermissionSpecification::canApproveCampaign();
        $campaignSpec = new CampaignApprovalSpecification;

        $userSpec->and($campaignSpec);

        // We need to check both the user and campaign separately since specifications
        // are designed for single object evaluation
        return $userSpec->isSatisfiedBy($user) && $campaignSpec->isSatisfiedBy($campaign);
    }

    /**
     * Get campaigns pending approval
     *
     * @return array<Campaign>
     */
    public function getPendingApprovalCampaigns(): array
    {
        return $this->repository->findByStatus(CampaignStatus::PENDING_APPROVAL);
    }

    /**
     * Get rejected campaigns for a user
     *
     * @return array<Campaign>
     */
    public function getRejectedCampaigns(User $user): array
    {
        return $this->repository->findByEmployeeAndStatus($user->id, CampaignStatus::REJECTED);
    }
}

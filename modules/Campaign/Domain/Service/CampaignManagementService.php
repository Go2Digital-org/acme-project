<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use DomainException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Specification\CampaignApprovalSpecification;
use Modules\Campaign\Domain\Specification\EligibleForDonationSpecification;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Domain\ValueObject\Money;
use Psr\Log\LoggerInterface;

/**
 * Domain service for campaign management operations.
 *
 * Uses specifications to encapsulate business rules and ensure
 * consistent validation across different campaign operations.
 */
class CampaignManagementService
{
    public function __construct(
        private readonly EligibleForDonationSpecification $donationEligibilitySpec,
        private readonly CampaignApprovalSpecification $approvalSpec,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Process a donation to a campaign.
     *
     * Uses the EligibleForDonationSpecification to ensure the campaign
     * can accept donations before processing.
     */
    public function processDonation(Campaign $campaign, Money $amount): void
    {
        $this->logger->info('Processing donation for campaign', [
            'campaign_id' => $campaign->id,
            'amount' => $amount->amount,
            'currency' => $amount->currency,
        ]);

        // Use specification to check if campaign can accept donations
        if (! $this->donationEligibilitySpec->isSatisfiedBy($campaign)) {
            $this->logger->warning('Campaign donation rejected - not eligible', [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status->value,
                'end_date' => $campaign->end_date?->toIso8601String(),
                'current_amount' => $campaign->current_amount,
                'goal_amount' => $campaign->goal_amount,
            ]);

            throw new DomainException('Campaign is not eligible to receive donations');
        }

        // Process the donation using the domain model logic
        $campaign->addDonation($amount);

        $this->logger->info('Donation processed successfully', [
            'campaign_id' => $campaign->id,
            'new_amount' => $campaign->current_amount,
            'progress_percentage' => $campaign->getProgressPercentage(),
        ]);
    }

    /**
     * Approve a campaign for publication.
     *
     * Uses the CampaignApprovalSpecification to ensure the campaign
     * meets all requirements for approval.
     */
    public function approveCampaign(Campaign $campaign, int $approvedByUserId): void
    {
        $this->logger->info('Approving campaign', [
            'campaign_id' => $campaign->id,
            'approved_by' => $approvedByUserId,
        ]);

        // Use specification to check if campaign can be approved
        if (! $this->approvalSpec->isSatisfiedBy($campaign)) {
            $this->logger->warning('Campaign approval rejected - requirements not met', [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status->value,
                'title' => $campaign->getTitle(),
                'has_description' => ! in_array($campaign->getDescription(), [null, '', '0'], true),
                'goal_amount' => $campaign->goal_amount,
                'organization_id' => $campaign->organization_id,
                'user_id' => $campaign->user_id,
            ]);

            throw new DomainException('Campaign does not meet approval requirements');
        }

        // Update campaign status to active
        $campaign->status = CampaignStatus::ACTIVE;
        $campaign->approved_by = $approvedByUserId;
        $campaign->approved_at = now();

        $this->logger->info('Campaign approved successfully', [
            'campaign_id' => $campaign->id,
            'approved_by' => $approvedByUserId,
            'approved_at' => $campaign->approved_at->toIso8601String(),
        ]);
    }

    /**
     * Reject a campaign with a reason.
     */
    public function rejectCampaign(Campaign $campaign, int $rejectedByUserId, string $reason): void
    {
        $this->logger->info('Rejecting campaign', [
            'campaign_id' => $campaign->id,
            'rejected_by' => $rejectedByUserId,
            'reason' => $reason,
        ]);

        // Campaign must be pending approval to be rejected
        if ($campaign->status !== CampaignStatus::PENDING_APPROVAL) {
            throw new DomainException('Only campaigns pending approval can be rejected');
        }

        // Update campaign status to rejected
        $campaign->status = CampaignStatus::REJECTED;
        $campaign->rejected_by = $rejectedByUserId;
        $campaign->rejected_at = now();
        $campaign->rejection_reason = $reason;

        $this->logger->info('Campaign rejected successfully', [
            'campaign_id' => $campaign->id,
            'rejected_by' => $rejectedByUserId,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if multiple campaigns are eligible for bulk operations.
     *
     * @param  Campaign[]  $campaigns
     * @return array{eligible: Campaign[], ineligible: Campaign[]}
     */
    public function checkDonationEligibility(array $campaigns): array
    {
        $eligible = [];
        $ineligible = [];

        foreach ($campaigns as $campaign) {
            if ($this->donationEligibilitySpec->isSatisfiedBy($campaign)) {
                $eligible[] = $campaign;

                continue;
            }

            $ineligible[] = $campaign;
        }

        $this->logger->debug('Checked campaign donation eligibility', [
            'total_campaigns' => count($campaigns),
            'eligible_count' => count($eligible),
            'ineligible_count' => count($ineligible),
        ]);

        return [
            'eligible' => $eligible,
            'ineligible' => $ineligible,
        ];
    }

    /**
     * Check if multiple campaigns meet approval requirements.
     *
     * @param  Campaign[]  $campaigns
     * @return array{approvable: Campaign[], not_approvable: Campaign[]}
     */
    public function checkApprovalEligibility(array $campaigns): array
    {
        $approvable = [];
        $notApprovable = [];

        foreach ($campaigns as $campaign) {
            if ($this->approvalSpec->isSatisfiedBy($campaign)) {
                $approvable[] = $campaign;

                continue;
            }

            $notApprovable[] = $campaign;
        }

        $this->logger->debug('Checked campaign approval eligibility', [
            'total_campaigns' => count($campaigns),
            'approvable_count' => count($approvable),
            'not_approvable_count' => count($notApprovable),
        ]);

        return [
            'approvable' => $approvable,
            'not_approvable' => $notApprovable,
        ];
    }
}

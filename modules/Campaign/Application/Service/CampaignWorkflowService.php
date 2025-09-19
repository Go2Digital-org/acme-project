<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Illuminate\Support\Facades\DB;
use Modules\Campaign\Application\Command\ActivateCampaignCommand;
use Modules\Campaign\Application\Command\ActivateCampaignCommandHandler;
use Modules\Campaign\Application\Command\CompleteCampaignCommand;
use Modules\Campaign\Application\Command\CompleteCampaignCommandHandler;
use Modules\Campaign\Application\Command\CreateCampaignCommand;
use Modules\Campaign\Application\Command\CreateCampaignCommandHandler;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;

final readonly class CampaignWorkflowService
{
    public function __construct(
        private CreateCampaignCommandHandler $createCampaignHandler,
        private ActivateCampaignCommandHandler $activateCampaignHandler,
        private CompleteCampaignCommandHandler $completeCampaignHandler,
        private CampaignRepositoryInterface $campaignRepository,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {}

    /**
     * Complete workflow: Create and activate a campaign in one operation.
     */
    public function createAndActivateCampaign(
        string $title,
        string $description,
        float $goalAmount,
        string $startDate,
        string $endDate,
        int $organizationId,
        int $userId,
    ): Campaign {
        return DB::transaction(function () use (
            $title,
            $description,
            $goalAmount,
            $startDate,
            $endDate,
            $organizationId,
            $userId
        ): Campaign {
            // Validate organization can create campaigns
            $organization = $this->organizationRepository->findById($organizationId);

            if (! $organization instanceof Organization || ! $organization->canCreateCampaigns()) {
                throw CampaignException::organizationCannotCreateCampaigns();
            }

            // Create campaign
            $campaign = $this->createCampaignHandler->handle(
                new CreateCampaignCommand(
                    title: ['en' => $title],
                    description: ['en' => $description],
                    goalAmount: $goalAmount,
                    startDate: $startDate,
                    endDate: $endDate,
                    organizationId: $organizationId,
                    userId: $userId,
                ),
            );

            // Activate immediately
            $this->activateCampaignHandler->handle(
                new ActivateCampaignCommand(
                    campaignId: $campaign->id,
                    userId: $userId,
                ),
            );

            // Return refreshed campaign with activated status
            $refreshedCampaign = $this->campaignRepository->findById($campaign->id);

            if (! $refreshedCampaign instanceof Campaign) {
                throw CampaignException::campaignNotFound($campaign->id);
            }

            return $refreshedCampaign;
        });
    }

    /**
     * Process expired campaigns and mark them as completed.
     */
    public function processExpiredCampaigns(): int
    {
        $expiredCampaigns = $this->campaignRepository->findExpiredCampaigns();
        $processedCount = 0;

        foreach ($expiredCampaigns as $campaign) {
            if ($campaign->status === CampaignStatus::ACTIVE) {
                $this->completeCampaignHandler->handle(
                    new CompleteCampaignCommand(
                        campaignId: $campaign->id,
                        userId: $campaign->user_id,
                    ),
                );
                $processedCount++;
            }
        }

        return $processedCount;
    }

    /**
     * Get campaign statistics for an organization.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationCampaignStats(int $organizationId): array
    {
        $campaigns = $this->campaignRepository->findActiveByOrganization($organizationId);

        $stats = [
            'total_campaigns' => count($campaigns),
            'active_campaigns' => 0,
            'completed_campaigns' => 0,
            'draft_campaigns' => 0,
            'total_raised' => 0.0,
            'total_goal' => 0.0,
            'average_progress' => 0.0,
        ];

        $totalProgress = 0.0;

        foreach ($campaigns as $campaign) {
            $stats['total_raised'] += $campaign->current_amount;
            $stats['total_goal'] += $campaign->goal_amount;
            $totalProgress += $campaign->getProgressPercentage();

            match ($campaign->status) {
                CampaignStatus::ACTIVE => $stats['active_campaigns']++,
                CampaignStatus::PAUSED => $stats['active_campaigns']++, // Count paused as active
                CampaignStatus::COMPLETED => $stats['completed_campaigns']++,
                CampaignStatus::DRAFT => $stats['draft_campaigns']++,
                CampaignStatus::CANCELLED => null, // Don't count cancelled campaigns in active stats
                CampaignStatus::EXPIRED => $stats['completed_campaigns']++, // Count expired as completed
                CampaignStatus::PENDING_APPROVAL => $stats['draft_campaigns']++, // Count as draft until approved
                CampaignStatus::REJECTED => $stats['draft_campaigns']++, // Count as draft when rejected
            };
        }

        if (count($campaigns) > 0) {
            $stats['average_progress'] = $totalProgress / count($campaigns);
        }

        return $stats;
    }
}

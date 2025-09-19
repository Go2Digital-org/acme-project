<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignProgress;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Domain\ValueObject\TimeRemaining;

class CampaignStatusService
{
    public function determineDisplayStatus(Campaign $campaign): CampaignStatus
    {
        $timeRemaining = TimeRemaining::fromCampaign($campaign);

        // If explicitly expired by time, override status
        if ($timeRemaining->isExpired() && $campaign->status->isActive()) {
            return CampaignStatus::fromString('expired');
        }

        return $campaign->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatusMetrics(Campaign $campaign): array
    {
        $progress = CampaignProgress::fromCampaign($campaign);
        $timeRemaining = TimeRemaining::fromCampaign($campaign);
        $status = $this->determineDisplayStatus($campaign);

        return [
            'status' => $status,
            'progress' => $progress,
            'timeRemaining' => $timeRemaining,
            'isUrgent' => $this->isUrgent($campaign),
            'displayPriority' => $this->getDisplayPriority($status, $timeRemaining),
        ];
    }

    public function isUrgent(Campaign $campaign): bool
    {
        if (! $campaign->status->isActive()) {
            return false;
        }

        $timeRemaining = TimeRemaining::fromCampaign($campaign);

        if ($timeRemaining->isExpiringSoon(3)) {
            return true;
        }

        return $timeRemaining->getUrgencyLevel() === 'critical';
    }

    public function getDisplayPriority(CampaignStatus $status, TimeRemaining $timeRemaining): int
    {
        // Higher number = higher priority for sorting
        return match ($status->value) {
            'active' => match ($timeRemaining->getUrgencyLevel()) {
                'critical' => 100,
                'urgent' => 90,
                'warning' => 80,
                'normal' => 70,
                default => 60,
            },
            'paused' => 40,
            'completed' => 50,
            'expired' => 30,
            'cancelled' => 20,
            'draft' => 10,
            'pending_approval' => 5,
            'rejected' => 1,
        };
    }

    public function canTransitionTo(Campaign $campaign, string $targetStatus): bool
    {
        $currentStatus = $campaign->status->value;

        $transitions = [
            'draft' => ['pending_approval', 'cancelled'],
            'pending_approval' => ['active', 'rejected'],
            'rejected' => ['draft', 'pending_approval', 'cancelled'],
            'active' => ['paused', 'completed', 'cancelled', 'expired'],
            'paused' => ['active', 'cancelled', 'expired'],
            'completed' => [], // Final state
            'cancelled' => [], // Final state
            'expired' => [], // Final state
        ];

        return in_array($targetStatus, $transitions[$currentStatus], true);
    }

    /**
     * @return array<string, string>
     */
    public function getValidTransitions(Campaign $campaign): array
    {
        $currentStatus = $campaign->status->value;
        $timeRemaining = TimeRemaining::fromCampaign($campaign);

        $baseTransitions = [
            'draft' => [
                'pending_approval' => __('campaigns.submit_for_approval'),
                'cancelled' => __('campaigns.cancel_campaign'),
            ],
            'pending_approval' => [
                'active' => __('campaigns.approve_campaign'),
                'rejected' => __('campaigns.reject_campaign'),
            ],
            'rejected' => [
                'draft' => __('campaigns.return_to_draft'),
                'pending_approval' => __('campaigns.resubmit_for_approval'),
                'cancelled' => __('campaigns.cancel_campaign'),
            ],
            'active' => [
                'paused' => __('campaigns.pause_campaign'),
                'completed' => __('campaigns.mark_as_completed'),
                'cancelled' => __('campaigns.cancel_campaign'),
                'expired' => __('campaigns.mark_as_expired'),
            ],
            'paused' => [
                'active' => __('campaigns.resume_campaign'),
                'cancelled' => __('campaigns.cancel_campaign'),
                'expired' => __('campaigns.mark_as_expired'),
            ],
            'completed' => [],
            'cancelled' => [],
            'expired' => [],
        ];

        $transitions = $baseTransitions[$currentStatus];

        // Remove invalid transitions based on business rules
        if ($currentStatus === CampaignStatus::ACTIVE->value && $timeRemaining->isExpired()) {
            // If expired, only allow completion or cancellation
            // No paused status exists, so no need to remove it
        }

        return $transitions;
    }

    public function shouldShowInList(Campaign $campaign, string $filter = 'all'): bool
    {
        $status = $this->determineDisplayStatus($campaign);
        $timeRemaining = TimeRemaining::fromCampaign($campaign);

        return match ($filter) {
            'active' => $status->isActive() && ! $timeRemaining->isExpired(),
            'urgent' => $this->isUrgent($campaign),
            'completed' => $status->value === CampaignStatus::COMPLETED->value,
            'expired' => $timeRemaining->isExpired() || $status->value === CampaignStatus::EXPIRED->value,
            'draft' => $status->value === CampaignStatus::DRAFT->value,
            'pending_approval' => $status->value === CampaignStatus::PENDING_APPROVAL->value,
            'rejected' => $status->value === CampaignStatus::REJECTED->value,
            'all' => true,
            default => true,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getListingData(Campaign $campaign): array
    {
        $metrics = $this->getStatusMetrics($campaign);

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'slug' => $campaign->slug,
            'status' => $metrics['status'],
            'progress' => $metrics['progress'],
            'timeRemaining' => $metrics['timeRemaining'],
            'isUrgent' => $metrics['isUrgent'],
            'priority' => $metrics['displayPriority'],
            'organization' => $campaign->organization ? $campaign->organization->getName() : null,
            'creator' => $campaign->creator->name ?? null,
        ];
    }
}

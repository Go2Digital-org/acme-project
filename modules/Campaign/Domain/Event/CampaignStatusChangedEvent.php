<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Event\AbstractDomainEvent;

class CampaignStatusChangedEvent extends AbstractDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly CampaignStatus $previousStatus,
        public readonly CampaignStatus $newStatus,
        public readonly int $changedByUserId,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($campaign->id);
    }

    public function isStatusChange(CampaignStatus $from, CampaignStatus $to): bool
    {
        return $this->previousStatus === $from && $this->newStatus === $to;
    }

    public function isSubmissionForApproval(): bool
    {
        if ($this->isStatusChange(CampaignStatus::DRAFT, CampaignStatus::PENDING_APPROVAL)) {
            return true;
        }

        return $this->isStatusChange(CampaignStatus::REJECTED, CampaignStatus::PENDING_APPROVAL);
    }

    public function isApproval(): bool
    {
        return $this->newStatus === CampaignStatus::ACTIVE;
    }

    public function isRejection(): bool
    {
        return $this->newStatus === CampaignStatus::REJECTED;
    }

    public function isActivation(): bool
    {
        return $this->newStatus === CampaignStatus::ACTIVE;
    }

    public function isCompletion(): bool
    {
        return $this->newStatus === CampaignStatus::COMPLETED;
    }

    public function getEventName(): string
    {
        return 'campaign.status_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'campaign_id' => $this->campaign->id,
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
            'changed_by_user_id' => $this->changedByUserId,
            'reason' => $this->reason,
            'campaign_title' => $this->campaign->title,
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}

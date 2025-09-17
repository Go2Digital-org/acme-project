<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Application\Event\AbstractDomainEvent;

class CampaignSubmittedForApprovalEvent extends AbstractDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly int $submitterId,
    ) {
        parent::__construct($campaign->id);
    }

    public function getEventName(): string
    {
        return 'campaign.submitted_for_approval';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'campaign_id' => $this->campaign->id,
            'submitter_id' => $this->submitterId,
            'campaign_title' => $this->campaign->title,
            'organization_id' => $this->campaign->organization_id,
            'goal_amount' => $this->campaign->goal_amount,
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}

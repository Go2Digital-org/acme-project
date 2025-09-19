<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Application\Event\AbstractDomainEvent;

class CampaignRejectedEvent extends AbstractDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly int $rejectedByUserId,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($campaign->id);
    }

    public function getEventName(): string
    {
        return 'campaign.rejected';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'campaign_id' => $this->campaign->id,
            'rejected_by_user_id' => $this->rejectedByUserId,
            'reason' => $this->reason,
            'campaign_title' => $this->campaign->title,
            'organization_id' => $this->campaign->organization_id,
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}

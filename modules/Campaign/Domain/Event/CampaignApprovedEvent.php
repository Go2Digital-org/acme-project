<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Event;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Application\Event\AbstractDomainEvent;

class CampaignApprovedEvent extends AbstractDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly int $approvedByUserId,
        public readonly ?string $notes = null,
    ) {
        parent::__construct($campaign->id);
    }

    public function getEventName(): string
    {
        return 'campaign.approved';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'campaign_id' => $this->campaign->id,
            'approved_by_user_id' => $this->approvedByUserId,
            'notes' => $this->notes,
            'campaign_title' => $this->campaign->title,
            'organization_id' => $this->campaign->organization_id,
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}

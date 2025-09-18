<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

class CampaignCompletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $campaignId,
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly float $totalRaised,
        public readonly float $goalAmount,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'campaign.completed';
    }
}

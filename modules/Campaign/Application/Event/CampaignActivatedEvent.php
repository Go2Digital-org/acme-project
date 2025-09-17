<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

final class CampaignActivatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $campaignId,
        public readonly int $employeeId,
        public readonly int $organizationId,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'campaign.activated';
    }
}

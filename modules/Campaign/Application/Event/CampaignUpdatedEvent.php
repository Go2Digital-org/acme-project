<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;
use Modules\Shared\Domain\Event\CampaignEventInterface;

final class CampaignUpdatedEvent extends AbstractDomainEvent implements CampaignEventInterface
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
        return 'campaign.updated';
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getEntityType(): string
    {
        return 'campaign';
    }

    public function getEntityId(): int
    {
        return $this->campaignId;
    }

    /** @return array<string, mixed> */
    public function getEventData(): array
    {
        return [
            'campaignId' => $this->campaignId,
            'employeeId' => $this->employeeId,
            'organizationId' => $this->organizationId,
        ];
    }
}

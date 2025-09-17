<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Event;

/**
 * Contract for campaign-related events.
 */
interface CampaignEventInterface extends DomainEventInterface
{
    public function getCampaignId(): int;

    public function getEntityType(): string;

    public function getEntityId(): int;
}

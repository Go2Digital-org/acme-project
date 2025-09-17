<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Event;

/**
 * Contract for donation-related events.
 */
interface DonationEventInterface extends DomainEventInterface
{
    public function getDonationId(): int;

    public function getCampaignId(): int;

    public function getAmount(): float;

    public function getEntityType(): string;

    public function getEntityId(): int;
}

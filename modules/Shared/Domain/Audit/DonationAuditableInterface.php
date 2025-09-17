<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Audit;

/**
 * Contract for donation entities that can be audited.
 * Used by the audit system for donation-specific metadata.
 */
interface DonationAuditableInterface extends AuditableEntityInterface
{
    public function getDonationAmount(): float;

    public function getDonationStatus(): string;

    public function getDonationCampaignId(): int;

    public function getDonationEmployeeId(): ?int;

    public function isDonationAnonymous(): bool;
}

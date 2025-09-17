<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Audit;

/**
 * Contract for campaign entities that can be audited.
 * Used by the audit system for campaign-specific metadata.
 */
interface CampaignAuditableInterface extends AuditableEntityInterface
{
    public function getCampaignTitle(): string;

    public function getCampaignStatus(): string;

    public function getCampaignOrganizationId(): int;
}

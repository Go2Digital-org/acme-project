<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Audit;

/**
 * Contract for organization entities that can be audited.
 * Used by the audit system for organization-specific metadata.
 */
interface OrganizationAuditableInterface extends AuditableEntityInterface
{
    public function getOrganizationName(): string;

    public function getOrganizationCategory(): ?string;

    public function isOrganizationVerified(): bool;

    public function isOrganizationActive(): bool;
}

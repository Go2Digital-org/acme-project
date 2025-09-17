<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Audit;

/**
 * Contract for entities that can be audited.
 * Used by the audit system to decouple from specific domain models.
 */
interface AuditableEntityInterface
{
    public function getAuditableId(): int;

    public function getAuditableType(): string;

    /** @return array<string, mixed> */
    public function getAuditableData(): array;
}

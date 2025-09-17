<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Audit;

/**
 * Contract for employee/user entities that can be audited.
 * Used by the audit system for employee-specific metadata.
 */
interface EmployeeAuditableInterface extends AuditableEntityInterface
{
    public function getEmployeeName(): string;

    public function getEmployeeEmail(): string;

    public function getEmployeeRole(): ?string;
}

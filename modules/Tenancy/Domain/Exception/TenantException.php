<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Exception;

use DomainException;
use Modules\Tenancy\Domain\Model\Tenant;

/**
 * Tenant Domain Exception.
 *
 * Represents domain-specific errors in tenant operations.
 */
final class TenantException extends DomainException
{
    /**
     * Invalid status transition.
     */
    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(
            "Invalid tenant status transition from '{$from}' to '{$to}'"
        );
    }

    /**
     * Cannot suspend inactive tenant.
     */
    /** @phpstan-ignore missingType.generics */
    public static function cannotSuspendInactiveTenant(Tenant $tenant): self
    {
        return new self(
            "Cannot suspend tenant {$tenant->id} with status '{$tenant->provisioning_status}'. " .
            'Only active tenants can be suspended.'
        );
    }

    /**
     * Cannot reactivate active tenant.
     */
    /** @phpstan-ignore missingType.generics */
    public static function cannotReactivateActiveTenant(Tenant $tenant): self
    {
        return new self(
            "Cannot reactivate tenant {$tenant->id} with status '{$tenant->provisioning_status}'. " .
            'Only suspended tenants can be reactivated.'
        );
    }

    /**
     * Tenant not found.
     */
    public static function notFound(string $identifier): self
    {
        return new self("Tenant not found: {$identifier}");
    }

    /**
     * Subdomain already taken.
     */
    public static function subdomainTaken(string $subdomain): self
    {
        return new self("Subdomain '{$subdomain}' is already taken");
    }

    /**
     * Reserved subdomain.
     */
    public static function reservedSubdomain(string $subdomain): self
    {
        return new self("Subdomain '{$subdomain}' is reserved and cannot be used");
    }

    /**
     * Database creation failed.
     */
    public static function databaseCreationFailed(string $database, string $error): self
    {
        return new self("Failed to create database '{$database}': {$error}");
    }

    /**
     * Migration failed.
     */
    public static function migrationFailed(string $tenantId, string $error): self
    {
        return new self("Failed to run migrations for tenant '{$tenantId}': {$error}");
    }

    /**
     * Admin creation failed.
     */
    public static function adminCreationFailed(string $tenantId, string $error): self
    {
        return new self("Failed to create admin user for tenant '{$tenantId}': {$error}");
    }

    /**
     * Index creation failed.
     */
    public static function indexCreationFailed(string $tenantId, string $error): self
    {
        return new self("Failed to create search indexes for tenant '{$tenantId}': {$error}");
    }

    /**
     * Provisioning in progress.
     */
    public static function provisioningInProgress(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' is currently being provisioned");
    }

    /**
     * Already provisioned.
     */
    public static function alreadyProvisioned(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' has already been provisioned");
    }
}

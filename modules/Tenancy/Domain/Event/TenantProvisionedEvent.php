<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Event;

use Modules\Tenancy\Domain\Model\Tenant;

/**
 * Tenant Provisioned Domain Event.
 *
 * Fired when a tenant has been successfully provisioned.
 */
final readonly class TenantProvisionedEvent
{
    /** @phpstan-ignore missingType.generics */
    public function __construct(
        public Tenant $tenant
    ) {}

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): string
    {
        return $this->tenant->id;
    }

    /**
     * Get event name for logging.
     */
    public function getName(): string
    {
        return 'tenant.provisioned';
    }

    /**
     * Get event payload for serialization.
     */
    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'subdomain' => $this->tenant->subdomain,
            'database' => $this->tenant->database,
            'status' => $this->tenant->provisioning_status,
            'provisioned_at' => $this->tenant->provisioned_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Event;

use Modules\Tenancy\Domain\Model\Tenant;

/**
 * Tenant Created Domain Event.
 *
 * Fired when a new tenant is created in the system.
 */
final readonly class TenantCreatedEvent
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
     * Get the subdomain.
     */
    public function getSubdomain(): string
    {
        return $this->tenant->subdomain;
    }

    /**
     * Get event name for logging.
     */
    public function getName(): string
    {
        return 'tenant.created';
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
            'created_at' => $this->tenant->created_at->toIso8601String(),
        ];
    }
}

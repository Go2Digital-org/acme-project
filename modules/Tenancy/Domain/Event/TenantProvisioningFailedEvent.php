<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Event;

use Modules\Tenancy\Domain\Model\Tenant;

/**
 * Tenant Provisioning Failed Domain Event.
 *
 * Fired when tenant provisioning fails.
 */
final readonly class TenantProvisioningFailedEvent
{
    /** @phpstan-ignore missingType.generics */
    public function __construct(
        public Tenant $tenant,
        public string $error
    ) {}

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): string
    {
        return $this->tenant->id;
    }

    /**
     * Get the error message.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Get event name for logging.
     */
    public function getName(): string
    {
        return 'tenant.provisioning_failed';
    }

    /**
     * Get event payload for serialization.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'subdomain' => $this->tenant->subdomain,
            'database' => $this->tenant->database,
            'status' => $this->tenant->provisioning_status,
            'error' => $this->error,
            'failed_at' => now()->toIso8601String(),
        ];
    }
}

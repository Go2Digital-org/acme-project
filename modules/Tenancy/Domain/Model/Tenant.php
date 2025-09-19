<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Tenancy\Domain\Event\TenantCreatedEvent;
use Modules\Tenancy\Domain\Event\TenantProvisionedEvent;
use Modules\Tenancy\Domain\Event\TenantProvisioningFailedEvent;
use Modules\Tenancy\Domain\Exception\TenantException;
use Modules\Tenancy\Domain\ValueObject\TenantDatabase;
use Modules\Tenancy\Domain\ValueObject\TenantDomain;
use Modules\Tenancy\Domain\ValueObject\TenantId;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Tenant Domain Model.
 *
 * Core aggregate root for multi-tenancy.
 * Extends Stancl\Tenancy base model with domain-specific behavior.
 *
 * @property string $id
 * @property string $subdomain
 * @property string $database
 * @property string $database_user
 * @property string $database_password
 * @property string $provisioning_status
 * @property string|null $provisioning_error
 * @property Carbon|null $provisioned_at
 * @property array<string, mixed> $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    /** @use HasFactory<Factory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Get custom columns for the tenant table.
     */
    /**
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'subdomain',
            'database',
            'database_user',
            'database_password',
            'provisioning_status',
            'provisioning_error',
            'provisioned_at',
        ];
    }

    /**
     * Get the tenant ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the tenant name.
     */
    public function getName(): string
    {
        return $this->data['name'] ?? $this->subdomain ?? 'Unnamed Tenant';
    }

    /**
     * Get the tenant domain/subdomain.
     */
    public function getDomain(): string
    {
        return $this->subdomain;
    }

    /**
     * Get the tenant status.
     */
    public function getStatus(): string
    {
        return $this->provisioning_status;
    }

    /**
     * Get the config data.
     */
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->data ?? [];
    }

    /**
     * Get the tenant ID value object.
     */
    public function getTenantId(): TenantId
    {
        return TenantId::fromString($this->id);
    }

    /**
     * Get the tenant database value object.
     */
    public function getTenantDatabase(): TenantDatabase
    {
        return TenantDatabase::fromString($this->database);
    }

    /**
     * Get the tenant domain value object.
     */
    public function getTenantDomain(): TenantDomain
    {
        return TenantDomain::fromSubdomain($this->subdomain);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->provisioning_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is pending provisioning.
     */
    public function isPending(): bool
    {
        return $this->provisioning_status === self::STATUS_PENDING;
    }

    /**
     * Check if tenant is currently being provisioned.
     */
    public function isProvisioning(): bool
    {
        return $this->provisioning_status === self::STATUS_PROVISIONING;
    }

    /**
     * Check if tenant provisioning failed.
     */
    public function hasFailed(): bool
    {
        return $this->provisioning_status === self::STATUS_FAILED;
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->provisioning_status === self::STATUS_SUSPENDED;
    }

    /**
     * Start provisioning process.
     *
     * @throws TenantException
     */
    public function startProvisioning(): void
    {
        if (! $this->isPending()) {
            throw TenantException::invalidStatusTransition(
                $this->provisioning_status,
                self::STATUS_PROVISIONING
            );
        }

        $this->update([
            'provisioning_status' => self::STATUS_PROVISIONING,
            'provisioning_error' => null,
        ]);
    }

    /**
     * Mark tenant as successfully provisioned.
     */
    public function markAsProvisioned(): void
    {
        $this->update([
            'provisioning_status' => self::STATUS_ACTIVE,
            'provisioning_error' => null,
            'provisioned_at' => now(),
        ]);

        event(new TenantProvisionedEvent($this));
    }

    /**
     * Mark tenant provisioning as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'provisioning_status' => self::STATUS_FAILED,
            'provisioning_error' => $error,
        ]);

        event(new TenantProvisioningFailedEvent($this, $error));
    }

    /**
     * Suspend the tenant.
     *
     * @throws TenantException
     */
    public function suspend(string $reason = ''): void
    {
        if (! $this->isActive()) {
            throw TenantException::cannotSuspendInactiveTenant($this);
        }

        $this->update([
            'provisioning_status' => self::STATUS_SUSPENDED,
            'data' => array_merge($this->data ?? [], [
                'suspension_reason' => $reason,
                'suspended_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Reactivate a suspended tenant.
     *
     * @throws TenantException
     */
    public function reactivate(): void
    {
        if (! $this->isSuspended()) {
            throw TenantException::cannotReactivateActiveTenant($this);
        }

        $data = $this->data ?? [];
        unset($data['suspension_reason'], $data['suspended_at']);

        $this->update([
            'provisioning_status' => self::STATUS_ACTIVE,
            'data' => $data,
        ]);
    }

    /**
     * Get admin user data from tenant data.
     */
    /**
     * @return array<string, mixed>
     */
    public function getAdminData(): ?array
    {
        return $this->data['admin'] ?? null;
    }

    /**
     * Store admin user data.
     */
    /**
     * @param  array<string, mixed>  $adminData
     */
    public function setAdminData(array $adminData): void
    {
        $this->update([
            'data' => array_merge($this->data ?? [], [
                'admin' => $adminData,
            ]),
        ]);
    }

    /**
     * Get feature flags for this tenant.
     */
    /**
     * @return array<string, mixed>
     */
    public function getFeatures(): array
    {
        return $this->data['features'] ?? [];
    }

    /**
     * Check if a feature is enabled for this tenant.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->getFeatures();

        return $features[$feature] ?? false;
    }

    /**
     * Enable a feature for this tenant.
     */
    public function enableFeature(string $feature): void
    {
        $features = $this->getFeatures();
        $features[$feature] = true;

        $this->update([
            'data' => array_merge($this->data ?? [], [
                'features' => $features,
            ]),
        ]);
    }

    /**
     * Disable a feature for this tenant.
     */
    public function disableFeature(string $feature): void
    {
        $features = $this->getFeatures();
        $features[$feature] = false;

        $this->update([
            'data' => array_merge($this->data ?? [], [
                'features' => $features,
            ]),
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            if (! $tenant->id) {
                $tenant->id = TenantId::generate()->toString();
            }

            if (! $tenant->database) {
                $tenant->database = TenantDatabase::fromTenantId(
                    TenantId::fromString($tenant->id)
                )->toString();
            }

            if (! $tenant->provisioning_status) {
                $tenant->provisioning_status = self::STATUS_PENDING;
            }
        });

        static::created(function (self $tenant): void {
            event(new TenantCreatedEvent($tenant));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provisioned_at' => 'datetime',
            'data' => 'array',
        ];
    }
}

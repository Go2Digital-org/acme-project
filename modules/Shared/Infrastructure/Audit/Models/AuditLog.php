<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Audit\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Infrastructure\Audit\Factories\AuditLogFactory;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $user_name
 * @property string $user_email
 * @property string $user_role
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property array<string, mixed> $old_values
 * @property array<string, mixed> $new_values
 * @property array<string, mixed> $metadata
 * @property Carbon $performed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User|null $user
 *
 * @method static Builder|AuditLog where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|AuditLog newModelQuery()
 * @method static Builder<static>|AuditLog newQuery()
 * @method static Builder<static>|AuditLog query()
 * @method static Builder<static>|AuditLog securityEvents()
 * @method static Builder<static>|AuditLog highRisk()
 * @method static Builder<static>|AuditLog byUser(int $userId)
 * @method static Builder<static>|AuditLog forEntity(string $entityType, int $entityId)
 * @method static Builder<static>|AuditLog dateRange(Carbon $from, Carbon $to)
 * @method static Builder<static>|AuditLog recent(int $days = 7)
 * @method static Builder<static>|AuditLog whereAction($value)
 * @method static Builder<static>|AuditLog whereCreatedAt($value)
 * @method static Builder<static>|AuditLog whereEntityId($value)
 * @method static Builder<static>|AuditLog whereEntityType($value)
 * @method static Builder<static>|AuditLog whereId($value)
 * @method static Builder<static>|AuditLog whereMetadata($value)
 * @method static Builder<static>|AuditLog whereNewValues($value)
 * @method static Builder<static>|AuditLog whereOldValues($value)
 * @method static Builder<static>|AuditLog wherePerformedAt($value)
 * @method static Builder<static>|AuditLog whereUpdatedAt($value)
 * @method static Builder<static>|AuditLog whereUserEmail($value)
 * @method static Builder<static>|AuditLog whereUserId($value)
 * @method static Builder<static>|AuditLog whereUserName($value)
 * @method static Builder<static>|AuditLog whereUserRole($value)
 *
 * @mixin Model
 */
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'metadata',
        'performed_at',
    ];

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the severity level of this audit log.
     */
    public function getSeverity(): string
    {
        return $this->metadata['severity'] ?? 'medium';
    }

    /**
     * Check if this audit log represents a security event.
     */
    public function isSecurityEvent(): bool
    {
        return str_starts_with($this->action, 'security.');
    }

    /**
     * Check if this audit log requires review.
     */
    public function requiresReview(): bool
    {
        return $this->metadata['requires_review'] ?? false;
    }

    /**
     * Get the IP address from metadata.
     */
    public function getIpAddress(): ?string
    {
        return $this->metadata['ip_address'] ?? null;
    }

    /**
     * Get the user agent from metadata.
     */
    public function getUserAgent(): ?string
    {
        return $this->metadata['user_agent'] ?? null;
    }

    /**
     * Get the URL from metadata.
     */
    public function getUrl(): ?string
    {
        return $this->metadata['url'] ?? null;
    }

    /**
     * Get the HTTP method from metadata.
     */
    public function getMethod(): ?string
    {
        return $this->metadata['method'] ?? null;
    }

    /**
     * Check if the action was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->metadata['success'] ?? true;
    }

    /**
     * Get human-readable action description.
     */
    public function getActionDescription(): string
    {
        return match ($this->action) {
            'campaign.create' => 'Created campaign',
            'campaign.update' => 'Updated campaign',
            'campaign.delete' => 'Deleted campaign',
            'campaign.activate' => 'Activated campaign',
            'campaign.feature' => 'Featured campaign',
            'campaign.unfeature' => 'Unfeatured campaign',

            'donation.create' => 'Created donation',
            'donation.update' => 'Updated donation',
            'donation.process' => 'Processed donation',
            'donation.complete' => 'Completed donation',
            'donation.refund' => 'Refunded donation',
            'donation.cancel' => 'Cancelled donation',

            'organization.create' => 'Created organization',
            'organization.update' => 'Updated organization',
            'organization.verify' => 'Verified organization',
            'organization.unverify' => 'Unverified organization',
            'organization.activate' => 'Activated organization',
            'organization.deactivate' => 'Deactivated organization',

            'employee.create' => 'Created employee',
            'employee.update' => 'Updated employee',
            'employee.activate' => 'Activated employee',
            'employee.deactivate' => 'Deactivated employee',
            'employee.role_change' => 'Changed employee role',
            'employee.password_reset' => 'Reset employee password',

            'bulk.approve' => 'Bulk approved items',
            'bulk.delete' => 'Bulk deleted items',
            'bulk.export' => 'Bulk exported data',
            'bulk.notify' => 'Sent bulk notifications',

            'security.login_success' => 'Successful login',
            'security.login_failed' => 'Failed login attempt',
            'security.logout' => 'User logout',
            'security.password_changed' => 'Password changed',
            'security.permission_denied' => 'Permission denied',
            'security.unauthorized_access' => 'Unauthorized access attempt',

            'system.backup' => 'System backup',
            'system.maintenance' => 'System maintenance',
            'system.config_change' => 'Configuration change',

            default => ucfirst(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    /**
     * Get the entity name for display.
     */
    public function getEntityName(): string
    {
        if (! $this->entity_id) {
            return '';
        }

        return match ($this->entity_type) {
            'campaign' => $this->metadata['campaign_title'] ?? "Campaign #{$this->entity_id}",
            'donation' => "Donation #{$this->entity_id}",
            'organization' => $this->metadata['organization_name'] ?? "Organization #{$this->entity_id}",
            'employee' => $this->metadata['employee_name'] ?? "Employee #{$this->entity_id}",
            default => ucfirst($this->entity_type) . " #{$this->entity_id}",
        };
    }

    /**
     * Get changes summary for display.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChangesSummary(): array
    {
        $changes = [];

        if (empty($this->old_values) && empty($this->new_values)) {
            return $changes;
        }

        // Handle creation (no old values)
        if (empty($this->old_values) && ! empty($this->new_values)) {
            foreach ($this->new_values as $key => $value) {
                $changes[] = [
                    'field' => $key,
                    'change_type' => 'created',
                    'old_value' => null,
                    'new_value' => $value,
                ];
            }

            return $changes;
        }

        // Handle updates
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $key,
                    'change_type' => 'updated',
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Scope for security events.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeSecurityEvents(Builder $query): Builder
    {
        return $query->where('action', 'like', 'security.%');
    }

    /**
     * Scope for high-risk actions.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('metadata->severity', 'high')
            ->orWhere('metadata->requires_review', true);
    }

    /**
     * Scope for user actions.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for entity.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Scope for date range.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    /**
     * Scope for recent logs.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'performed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

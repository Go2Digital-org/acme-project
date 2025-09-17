<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Modules\Notification\Infrastructure\Laravel\Factory\NotificationFactory;

/**
 * Domain entity representing a notification in the ACME Corp CSR platform.
 *
 * This model follows Laravel's standard notification structure for compatibility
 * with the built-in notification system.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property array<string, mixed> $data
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $notifiable_id
 * @property-read string $user_id
 * @property-read string|null $sender_id
 * @property-read string|null $status
 * @property-read string|null $title
 * @property-read string|null $message
 * @property-read string|null $priority
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $channel
 * @property-read Carbon|null $sent_at
 * @property-read Carbon|null $scheduled_for
 * @property-read Carbon|null $expires_at
 * @property-read array<string, mixed>|null $actions
 * @property-read Model|null $sender
 * @property-read Model|null $recipient
 */
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /** @var array<string, string> */
    protected $attributes = [
        'data' => '{}',
    ];

    /**
     * Get the notifiable entity that this notification belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->read_at = now();
            $this->save();
        }
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->read_at = null;
        $this->save();
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get the notification's title from data.
     */
    public function getTitle(): ?string
    {
        return $this->data['title'] ?? null;
    }

    /**
     * Get the notification's message from data.
     */
    public function getMessage(): ?string
    {
        return $this->data['message'] ?? $this->data['description'] ?? null;
    }

    /**
     * Get the notification's type label from data.
     */
    public function getTypeLabel(): ?string
    {
        return $this->data['type'] ?? class_basename($this->type);
    }

    /**
     * Check if notification has high priority.
     */
    public function isHighPriority(): bool
    {
        $priority = $this->data['priority'] ?? 'normal';

        return in_array($priority, ['high', 'urgent', 'critical'], true);
    }

    /**
     * Check if notification supports real-time broadcast.
     */
    public function supportsRealTimeBroadcast(): bool
    {
        return $this->data['broadcast_enabled'] ?? false;
    }

    /**
     * Check if notification supports desktop notification.
     */
    public function supportsDesktopNotification(): bool
    {
        return $this->data['desktop_enabled'] ?? true;
    }

    /**
     * Check if notification has organization context.
     */
    public function hasOrganizationContext(): bool
    {
        return isset($this->data['organization_id']);
    }

    /**
     * Get organization ID from notification data.
     */
    public function getOrganizationId(): ?int
    {
        return isset($this->data['organization_id']) ? (int) $this->data['organization_id'] : null;
    }

    /**
     * Check if notification has campaign context.
     */
    public function hasCampaignContext(): bool
    {
        return isset($this->data['campaign_id']);
    }

    /**
     * Get campaign ID from notification data.
     */
    public function getCampaignId(): ?int
    {
        return isset($this->data['campaign_id']) ? (int) $this->data['campaign_id'] : null;
    }

    /**
     * Get action URL for notification.
     */
    public function getActionUrl(): ?string
    {
        return $this->data['url'] ?? $this->data['action_url'] ?? null;
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function unread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function read($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include sendable notifications.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function sendable($query)
    {
        return $query->where(function ($q): void {
            $q->whereNull('data->status')
                ->orWhere('data->status', '!=', 'sent');
        });
    }

    /**
     * Scope a query to filter notifications by type.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function ofType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter notifications by priority.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function byPriority($query, string $priority)
    {
        return $query->where('data->priority', $priority);
    }

    /**
     * Scope a query to only include recent notifications.
     *
     * @param  Builder<static>  $query
     * @param  int  $hours  Number of hours to look back (default: 7 days)
     * @return Builder<static>
     */
    #[Scope]
    protected function recent($query, int $hours = 168)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if notification can be sent now.
     */
    public function canBeSentNow(): bool
    {
        $scheduledAt = $this->data['scheduled_at'] ?? null;

        if ($scheduledAt === null) {
            return true;
        }

        return Carbon::parse($scheduledAt)->isPast();
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(): void
    {
        $data = $this->data;
        $data['status'] = 'sent';
        $data['sent_at'] = now()->toDateTimeString();
        $this->data = $data;
        $this->save();
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $data = $this->data;
        $data['status'] = 'failed';
        $data['failed_at'] = now()->toDateTimeString();

        if ($reason !== null) {
            $data['failure_reason'] = $reason;
        }

        $this->data = $data;
        $this->save();
    }

    /**
     * Get the recipient ID (alias for notifiable_id).
     *
     * @return Attribute<string, never>
     */
    protected function recipientId(): Attribute
    {
        return Attribute::make(get: fn () => $this->notifiable_id);
    }

    /**
     * Get the sender ID from data.
     *
     * @return Attribute<string|null, never>
     */
    protected function senderId(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['sender_id'] ?? null);
    }

    /**
     * Get the status from data.
     *
     * @return Attribute<string|null, never>
     */
    protected function status(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['status'] ?? null);
    }

    /**
     * Get the title from data (accessor property).
     *
     * @return Attribute<string|null, never>
     */
    protected function title(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['title'] ?? null);
    }

    /**
     * Get the message from data (accessor property).
     *
     * @return Attribute<string|null, never>
     */
    protected function message(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['message'] ?? null);
    }

    /**
     * Get the priority from data.
     *
     * @return Attribute<string|null, never>
     */
    protected function priority(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['priority'] ?? null);
    }

    /**
     * Get the metadata from data.
     *
     * @return Attribute<array<string, mixed>|null, never>
     */
    protected function metadata(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['metadata'] ?? null);
    }

    /**
     * Get the channel from data.
     *
     * @return Attribute<string|null, never>
     */
    protected function channel(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['channel'] ?? null);
    }

    /**
     * Get the sent_at timestamp from data.
     *
     * @return Attribute<Carbon|null, never>
     */
    protected function sentAt(): Attribute
    {
        return Attribute::make(get: function (): ?Carbon {
            $sentAt = $this->data['sent_at'] ?? null;

            return $sentAt ? Carbon::parse($sentAt) : null;
        });
    }

    /**
     * Get the scheduled_for timestamp from data.
     *
     * @return Attribute<Carbon|null, never>
     */
    protected function scheduledFor(): Attribute
    {
        return Attribute::make(get: function (): ?Carbon {
            $scheduledFor = $this->data['scheduled_for'] ?? $this->data['scheduled_at'] ?? null;

            return $scheduledFor ? Carbon::parse($scheduledFor) : null;
        });
    }

    /**
     * Get the expires_at timestamp from data.
     *
     * @return Attribute<Carbon|null, never>
     */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(get: function (): ?Carbon {
            $expiresAt = $this->data['expires_at'] ?? null;

            return $expiresAt ? Carbon::parse($expiresAt) : null;
        });
    }

    /**
     * Get the actions from data.
     *
     * @return Attribute<array<string, mixed>|null, never>
     */
    protected function actions(): Attribute
    {
        return Attribute::make(get: fn () => $this->data['actions'] ?? null);
    }

    /**
     * Get the sender (alias for notifiable when appropriate).
     *
     * @return Attribute<Model|null, never>
     */
    protected function sender(): Attribute
    {
        return Attribute::make(get: function () {
            $senderId = $this->data['sender_id'] ?? null;
            $senderType = $this->data['sender_type'] ?? null;
            if ($senderId && $senderType) {
                return $senderType::find($senderId);
            }

            return null;
        });
    }

    /**
     * Get the recipient (alias for notifiable).
     *
     * @return Attribute<Model|null, never>
     */
    protected function recipient(): Attribute
    {
        return Attribute::make(get: fn () => $this->notifiable);
    }

    /**
     * Get the user ID (alias for notifiable_id).
     *
     * @return Attribute<string, never>
     */
    protected function userId(): Attribute
    {
        return Attribute::make(get: fn () => (string) $this->notifiable_id);
    }

    /**
     * Get a new factory instance for the model.
     */
    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    /**
     * @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'notifiable_id' => 'integer',
        ];
    }
}

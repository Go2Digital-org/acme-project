<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Model;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Domain entity representing user notification preferences.
 *
 * Manages user-specific notification settings including channel preferences,
 * frequency settings, quiet hours, and opt-out preferences.
 *
 * @property string $id
 * @property string $user_id
 * @property array<string, mixed> $channel_preferences
 * @property array<string, mixed> $frequency_preferences
 * @property array<string, mixed> $type_preferences
 * @property array<string, mixed> $preferences
 * @property array<string, mixed> $quiet_hours
 * @property string $timezone
 * @property int $digest_frequency
 * @property bool $global_email_enabled
 * @property bool $global_sms_enabled
 * @property bool $global_push_enabled
 * @property array<string, mixed> $metadata
 * @property DateTime $created_at
 * @property DateTime $updated_at
 * @property User $user
 */
final class NotificationPreferences extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'channel_preferences',
        'frequency_preferences',
        'type_preferences',
        'preferences',
        'quiet_hours',
        'timezone',
        'digest_frequency',
        'global_email_enabled',
        'global_sms_enabled',
        'global_push_enabled',
        'metadata',
    ];

    protected $attributes = [
        'channel_preferences' => '{}',
        'frequency_preferences' => '{}',
        'type_preferences' => '{}',
        'preferences' => '{}',
        'quiet_hours' => '{}',
        'timezone' => 'UTC',
        'digest_frequency' => 1,
        'global_email_enabled' => true,
        'global_sms_enabled' => true,
        'global_push_enabled' => true,
        'metadata' => '{}',
    ];

    /**
     * Get the user relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Domain Business Logic

    /**
     * Check if a specific channel is enabled for a notification type.
     */
    public function isChannelEnabledForType(string $channel, string $notificationType): bool
    {
        // Check global channel setting first
        if (! $this->isGlobalChannelEnabled($channel)) {
            return false;
        }

        // Check type-specific channel preferences
        $typePreferences = $this->channel_preferences[$notificationType] ?? [];

        return $typePreferences[$channel] ?? $this->getDefaultChannelSetting($channel);
    }

    /**
     * Check if a specific channel is enabled globally.
     */
    public function isChannelEnabled(string $channel): bool
    {
        return $this->isGlobalChannelEnabled($channel);
    }

    /**
     * Get frequency preference for a notification type.
     */
    public function getFrequencyForType(string $notificationType): string
    {
        return $this->frequency_preferences[$notificationType] ?? 'immediate';
    }

    /**
     * Check if user has opted out of a notification type.
     */
    public function hasOptedOutOfType(string $notificationType): bool
    {
        return ($this->type_preferences[$notificationType]['enabled'] ?? true) === false;
    }

    /**
     * Check if user is currently in quiet hours.
     */
    public function isInQuietHours(?DateTime $time = null): bool
    {
        $time ??= now($this->timezone);

        if (empty($this->quiet_hours['start_time']) || empty($this->quiet_hours['end_time'])) {
            return false;
        }

        $startTime = $this->quiet_hours['start_time']; // e.g., "22:00"
        $endTime = $this->quiet_hours['end_time'];     // e.g., "07:00"

        $currentTime = $time->format('H:i');

        // Handle overnight quiet hours (e.g., 22:00 - 07:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        // Handle same-day quiet hours (e.g., 13:00 - 14:00)
        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Set channel preference for a notification type.
     */
    public function setChannelPreference(string $notificationType, string $channel, bool $enabled): void
    {
        $preferences = $this->channel_preferences;
        $preferences[$notificationType][$channel] = $enabled;
        $this->channel_preferences = $preferences;
    }

    /**
     * Set frequency preference for a notification type.
     */
    public function setFrequencyPreference(string $notificationType, string $frequency): void
    {
        $preferences = $this->frequency_preferences;
        $preferences[$notificationType] = $frequency;
        $this->frequency_preferences = $preferences;
    }

    /**
     * Opt out of a notification type.
     */
    public function optOutOfType(string $notificationType): void
    {
        $preferences = $this->type_preferences;
        $preferences[$notificationType]['enabled'] = false;
        $preferences[$notificationType]['opted_out_at'] = now()->toIso8601String();
        $this->type_preferences = $preferences;
    }

    /**
     * Opt back in to a notification type.
     */
    public function optInToType(string $notificationType): void
    {
        $preferences = $this->type_preferences;
        $preferences[$notificationType]['enabled'] = true;
        unset($preferences[$notificationType]['opted_out_at']);
        $this->type_preferences = $preferences;
    }

    /**
     * Set quiet hours.
     */
    public function setQuietHours(string $startTime, string $endTime, ?string $timezone = null): void
    {
        $this->quiet_hours = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'enabled' => true,
        ];

        if ($timezone) {
            $this->timezone = $timezone;
        }
    }

    /**
     * Disable quiet hours.
     */
    public function disableQuietHours(): void
    {
        $quietHours = $this->quiet_hours;
        $quietHours['enabled'] = false;
        $this->quiet_hours = $quietHours;
    }

    /**
     * Get all enabled channels for a notification type.
     *
     * @return array<int, string>
     */
    public function getEnabledChannelsForType(string $notificationType): array
    {
        $enabledChannels = [];
        $channels = ['database', 'email', 'sms', 'push'];

        foreach ($channels as $channel) {
            if ($this->isChannelEnabledForType($channel, $notificationType)) {
                $enabledChannels[] = $channel;
            }
        }

        return $enabledChannels;
    }

    /**
     * Get delivery preference summary for a notification type.
     *
     * @return array<string, mixed>
     */
    public function getDeliveryPreferenceForType(string $notificationType): array
    {
        return [
            'enabled' => ! $this->hasOptedOutOfType($notificationType),
            'frequency' => $this->getFrequencyForType($notificationType),
            'channels' => $this->getEnabledChannelsForType($notificationType),
            'in_quiet_hours' => $this->isInQuietHours(),
        ];
    }

    /**
     * Update metadata with new information.
     *
     * @param  array<string, mixed>  $newMetadata
     */
    public function updateMetadata(array $newMetadata): void
    {
        $this->metadata = array_merge($this->metadata ?? [], $newMetadata);
    }

    /**
     * Check if global channel setting is enabled.
     */
    private function isGlobalChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => $this->global_email_enabled,
            'sms' => $this->global_sms_enabled,
            'push' => $this->global_push_enabled,
            default => true,
        };
    }

    /**
     * Get default channel setting.
     */
    private function getDefaultChannelSetting(string $channel): bool
    {
        return match ($channel) {
            'database' => true,  // Always enabled by default
            'email' => true,     // Enabled by default
            'sms' => false,      // Disabled by default (requires opt-in)
            'push' => true,      // Enabled by default
            default => false,
        };
    }

    protected function casts(): array
    {
        return [
            'channel_preferences' => 'array',
            'frequency_preferences' => 'array',
            'type_preferences' => 'array',
            'preferences' => 'array',
            'quiet_hours' => 'array',
            'digest_frequency' => 'integer',
            'global_email_enabled' => 'boolean',
            'global_sms_enabled' => 'boolean',
            'global_push_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }
}

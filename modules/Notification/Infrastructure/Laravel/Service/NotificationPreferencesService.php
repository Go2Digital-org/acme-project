<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Service;

use Log;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Notification preferences service
 *
 * Manages user notification preferences for different channels
 */
final class NotificationPreferencesService
{
    /**
     * Get user notification preferences
     */
    /**
     * @return array<string, mixed>
     */
    public function getPreferences(User $user): array
    {
        // In a real implementation, this would fetch from a preferences table
        // For now, return default preferences
        return [
            'email_enabled' => true,
            'email_frequency' => 'instant', // instant, daily, weekly
            'sms_enabled' => false,
            'sms_types' => ['donations', 'urgent'], // types of notifications to send via SMS
            'push_enabled' => true,
            'push_types' => ['donations', 'campaigns', 'milestones'],
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'timezone' => $user->timezone ?? 'UTC',
            'slack_enabled' => ! empty($user->slack_webhook_url ?? null),
        ];
    }

    /**
     * Update user notification preferences
     */
    /**
     * @param  array<string, mixed>  $preferences
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        // In a real implementation, this would update the preferences table
        // For now, just log the update
        Log::info('Updating notification preferences', [
            'user_id' => $user->id,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Check if user wants notifications for a specific type
     */
    public function wantsNotification(User $user, string $type, string $channel = 'email'): bool
    {
        $preferences = $this->getPreferences($user);

        // Check if channel is enabled
        if (! ($preferences["{$channel}_enabled"] ?? false)) {
            return false;
        }

        // Check if notification type is allowed for this channel
        $allowedTypes = $preferences["{$channel}_types"] ?? [];

        return empty($allowedTypes) || in_array($type, $allowedTypes, true);
    }

    /**
     * Get optimal delivery channels for user
     *
     * @return string[]
     */
    public function getDeliveryChannels(User $user, string $notificationType): array
    {
        $preferences = $this->getPreferences($user);
        $channels = [];

        // Always include database for in-app notifications
        $channels[] = 'database';

        // Email (most users want email notifications)
        if ($preferences['email_enabled'] && $this->wantsNotification($user, $notificationType, 'email')) {
            $channels[] = 'mail';
        }

        // SMS (only for important notifications and if user has phone)
        if ($preferences['sms_enabled'] &&
            $this->wantsNotification($user, $notificationType, 'sms') &&
            ! empty($user->phone)) {
            $channels[] = 'sms';
        }

        // Push notifications (for real-time updates)
        if ($preferences['push_enabled'] &&
            $this->wantsNotification($user, $notificationType, 'push')) {
            $channels[] = 'broadcast';
        }

        // Slack (for corporate notifications)
        if ($preferences['slack_enabled']) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get default preferences for new users
     */
    /**
     * @return array<string, mixed>
     */
    public function getDefaultPreferences(): array
    {
        return [
            'email_enabled' => true,
            'email_frequency' => 'instant',
            'sms_enabled' => false,
            'sms_types' => ['urgent'],
            'push_enabled' => true,
            'push_types' => ['donations', 'campaigns'],
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'timezone' => 'UTC',
            'slack_enabled' => false,
        ];
    }
}

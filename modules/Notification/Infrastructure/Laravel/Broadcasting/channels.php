<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Notification Broadcasting Channels
|--------------------------------------------------------------------------
|
| This file defines the broadcast channels for the notification module.
| These channels handle real-time notification delivery via WebSockets.
|
*/

// User-specific notification channels (private)
Broadcast::channel('user.{userId}.notifications', fn ($user, $userId): bool => (int) $user->id === (int) $userId);

// User desktop notification channel (private)
Broadcast::channel('user.{userId}.desktop', fn ($user, $userId): bool => (int) $user->id === (int) $userId);

// Campaign-specific notification channels (private - only for campaign participants)
Broadcast::channel('campaign.{campaignId}.notifications', fn ($user, $campaignId) =>
    // Check if user is involved with this campaign (creator, donor, or organization admin)
    $user->hasAccessToCampaign($campaignId));

// Organization notification channels (private - only for organization members)
Broadcast::channel('organization.{organizationId}.notifications', fn ($user, $organizationId) =>
    // Check if user belongs to this organization
    $user->belongsToOrganization($organizationId));

// Admin notification channels (private - admin users only)
Broadcast::channel('admin.notifications', fn ($user) => $user->hasRole(['super_admin', 'admin']));

// Platform-wide announcement channel (public - authenticated users only)
Broadcast::channel('platform.announcements', fn ($user): array => ['id' => $user->id, 'name' => $user->name]);

// Notification activity channels (presence channels for real-time interaction)
Broadcast::channel('notification.{notificationId}.activity', function ($user, $notificationId): array|false {
    // Check if user can access this notification
    if ($user->canAccessNotification($notificationId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }

    return false;
});

// System status channel (public - for system-wide notifications)
Broadcast::channel('system.status', function (): true {
    return true; // Public channel for system status updates
});

// Real-time metrics channel (private - admin users only)
Broadcast::channel('admin.metrics.notifications', fn ($user) => $user->hasPermission('view_notification_metrics'));

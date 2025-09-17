<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Repository interface for notification preferences persistence operations.
 *
 * Handles user notification preferences including channel preferences,
 * frequency settings, and notification type subscriptions.
 */
interface NotificationPreferencesRepositoryInterface
{
    /**
     * Find preferences by user ID.
     */
    public function findByUserId(string $userId): ?NotificationPreferences;

    /**
     * Create or update user preferences.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $userId, array $data): NotificationPreferences;

    /**
     * Get channel preferences for a user and notification type.
     *
     * @return array<string, bool>
     */
    public function getChannelPreferences(string $userId, string $notificationType): array;

    /**
     * Update channel preference for a specific notification type.
     */
    public function updateChannelPreference(
        string $userId,
        string $notificationType,
        string $channel,
        bool $enabled,
    ): bool;

    /**
     * Get frequency setting for a user and notification type.
     */
    public function getFrequencyPreference(string $userId, string $notificationType): string;

    /**
     * Update frequency preference for a notification type.
     */
    public function updateFrequencyPreference(
        string $userId,
        string $notificationType,
        string $frequency,
    ): bool;

    /**
     * Check if user has opted out of a notification type.
     */
    public function hasOptedOut(string $userId, string $notificationType): bool;

    /**
     * Opt user out of a notification type.
     */
    public function optOut(string $userId, string $notificationType): bool;

    /**
     * Opt user back in to a notification type.
     */
    public function optIn(string $userId, string $notificationType): bool;

    /**
     * Get users who should receive notifications of a specific type.
     *
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    /**
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    public function filterUsersForNotificationType(array $userIds, string $notificationType): array;

    /**
     * Get users subscribed to email digest.
     *
     * @return Collection<int, User>
     */
    public function getUsersForEmailDigest(string $frequency = 'daily'): Collection;

    /**
     * Get default preferences for a new user.
     *
     * @return array<string, mixed>
     */
    public function getDefaultPreferences(): array;

    /**
     * Bulk update preferences for multiple users.
     *
     * @param  array<string, array<string, mixed>>  $userPreferences
     */
    public function bulkUpdate(array $userPreferences): int;

    /**
     * Get preferences statistics for analytics.
     *
     * @return array<string, mixed>
     */
    public function getPreferencesStatistics(): array;

    /**
     * Get users who should receive notifications via a specific channel.
     *
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    public function filterUsersByChannel(array $userIds, string $channel, string $notificationType): array;

    /**
     * Update quiet hours for a user.
     */
    public function updateQuietHours(
        string $userId,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $timezone = null,
    ): bool;

    /**
     * Check if user is in quiet hours.
     */
    public function isInQuietHours(string $userId): bool;

    /**
     * Get users for notification batching based on frequency preferences.
     *
     * @return array<string, array<int, string>>
     */
    public function getUsersForBatching(): array;

    /**
     * Create new notification preferences.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationPreferences;

    /**
     * Update existing notification preferences.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationPreferences $preferences, array $data): bool;

    /**
     * Find users by digest frequency.
     *
     * @return Collection<int, NotificationPreferences>
     */
    public function findByDigestFrequency(string $frequency): Collection;

    /**
     * Find preferences by user.
     */
    public function findByUser(User $user): ?NotificationPreferences;
}

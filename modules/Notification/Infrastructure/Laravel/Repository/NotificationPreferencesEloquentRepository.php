<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Eloquent implementation of notification preferences repository.
 *
 * Handles user notification preferences with caching and performance optimizations.
 */
final readonly class NotificationPreferencesEloquentRepository implements NotificationPreferencesRepositoryInterface
{
    public function __construct(
        private NotificationPreferences $model,
    ) {}

    public function findByUserId(string $userId): ?NotificationPreferences
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $userId, array $data): NotificationPreferences
    {
        return $this->model->updateOrCreate(
            ['user_id' => $userId],
            $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getChannelPreferences(string $userId, string $notificationType): array
    {
        $preferences = $this->findByUserId($userId);

        if (! $preferences instanceof NotificationPreferences) {
            return $this->getDefaultChannelPreferences();
        }

        $channelPreferences = $preferences->channel_preferences[$notificationType] ?? [];

        // Ensure $channelPreferences is always an array for array_merge
        if (! is_array($channelPreferences)) {
            $channelPreferences = [];
        }

        return array_merge($this->getDefaultChannelPreferences(), $channelPreferences);
    }

    public function updateChannelPreference(
        string $userId,
        string $notificationType,
        string $channel,
        bool $enabled,
    ): bool {
        $preferences = $this->findByUserId($userId) ?? $this->createDefaultPreferences($userId);

        $preferences->setChannelPreference($notificationType, $channel, $enabled);

        return $preferences->save();
    }

    public function getFrequencyPreference(string $userId, string $notificationType): string
    {
        $preferences = $this->findByUserId($userId);

        return $preferences?->getFrequencyForType($notificationType) ?? 'immediate';
    }

    public function updateFrequencyPreference(
        string $userId,
        string $notificationType,
        string $frequency,
    ): bool {
        $preferences = $this->findByUserId($userId) ?? $this->createDefaultPreferences($userId);

        $preferences->setFrequencyPreference($notificationType, $frequency);

        return $preferences->save();
    }

    public function hasOptedOut(string $userId, string $notificationType): bool
    {
        $preferences = $this->findByUserId($userId);

        return $preferences?->hasOptedOutOfType($notificationType) ?? false;
    }

    public function optOut(string $userId, string $notificationType): bool
    {
        $preferences = $this->findByUserId($userId) ?? $this->createDefaultPreferences($userId);

        $preferences->optOutOfType($notificationType);

        return $preferences->save();
    }

    public function optIn(string $userId, string $notificationType): bool
    {
        $preferences = $this->findByUserId($userId) ?? $this->createDefaultPreferences($userId);

        $preferences->optInToType($notificationType);

        return $preferences->save();
    }

    /**
     * @param  array<string>  $userIds
     * @return array<int, string>
     */
    public function filterUsersForNotificationType(array $userIds, string $notificationType): array
    {
        if ($userIds === []) {
            return [];
        }

        // Use raw query for better performance with large user sets
        $optedOutUsers = $this->model
            ->whereIn('user_id', $userIds)
            ->whereRaw("JSON_EXTRACT(type_preferences, '$.{$notificationType}.enabled') = false")
            ->pluck('user_id')
            ->toArray();

        return array_values(array_diff($userIds, $optedOutUsers));
    }

    public function getUsersForEmailDigest(string $frequency = 'daily'): Collection
    {
        $preferences = $this->model
            ->where('global_email_enabled', true)
            ->where(function (Builder $query) use ($frequency): void {
                $query->whereRaw("JSON_EXTRACT(frequency_preferences, '$.email_digest') = ?", [$frequency])
                    ->orWhereRaw("JSON_EXTRACT(frequency_preferences, '$.email_digest') IS NULL");
            })
            ->whereHas('user', function (Builder $query): void {
                $query->whereRaw("JSON_EXTRACT(notification_preferences, '$.email_digest.enabled') IS NULL OR JSON_EXTRACT(notification_preferences, '$.email_digest.enabled') != false");
            })
            ->with('user')
            ->get();

        return $preferences->pluck('user')->filter(); // @phpstan-ignore-line
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultPreferences(): array
    {
        return [
            'channel_preferences' => [],
            'frequency_preferences' => [],
            'type_preferences' => [],
            'quiet_hours' => [],
            'timezone' => 'UTC',
            'global_email_enabled' => true,
            'global_sms_enabled' => true,
            'global_push_enabled' => true,
            'metadata' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $userPreferences
     */
    public function bulkUpdate(array $userPreferences): int
    {
        $updated = 0;

        DB::transaction(function () use ($userPreferences, &$updated): void {
            foreach ($userPreferences as $userId => $preferences) {
                $result = $this->model->updateOrCreate(
                    ['user_id' => $userId],
                    $preferences,
                );

                if ($result->wasRecentlyCreated || $result->wasChanged()) {
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreferencesStatistics(): array
    {
        return [
            'total_users_with_preferences' => $this->model->count(),
            'email_enabled_users' => $this->model->where('global_email_enabled', true)->count(),
            'sms_enabled_users' => $this->model->where('global_sms_enabled', true)->count(),
            'push_enabled_users' => $this->model->where('global_push_enabled', true)->count(),
            'users_with_quiet_hours' => $this->model
                ->whereRaw("JSON_EXTRACT(quiet_hours, '$.enabled') = true")
                ->count(),
            'frequency_distribution' => $this->getFrequencyDistribution(),
            'most_disabled_types' => $this->getMostDisabledTypes(),
        ];
    }

    /**
     * @param  array<string>  $userIds
     * @return array<int, string>
     */
    public function filterUsersByChannel(array $userIds, string $channel, string $notificationType): array
    {
        if ($userIds === []) {
            return [];
        }

        $globalColumnMap = [
            'email' => 'global_email_enabled',
            'sms' => 'global_sms_enabled',
            'push' => 'global_push_enabled',
        ];

        $query = $this->model->whereIn('user_id', $userIds);

        // Check global channel setting
        if (isset($globalColumnMap[$channel])) {
            $query->where($globalColumnMap[$channel], true);
        }

        // Check type-specific channel preferences
        $enabledUsers = $query
            ->where(function (Builder $q) use ($channel, $notificationType): void {
                $q->whereRaw("JSON_EXTRACT(channel_preferences, '$.{$notificationType}.{$channel}') = true")
                    ->orWhereRaw("JSON_EXTRACT(channel_preferences, '$.{$notificationType}.{$channel}') IS NULL");
            })
            ->pluck('user_id')
            ->toArray();

        // For users without preferences, include them if it's a default-enabled channel
        $usersWithoutPreferences = array_diff($userIds, $this->model->whereIn('user_id', $userIds)->pluck('user_id')->toArray());

        if ($this->isChannelEnabledByDefault($channel)) {
            $enabledUsers = array_merge($enabledUsers, $usersWithoutPreferences);
        }

        return array_values(array_unique($enabledUsers));
    }

    public function updateQuietHours(
        string $userId,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $timezone = null,
    ): bool {
        $preferences = $this->findByUserId($userId) ?? $this->createDefaultPreferences($userId);

        if ($startTime && $endTime) {
            $preferences->setQuietHours($startTime, $endTime, $timezone);

            return $preferences->save();
        }

        $preferences->disableQuietHours();

        return $preferences->save();
    }

    public function isInQuietHours(string $userId): bool
    {
        $preferences = $this->findByUserId($userId);

        return $preferences?->isInQuietHours() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersForBatching(): array
    {
        $batches = [
            'immediate' => [],
            'hourly' => [],
            'daily' => [],
            'weekly' => [],
        ];

        $preferences = $this->model->with('user')->get();

        foreach ($preferences as $preference) {
            $frequencySettings = $preference->frequency_preferences;

            foreach ($frequencySettings as $frequency) {
                if (isset($batches[$frequency])) {
                    $batches[$frequency][] = $preference->user_id;
                }
            }
        }

        // Remove duplicates
        return array_map('array_unique', $batches);
    }

    /**
     * Create default preferences for a user.
     */
    private function createDefaultPreferences(string $userId): NotificationPreferences
    {
        return $this->model->create(array_merge(
            ['user_id' => $userId],
            $this->getDefaultPreferences(),
        ));
    }

    /**
     * Get default channel preferences.
     */
    /**
     * @return array<string, mixed>
     */
    private function getDefaultChannelPreferences(): array
    {
        return [
            'database' => true,
            'email' => true,
            'sms' => false,
            'push' => true,
        ];
    }

    /**
     * Check if channel is enabled by default.
     */
    private function isChannelEnabledByDefault(string $channel): bool
    {
        $defaults = $this->getDefaultChannelPreferences();

        return $defaults[$channel] ?? false;
    }

    /**
     * Get frequency distribution for statistics.
     */
    /**
     * @return array<string, mixed>
     */
    private function getFrequencyDistribution(): array
    {
        // This is a simplified version - in reality, you'd analyze the JSON data
        return [
            'immediate' => $this->model->whereRaw("JSON_EXTRACT(frequency_preferences, '$') LIKE '%immediate%'")->count(),
            'hourly' => $this->model->whereRaw("JSON_EXTRACT(frequency_preferences, '$') LIKE '%hourly%'")->count(),
            'daily' => $this->model->whereRaw("JSON_EXTRACT(frequency_preferences, '$') LIKE '%daily%'")->count(),
            'weekly' => $this->model->whereRaw("JSON_EXTRACT(frequency_preferences, '$') LIKE '%weekly%'")->count(),
        ];
    }

    /**
     * Get most disabled notification types.
     */
    /**
     * @return array<string, mixed>
     */
    private function getMostDisabledTypes(): array
    {
        // This would require more complex JSON parsing in a real implementation
        return [
            'marketing' => $this->model->whereRaw("JSON_EXTRACT(type_preferences, '$.marketing.enabled') = false")->count(),
            'social' => $this->model->whereRaw("JSON_EXTRACT(type_preferences, '$.social.enabled') = false")->count(),
            'system' => $this->model->whereRaw("JSON_EXTRACT(type_preferences, '$.system.enabled') = false")->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationPreferences
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationPreferences $preferences, array $data): bool
    {
        return $preferences->update($data);
    }

    /**
     * @return Collection<int, NotificationPreferences>
     */
    public function findByDigestFrequency(string $frequency): Collection
    {
        return $this->model->where('digest_frequency', $frequency)->get();
    }

    public function findByUser(User $user): ?NotificationPreferences
    {
        return $this->findByUserId((string) $user->id);
    }
}

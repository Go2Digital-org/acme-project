<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use InvalidArgumentException;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for getting notification preferences query.
 */
final readonly class GetNotificationPreferencesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationPreferencesRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetNotificationPreferencesQuery) {
            throw new InvalidArgumentException('Expected GetNotificationPreferencesQuery');
        }

        $this->validateQuery($query);

        // Get user preferences
        $preferences = $this->repository->findByUserId((string) $query->userId);

        if (! $preferences instanceof NotificationPreferences && ! $query->includeDefaults) {
            return null;
        }

        // Build response with user preferences or defaults
        $response = [
            'user_id' => $query->userId,
            'has_custom_preferences' => $preferences instanceof NotificationPreferences,
        ];

        if ($preferences instanceof NotificationPreferences) {
            $response = array_merge($response, [
                'id' => $preferences->id,
                'preferences' => $preferences->preferences,
                'timezone' => $preferences->timezone,
                'quiet_hours' => $preferences->quiet_hours,
                'digest_frequency' => $preferences->digest_frequency,
                'metadata' => $preferences->metadata,
                'created_at' => $preferences->created_at->format('c'),
                'updated_at' => $preferences->updated_at->format('c'),
            ]);
        } elseif ($query->includeDefaults) {
            $response['preferences'] = $this->getDefaultPreferences();
            $response['timezone'] = null;
            $response['quiet_hours'] = null;
            $response['digest_frequency'] = 1; // Daily by default
            $response['metadata'] = [];
        }

        // Filter by channel if requested
        if ($query->channel !== null && isset($response['preferences'])) {
            $filteredPreferences = [];

            foreach ($response['preferences'] as $type => $channels) {
                if (isset($channels[$query->channel])) {
                    $filteredPreferences[$type] = [$query->channel => $channels[$query->channel]];
                }
            }
            $response['preferences'] = $filteredPreferences;
        }

        return $response;
    }

    /**
     * Get default notification preferences for all types and channels.
     *
     * @return array<string, mixed>
     */
    private function getDefaultPreferences(): array
    {
        $preferences = [];
        $types = NotificationType::getAllValues();
        $channels = NotificationChannel::getAllValues();

        foreach ($types as $type) {
            $preferences[$type] = [];

            foreach ($channels as $channel) {
                // Default preferences based on type and channel
                $preferences[$type][$channel] = $this->getDefaultPreference($type, $channel);
            }
        }

        return $preferences;
    }

    /**
     * Get default preference for a specific type and channel combination.
     */
    private function getDefaultPreference(string $type, string $channel): bool
    {
        // High-priority types are enabled by default for all channels
        $highPriorityTypes = ['security_alert', 'system_alert', 'payment_failed'];

        if (in_array($type, $highPriorityTypes, true)) {
            return true;
        }

        // Email is generally enabled by default for most types
        if ($channel === 'email') {
            return ! in_array($type, ['marketing', 'newsletter'], true);
        }

        // Push notifications default to enabled for important types
        if ($channel === 'push') {
            return in_array($type, [
                'donation_received',
                'campaign_milestone',
                'campaign_completed',
                'security_alert',
                'system_alert',
            ], true);
        }

        // SMS defaults to disabled except for critical alerts
        if ($channel === 'sms') {
            return in_array($type, ['security_alert', 'payment_failed'], true);
        }

        // Database notifications are always enabled
        // Default to false for other combinations
        return $channel === 'database';
    }

    /**
     * Validate the get notification preferences query.
     */
    private function validateQuery(GetNotificationPreferencesQuery $query): void
    {
        if ($query->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if ($query->channel !== null && ! in_array($query->channel, array_column(NotificationChannel::cases(), 'value'), true)) {
            throw NotificationException::unsupportedChannel($query->channel);
        }
    }
}

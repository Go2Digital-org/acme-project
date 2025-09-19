<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationPreferencesUpdatedEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\NotificationPreferences;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\Notification\Domain\ValueObject\NotificationChannel;
use Modules\Notification\Domain\ValueObject\NotificationType;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for updating notification preferences.
 *
 * Validates preference structure and updates user notification settings
 * including channel preferences, quiet hours, and digest frequency.
 */
final readonly class UpdateNotificationPreferencesCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationPreferencesRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the update notification preferences command.
     *
     * @return NotificationPreferences
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof UpdateNotificationPreferencesCommand) {
            throw new InvalidArgumentException('Expected UpdateNotificationPreferencesCommand');
        }

        $this->validateCommand($command);

        return DB::transaction(function () use ($command): NotificationPreferences {
            // Get existing preferences or create new ones
            $existingPreferences = $this->repository->findByUserId((string) $command->userId);

            $preferenceData = [
                'user_id' => $command->userId,
                'channel_preferences' => $command->preferences,
                'timezone' => $command->timezone,
                'quiet_hours' => $command->quietHours,
                'frequency_preferences' => ['digest_frequency' => $command->digestFrequency],
                'metadata' => array_merge(
                    $existingPreferences->metadata ?? [],
                    $command->metadata,
                    ['updated_at' => Carbon::now()->toIso8601String()],
                ),
            ];

            try {
                if (! $existingPreferences instanceof NotificationPreferences) {
                    $preferences = $this->repository->create($preferenceData);
                } else {
                    $this->repository->update($existingPreferences, $preferenceData);
                    $preferences = $this->repository->findByUserId((string) $command->userId);
                }

                // Ensure $preferences is properly typed
                if (! $preferences instanceof NotificationPreferences) {
                    throw new Exception('Failed to create or update notification preferences');
                }

                // Dispatch preferences updated event
                Event::dispatch(new NotificationPreferencesUpdatedEvent(
                    userId: $command->userId,
                    preferences: $preferences,
                    previousPreferences: $existingPreferences,
                    updatedAt: Carbon::now(),
                    source: 'command_handler',
                    context: array_merge($command->metadata, [
                        'command_class' => $command::class,
                        'operation' => $existingPreferences instanceof NotificationPreferences ? 'updated' : 'created',
                    ]),
                ));

                $this->logger->info('Notification preferences updated successfully', [
                    'user_id' => $command->userId,
                    'preferences_id' => $preferences->id,
                    'operation' => $existingPreferences instanceof NotificationPreferences ? 'updated' : 'created',
                    'timezone' => $command->timezone,
                    'digest_frequency' => $command->digestFrequency,
                ]);

                return $preferences;
            } catch (Exception $e) {
                $this->logger->error('Failed to update notification preferences', [
                    'user_id' => $command->userId,
                    'error' => $e->getMessage(),
                    'preferences' => $command->preferences,
                ]);

                throw NotificationException::preferenceUpdateFailed(
                    "Failed to update notification preferences for user {$command->userId}: {$e->getMessage()}",
                );
            }
        });
    }

    /**
     * Validate the update notification preferences command.
     */
    private function validateCommand(UpdateNotificationPreferencesCommand $command): void
    {
        if ($command->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if ($command->preferences === []) {
            throw NotificationException::invalidData(
                'preferences',
                'Preferences cannot be empty',
            );
        }

        // Validate preferences structure: type -> channel -> enabled
        foreach ($command->preferences as $type => $channels) {
            if (! NotificationType::isValid($type)) {
                throw NotificationException::invalidType("Invalid notification type: {$type}");
            }

            // $channels is already typed as array from the command

            foreach ($channels as $channel => $enabled) {
                if (! NotificationChannel::isValid($channel)) {
                    throw NotificationException::unsupportedChannel("Invalid channel: {$channel}");
                }

                // $enabled is already typed as bool from the command
            }
        }

        // Validate timezone if provided
        if ($command->timezone !== null) {
            try {
                new DateTimeZone($command->timezone);
            } catch (Exception) {
                throw NotificationException::invalidData(
                    'timezone',
                    "Invalid timezone: {$command->timezone}",
                );
            }
        }

        // Validate quiet hours if provided
        if ($command->quietHours !== null) {
            $this->validateQuietHours($command->quietHours);
        }

        // Validate digest frequency if provided
        if ($command->digestFrequency !== null) {
            $validFrequencies = [0, 1, 7, 30]; // 0=disabled, 1=daily, 7=weekly, 30=monthly

            if (! in_array($command->digestFrequency, $validFrequencies, true)) {
                throw NotificationException::invalidData(
                    'digest_frequency',
                    'Digest frequency must be one of: 0 (disabled), 1 (daily), 7 (weekly), 30 (monthly)',
                );
            }
        }

        // metadata is already typed as array in the command
    }

    /**
     * Validate quiet hours configuration.
     *
     * @param  array<string, mixed>  $quietHours
     */
    private function validateQuietHours(array $quietHours): void
    {
        $requiredKeys = ['start_time', 'end_time', 'timezone'];

        foreach ($requiredKeys as $key) {
            if (! isset($quietHours[$key])) {
                throw NotificationException::invalidData(
                    "quiet_hours.{$key}",
                    "Quiet hours must include {$key}",
                );
            }
        }

        // Validate time format (HH:MM)
        $timePattern = '/^([01]?\d|2[0-3]):[0-5]\d$/';

        $startTime = $quietHours['start_time'];
        if (! is_string($startTime) || ! preg_match($timePattern, $startTime)) {
            throw NotificationException::invalidData(
                'quiet_hours.start_time',
                'Start time must be in HH:MM format',
            );
        }

        $endTime = $quietHours['end_time'];
        if (! is_string($endTime) || ! preg_match($timePattern, $endTime)) {
            throw NotificationException::invalidData(
                'quiet_hours.end_time',
                'End time must be in HH:MM format',
            );
        }

        // Validate timezone
        $timezone = $quietHours['timezone'];
        if (! is_string($timezone)) {
            throw NotificationException::invalidData(
                'quiet_hours.timezone',
                'Timezone must be a string',
            );
        }

        try {
            new DateTimeZone($timezone);
        } catch (Exception) {
            throw NotificationException::invalidData(
                'quiet_hours.timezone',
                "Invalid timezone in quiet hours: {$timezone}",
            );
        }
    }
}

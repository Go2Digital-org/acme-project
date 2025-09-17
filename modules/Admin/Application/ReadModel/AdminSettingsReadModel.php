<?php

declare(strict_types=1);

namespace Modules\Admin\Application\ReadModel;

use DateTimeImmutable;

final readonly class AdminSettingsReadModel
{
    public function __construct(
        public int $id,
        public string $siteName,
        public string $siteDescription,
        public MaintenanceSettingsReadModel $maintenanceSettings,
        public bool $debugMode,
        public EmailSettingsReadModel $emailSettings,
        public NotificationSettingsReadModel $notificationSettings,
        public int $updatedBy,
        public DateTimeImmutable $updatedAt
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'site_name' => $this->siteName,
            'site_description' => $this->siteDescription,
            'maintenance_settings' => $this->maintenanceSettings->toArray(),
            'debug_mode' => $this->debugMode,
            'email_settings' => $this->emailSettings->toArray(),
            'notification_settings' => $this->notificationSettings->toArray(),
            'updated_by' => $this->updatedBy,
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}

final readonly class MaintenanceSettingsReadModel
{
    /**
     * @param  array<string>  $allowedIps
     */
    public function __construct(
        public bool $enabled,
        public ?string $message,
        public array $allowedIps,
        public ?DateTimeImmutable $scheduledAt = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'message' => $this->message,
            'allowed_ips' => $this->allowedIps,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
        ];
    }
}

final readonly class EmailSettingsReadModel
{
    public function __construct(
        public string $driver,
        public string $host,
        public int $port,
        public string $username,
        public string $fromAddress,
        public string $fromName,
        public bool $encryption
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'from_address' => $this->fromAddress,
            'from_name' => $this->fromName,
            'encryption' => $this->encryption,
        ];
    }
}

final readonly class NotificationSettingsReadModel
{
    /**
     * @param  array<string>  $channels
     * @param  array<string, mixed>  $preferences
     */
    public function __construct(
        public bool $emailNotifications,
        public bool $pushNotifications,
        public bool $slackNotifications,
        public array $channels,
        public array $preferences
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email_notifications' => $this->emailNotifications,
            'push_notifications' => $this->pushNotifications,
            'slack_notifications' => $this->slackNotifications,
            'channels' => $this->channels,
            'preferences' => $this->preferences,
        ];
    }
}

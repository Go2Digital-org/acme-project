<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use DateTimeImmutable;
use Modules\Admin\Application\ReadModel\AdminSettingsReadModel;
use Modules\Admin\Application\ReadModel\EmailSettingsReadModel;
use Modules\Admin\Application\ReadModel\MaintenanceSettingsReadModel;
use Modules\Admin\Application\ReadModel\NotificationSettingsReadModel;
use Modules\Admin\Domain\Repository\AdminSettingsRepositoryInterface;

final readonly class GetAdminSettingsQueryHandler
{
    public function __construct(
        private AdminSettingsRepositoryInterface $repository
    ) {}

    public function handle(GetAdminSettingsQuery $query): AdminSettingsReadModel
    {
        $settings = $this->repository->getSettings();

        $maintenanceSettings = new MaintenanceSettingsReadModel(
            enabled: $settings->maintenance_mode ?? false,
            message: $settings->maintenance_message,
            allowedIps: $settings->allowed_ips ?? []
        );

        $emailSettings = $this->buildEmailSettings($settings->email_settings ?? [], $query->includeSensitiveData);
        $notificationSettings = $this->buildNotificationSettings($settings->notification_settings ?? []);

        return new AdminSettingsReadModel(
            id: $settings->id,
            siteName: $settings->site_name ?? config('app.name', 'Laravel'),
            siteDescription: $settings->site_description ?? 'A Laravel application',
            maintenanceSettings: $maintenanceSettings,
            debugMode: $settings->debug_mode ?? config('app.debug', false),
            emailSettings: $emailSettings,
            notificationSettings: $notificationSettings,
            updatedBy: $settings->updated_by ?? 0,
            updatedAt: new DateTimeImmutable(
                $settings->updated_at ? $settings->updated_at->toDateTimeString() : 'now'
            )
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function buildEmailSettings(array $settings, bool $includeSensitiveData): EmailSettingsReadModel
    {
        return new EmailSettingsReadModel(
            driver: $settings['driver'] ?? config('mail.default', 'smtp'),
            host: $settings['host'] ?? config('mail.mailers.smtp.host', 'localhost'),
            port: $settings['port'] ?? config('mail.mailers.smtp.port', 587),
            username: $includeSensitiveData
                ? ($settings['username'] ?? config('mail.mailers.smtp.username', ''))
                : '***',
            fromAddress: $settings['from_address'] ?? config('mail.from.address', 'noreply@example.com'),
            fromName: $settings['from_name'] ?? config('mail.from.name', 'Laravel'),
            encryption: $settings['encryption'] ?? config('mail.mailers.smtp.encryption', 'tls') === 'tls'
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function buildNotificationSettings(array $settings): NotificationSettingsReadModel
    {
        return new NotificationSettingsReadModel(
            emailNotifications: $settings['email_notifications'] ?? true,
            pushNotifications: $settings['push_notifications'] ?? false,
            slackNotifications: $settings['slack_notifications'] ?? false,
            channels: $settings['channels'] ?? ['email'],
            preferences: $settings['preferences'] ?? [
                'campaign_created' => true,
                'donation_received' => true,
                'campaign_completed' => true,
                'system_alerts' => true,
            ]
        );
    }
}

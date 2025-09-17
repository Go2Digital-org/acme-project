<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Repository;

use Modules\Admin\Domain\Model\AdminSettings;
use Modules\Admin\Domain\Repository\AdminSettingsRepositoryInterface;

final readonly class AdminSettingsEloquentRepository implements AdminSettingsRepositoryInterface
{
    public function __construct(
        private AdminSettings $model
    ) {}

    public function getSettings(): AdminSettings
    {
        $settings = $this->model->first();

        if (! $settings) {
            return $this->createDefaultSettings();
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): AdminSettings
    {
        $settings = $this->getSettings();
        $settings->update($data);

        $freshSettings = $settings->fresh();

        return $freshSettings ?? $settings;
    }

    public function createDefaultSettings(): AdminSettings
    {
        return $this->model->create([
            'site_name' => config('app.name', 'Laravel'),
            'site_description' => 'A Laravel application for managing CSR campaigns',
            'maintenance_mode' => false,
            'maintenance_message' => null,
            'allowed_ips' => [],
            'debug_mode' => config('app.debug', false),
            'email_settings' => [
                'driver' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host', 'localhost'),
                'port' => config('mail.mailers.smtp.port', 587),
                'username' => config('mail.mailers.smtp.username', ''),
                'from_address' => config('mail.from.address', 'noreply@example.com'),
                'from_name' => config('mail.from.name', 'Laravel'),
                'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            ],
            'notification_settings' => [
                'email_notifications' => true,
                'push_notifications' => false,
                'slack_notifications' => false,
                'channels' => ['email'],
                'preferences' => [
                    'campaign_created' => true,
                    'donation_received' => true,
                    'campaign_completed' => true,
                    'system_alerts' => true,
                ],
            ],
            'updated_by' => 1, // Default system user
        ]);
    }

    /**
     * @param  array<string>|null  $allowedIps
     */
    public function toggleMaintenanceMode(bool $enabled, ?string $message = null, ?array $allowedIps = null): AdminSettings
    {
        $settings = $this->getSettings();

        if ($enabled) {
            $settings->enableMaintenanceMode($message, $allowedIps ?? []);
        } else {
            $settings->disableMaintenanceMode();
        }

        $settings->save();

        return $settings;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $site_name
 * @property string $site_description
 * @property bool $maintenance_mode
 * @property string|null $maintenance_message
 * @property array<string>|null $allowed_ips
 * @property bool $debug_mode
 * @property array<string, mixed>|null $email_settings
 * @property array<string, mixed>|null $notification_settings
 * @property int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AdminSettings extends Model
{
    protected $table = 'admin_settings';

    protected $fillable = [
        'site_name',
        'site_description',
        'maintenance_mode',
        'maintenance_message',
        'allowed_ips',
        'debug_mode',
        'email_settings',
        'notification_settings',
        'updated_by',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'debug_mode' => 'boolean',
        'email_settings' => 'array',
        'notification_settings' => 'array',
        'allowed_ips' => 'array',
    ];

    // Business logic methods
    /**
     * @param  array<string>  $allowedIps
     */
    public function enableMaintenanceMode(?string $message = null, array $allowedIps = []): void
    {
        $this->maintenance_mode = true;
        $this->maintenance_message = $message;
        $this->allowed_ips = $allowedIps;
    }

    public function disableMaintenanceMode(): void
    {
        $this->maintenance_mode = false;
        $this->maintenance_message = null;
        $this->allowed_ips = null;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateEmailSettings(array $settings): void
    {
        $this->email_settings = array_merge($this->email_settings ?? [], $settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateNotificationSettings(array $settings): void
    {
        $this->notification_settings = array_merge($this->notification_settings ?? [], $settings);
    }

    public function isInMaintenanceMode(): bool
    {
        return $this->maintenance_mode === true;
    }

    public function canAccessDuringMaintenance(string $ip): bool
    {
        if (! $this->isInMaintenanceMode()) {
            return true;
        }

        return in_array($ip, $this->allowed_ips ?? []);
    }
}

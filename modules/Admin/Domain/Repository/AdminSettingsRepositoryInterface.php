<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Repository;

use Modules\Admin\Domain\Model\AdminSettings;

interface AdminSettingsRepositoryInterface
{
    public function getSettings(): AdminSettings;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): AdminSettings;

    public function createDefaultSettings(): AdminSettings;

    /**
     * @param  array<string>|null  $allowedIps
     */
    public function toggleMaintenanceMode(bool $enabled, ?string $message = null, ?array $allowedIps = null): AdminSettings;
}

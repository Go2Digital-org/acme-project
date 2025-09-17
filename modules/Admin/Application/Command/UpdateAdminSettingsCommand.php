<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdateAdminSettingsCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $emailSettings
     * @param  array<string, mixed>  $notificationSettings
     */
    public function __construct(
        public string $siteName,
        public string $siteDescription,
        public bool $maintenanceMode,
        public bool $debugMode,
        public array $emailSettings,
        public array $notificationSettings,
        public int $updatedBy
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Admin\Application\Event\AdminSettingsUpdatedEvent;
use Modules\Admin\Domain\Model\AdminSettings;
use Modules\Admin\Domain\Repository\AdminSettingsRepositoryInterface;
use Modules\Admin\Domain\ValueObject\SystemConfiguration;

final readonly class UpdateAdminSettingsCommandHandler
{
    public function __construct(
        private AdminSettingsRepositoryInterface $repository
    ) {}

    public function handle(UpdateAdminSettingsCommand $command): AdminSettings
    {
        return DB::transaction(function () use ($command) {
            // Create value object to validate data
            $configuration = new SystemConfiguration(
                siteName: $command->siteName,
                siteDescription: $command->siteDescription,
                debugMode: $command->debugMode,
                emailSettings: $command->emailSettings,
                notificationSettings: $command->notificationSettings
            );

            $settings = $this->repository->updateSettings([
                'site_name' => $configuration->siteName,
                'site_description' => $configuration->siteDescription,
                'maintenance_mode' => $command->maintenanceMode,
                'debug_mode' => $configuration->debugMode,
                'email_settings' => $configuration->emailSettings,
                'notification_settings' => $configuration->notificationSettings,
                'updated_by' => $command->updatedBy,
            ]);

            // Dispatch domain event
            Event::dispatch(new AdminSettingsUpdatedEvent(
                settingsId: $settings->id,
                updatedBy: $command->updatedBy,
                changes: [
                    'site_name' => $configuration->siteName,
                    'debug_mode' => $configuration->debugMode,
                    'maintenance_mode' => $command->maintenanceMode,
                ]
            ));

            return $settings;
        });
    }
}

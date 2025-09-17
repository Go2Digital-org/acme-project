<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Admin\Application\Event\MaintenanceModeToggledEvent;
use Modules\Admin\Domain\Model\AdminSettings;
use Modules\Admin\Domain\Repository\AdminSettingsRepositoryInterface;
use Modules\Admin\Domain\ValueObject\MaintenanceMode;

final readonly class ToggleMaintenanceModeCommandHandler
{
    public function __construct(
        private AdminSettingsRepositoryInterface $repository
    ) {}

    public function handle(ToggleMaintenanceModeCommand $command): AdminSettings
    {
        return DB::transaction(function () use ($command) {
            // Create maintenance mode value object for validation
            $maintenanceMode = $command->enabled
                ? MaintenanceMode::enabled(
                    message: $command->message ?? 'Site is under maintenance',
                    allowedIps: $command->allowedIps ? explode(',', $command->allowedIps) : []
                )
                : MaintenanceMode::disabled();

            // Toggle Laravel's maintenance mode
            if ($command->enabled) {
                Artisan::call('down', [
                    '--message' => $maintenanceMode->message,
                    '--allow' => implode(',', $maintenanceMode->allowedIps),
                ]);
            } else {
                Artisan::call('up');
            }

            // Update database settings
            $settings = $this->repository->toggleMaintenanceMode(
                enabled: $command->enabled,
                message: $command->message,
                allowedIps: $maintenanceMode->allowedIps
            );

            // Dispatch domain event
            Event::dispatch(new MaintenanceModeToggledEvent(
                settingsId: $settings->id,
                enabled: $command->enabled,
                triggeredBy: $command->triggeredBy,
                message: $command->message
            ));

            return $settings;
        });
    }
}

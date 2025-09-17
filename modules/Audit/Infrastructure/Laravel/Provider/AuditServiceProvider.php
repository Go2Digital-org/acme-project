<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Laravel\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets\AuditActivityWidget;
use Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets\AuditStatsWidget;
use Modules\Audit\Infrastructure\Repository\EloquentAuditRepository;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            AuditRepositoryInterface::class,
            EloquentAuditRepository::class
        );

        // Update audit config to use our custom model
        config(['audit.implementation' => Audit::class]);
    }

    public function boot(): void
    {
        // Register Livewire components for Filament widgets
        // Check if Livewire is available and bound to prevent race conditions in parallel testing
        if (class_exists(Livewire::class) && $this->app->bound('livewire')) {
            try {
                Livewire::component('modules.audit.infrastructure.filament.resources.audit-resource.widgets.audit-activity-widget', AuditActivityWidget::class);
                Livewire::component('modules.audit.infrastructure.filament.resources.audit-resource.widgets.audit-stats-widget', AuditStatsWidget::class);
            } catch (Exception $e) {
                // Silently ignore in test environment if Livewire is not yet available
                // This handles race conditions in parallel testing
                if (! app()->environment('testing')) {
                    throw $e;
                }
            }
        }
    }
}

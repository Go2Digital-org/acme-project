<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Provider;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\File;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Admin\Infrastructure\Laravel\Responses\LogoutResponse;
use Modules\Shared\Infrastructure\Laravel\Middleware\BlockAdminLoginMiddleware;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use SolutionForest\FilamentTranslateField\FilamentTranslateFieldPlugin;

class TenantPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        // Register custom logout response for tenant panel
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            LogoutResponse::class,
        );
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('tenant')
            ->path('admin')
            ->login()
            ->brandName(tenant() ? tenant()->name : 'Tenant Admin')
            ->brandLogo(asset('images/acme-logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Purple,
                'gray' => Color::Slate,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('View Website')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt'),
            ]);

        // Auto-discover Filament resources from all modules
        $this->autodiscoverFilamentResources($panel);

        return $panel
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                // Core Filament widgets
                AccountWidget::class,

                // System info (keep at bottom)
                FilamentInfoWidget::class,

                // Note: Analytics module widgets are auto-discovered via autodiscoverFilamentResources()
                // This prevents duplicate registrations
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                EnvironmentIndicatorPlugin::make(),
                FilamentTranslateFieldPlugin::make()
                    ->defaultLocales(['en', 'nl', 'fr']),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                BlockAdminLoginMiddleware::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Automatically discover and register Filament resources from all modules.
     */
    protected function autodiscoverFilamentResources(Panel $panel): void
    {
        if (! $this->shouldLoadModuleResources()) {
            return;
        }

        $modulesPath = base_path('modules');

        // Auto-discover from all modules except Organization
        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $moduleName = basename((string) $moduleDirectory);

            // Skip Organization module for tenant admin
            if ($moduleName === 'Organization') {
                continue;
            }

            $resourcePath = "{$moduleDirectory}/Infrastructure/Filament/Resources";

            if (File::exists($resourcePath)) {
                $namespace = "Modules\\{$moduleName}\\Infrastructure\\Filament\\Resources";
                $panel->discoverResources(in: $resourcePath, for: $namespace);
            }
        }

        // Also check legacy app location if it exists
        if (File::exists(app_path('Filament/Resources'))) {
            $panel->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources');
        }

        // Auto-discover pages if they exist
        if (File::exists(app_path('Filament/Pages'))) {
            $panel->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages');
        }

        // Auto-discover widgets from modules except Organization
        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $moduleName = basename((string) $moduleDirectory);

            // Skip Organization module for tenant admin
            if ($moduleName === 'Organization') {
                continue;
            }

            $widgetPath = "{$moduleDirectory}/Infrastructure/Filament/Widgets";

            if (File::exists($widgetPath)) {
                $namespace = "Modules\\{$moduleName}\\Infrastructure\\Filament\\Widgets";
                $panel->discoverWidgets(in: $widgetPath, for: $namespace);
            }
        }

        // Auto-discover widgets if they exist in app
        if (File::exists(app_path('Filament/Widgets'))) {
            $panel->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets');
        }
    }

    /**
     * Determine if module resources should be loaded.
     * Only load when accessing admin panel to avoid frontend conflicts.
     */
    protected function shouldLoadModuleResources(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        $request = request();

        // Only load for admin and livewire routes
        return str_starts_with($request->path(), 'admin') ||
               str_starts_with($request->path(), 'livewire');
    }
}

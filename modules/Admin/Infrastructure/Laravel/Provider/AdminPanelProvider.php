<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Provider;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Exception;
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
use Livewire\Livewire;
use Modules\Admin\Application\Service\AdminNavigationService;
use Modules\Admin\Infrastructure\Laravel\Adapter\FilamentNavigationAdapter;
use Modules\Admin\Infrastructure\Laravel\Responses\LogoutResponse;
use Modules\CacheWarming\Infrastructure\Laravel\Middleware\CacheWarmingMiddleware;
use Modules\Organization\Infrastructure\Laravel\Middleware\InitializeTenancyForNonCentralDomains;
use Modules\Shared\Infrastructure\Laravel\Middleware\BlockAdminLoginMiddleware;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use SolutionForest\FilamentTranslateField\FilamentTranslateFieldPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        // Register custom logout response for admin panel
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            LogoutResponse::class,
        );

        // Register navigation service and adapter
        $this->app->singleton(AdminNavigationService::class);
        $this->app->singleton(FilamentNavigationAdapter::class, fn ($app) => new FilamentNavigationAdapter(
            $app->make(AdminNavigationService::class)
        ));

        // Register Livewire components for Filament resource widgets
        $this->registerResourceWidgets();
    }

    /**
     * Register all Filament resource widgets as Livewire components
     */
    protected function registerResourceWidgets(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        $modulesPath = base_path('modules');

        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $moduleName = basename((string) $moduleDirectory);
            $widgetPath = "{$moduleDirectory}/Infrastructure/Filament/Resources";

            if (! File::exists($widgetPath)) {
                continue;
            }

            // Find all widget files in resource subdirectories
            $widgetFiles = File::allFiles($widgetPath);

            foreach ($widgetFiles as $file) {
                if (! str_ends_with($file->getFilename(), 'Widget.php')) {
                    continue;
                }

                $relativePath = str_replace($widgetPath . '/', '', $file->getPath());
                $className = $file->getFilenameWithoutExtension();

                // Build the full class name
                $fullClassName = "Modules\\{$moduleName}\\Infrastructure\\Filament\\Resources\\"
                    . str_replace('/', '\\', $relativePath)
                    . '\\' . $className;

                if (class_exists($fullClassName)) {
                    // Generate Livewire component name
                    $pregResult = preg_replace('/(?<!^)[A-Z]/', '-$0', $className);
                    $componentName = 'modules.' . strtolower($moduleName) . '.infrastructure.filament.resources.'
                        . str_replace(['/', '\\'], '.', strtolower(str_replace('_', '-', $relativePath)))
                        . '.' . strtolower(str_replace('_', '-', $pregResult ?? $className));

                    try {
                        Livewire::component($componentName, $fullClassName);
                    } catch (Exception $e) {
                        // Silently fail in test environment when Livewire isn't bound
                        if (! app()->environment('testing')) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }

    public function panel(Panel $panel): Panel
    {
        // Get navigation adapter to register custom navigation items
        $navigationAdapter = app(FilamentNavigationAdapter::class);

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->brandName('ACME Corp Admin')
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
            ])
            ->navigationItems($navigationAdapter->getNavigationItems());

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
                InitializeTenancyForNonCentralDomains::class, // Add tenancy initialization
                CacheWarmingMiddleware::class, // Add cache warming
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

        // Check if we're on a tenant domain
        $currentHost = request()->getHost();
        $centralDomains = config('tenancy.central_domains', []);
        $isOnTenantDomain = ! in_array($currentHost, $centralDomains);

        // Get resources that should only be available on central domains
        $centralOnlyResources = config('tenancy.central_only_resources', []);

        // Auto-discover from all modules
        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $moduleName = basename((string) $moduleDirectory);
            $resourcePath = "{$moduleDirectory}/Infrastructure/Filament/Resources";

            if (File::exists($resourcePath)) {
                if ($isOnTenantDomain) {
                    // On tenant domain, discover resources but exclude central-only ones
                    foreach (File::files($resourcePath) as $file) {
                        if ($file->getExtension() !== 'php') {
                            continue;
                        }
                        if (str_contains($file->getFilename(), 'RelationManager')) {
                            continue;
                        }
                        $resourceClass = "Modules\\{$moduleName}\\Infrastructure\\Filament\\Resources\\" . $file->getFilenameWithoutExtension();

                        // Skip if resource is in central-only list
                        if (! in_array($resourceClass, $centralOnlyResources) && class_exists($resourceClass)) {
                            /** @var class-string $resourceClass */
                            $panel->resources([$resourceClass]);
                        }
                    }
                } else {
                    // On central domain, discover all resources normally
                    $namespace = "Modules\\{$moduleName}\\Infrastructure\\Filament\\Resources";
                    $panel->discoverResources(in: $resourcePath, for: $namespace);
                }
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

        // Auto-discover widgets from modules
        foreach (File::directories($modulesPath) as $moduleDirectory) {
            $moduleName = basename((string) $moduleDirectory);
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

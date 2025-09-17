<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Providers\HorizonServiceProvider::class,
    Laravel\Socialite\SocialiteServiceProvider::class,
    Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,

    // Core Shared Infrastructure - Order matters!
    Modules\Shared\Infrastructure\Laravel\Provider\EventBusServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\ModuleDiscoveryServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\HexagonalFactoryServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\HexagonalMigrationServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\CommandServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\SharedServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\ReadModelServiceProvider::class,

    // Domain Service Providers - Core business logic
    Modules\User\Infrastructure\Laravel\Provider\UserServiceProvider::class,
    Modules\Auth\Infrastructure\Laravel\Provider\AuthServiceProvider::class,
    Modules\Organization\Infrastructure\Laravel\Provider\OrganizationServiceProvider::class,
    Modules\Tenancy\Infrastructure\Laravel\Provider\TenancyServiceProvider::class,

    // Business Domain Modules
    Modules\Campaign\Infrastructure\Laravel\Provider\CampaignServiceProvider::class,
    Modules\Donation\Infrastructure\Laravel\Provider\DonationServiceProvider::class,
    Modules\Category\Infrastructure\Laravel\Provider\CategoryServiceProvider::class,
    Modules\Currency\Infrastructure\Laravel\Provider\CurrencyServiceProvider::class,
    Modules\Currency\Infrastructure\Laravel\Provider\ExchangeRateServiceProvider::class,

    // Support Modules
    Modules\Notification\Infrastructure\Laravel\Provider\NotificationServiceProvider::class,
    Modules\Search\Infrastructure\Laravel\Provider\SearchServiceProvider::class,
    Modules\Export\Infrastructure\Laravel\Provider\ExportServiceProvider::class,
    Modules\Audit\Infrastructure\Laravel\Provider\AuditServiceProvider::class,
    Modules\Bookmark\Infrastructure\Laravel\Provider\BookmarkServiceProvider::class,

    // Admin & Dashboard
    Modules\Admin\Infrastructure\Laravel\Provider\AdminServiceProvider::class,
    Modules\Dashboard\Infrastructure\Laravel\Provider\DashboardServiceProvider::class,

    // Infrastructure & Tools
    Modules\CacheWarming\Infrastructure\Laravel\Provider\CacheWarmingServiceProvider::class,
    Modules\DevTools\Infrastructure\Laravel\Provider\DevToolsServiceProvider::class,
    Modules\Localization\Infrastructure\Laravel\Provider\FilamentTranslationServiceProvider::class,

    // Tenancy Infrastructure - Must be after core tenancy
    Modules\Organization\Infrastructure\Laravel\Provider\LivewireTenancyServiceProvider::class,

    // UI & Presentation Layer - Load last
    Modules\Shared\Infrastructure\Laravel\Provider\BladeServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\BreadcrumbServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\ViewComponentServiceProvider::class,
    Modules\Shared\Infrastructure\Laravel\Provider\SuperSeederServiceProvider::class,
];

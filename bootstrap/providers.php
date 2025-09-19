<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider;
use Modules\Admin\Infrastructure\Laravel\Provider\AdminServiceProvider;
use Modules\Audit\Infrastructure\Laravel\Provider\AuditServiceProvider;
use Modules\Auth\Infrastructure\Laravel\Provider\AuthServiceProvider;
use Modules\Bookmark\Infrastructure\Laravel\Provider\BookmarkServiceProvider;
use Modules\CacheWarming\Infrastructure\Laravel\Provider\CacheWarmingServiceProvider;
use Modules\Campaign\Infrastructure\Laravel\Provider\CampaignServiceProvider;
use Modules\Category\Infrastructure\Laravel\Provider\CategoryServiceProvider;
use Modules\Currency\Infrastructure\Laravel\Provider\CurrencyServiceProvider;
use Modules\Currency\Infrastructure\Laravel\Provider\ExchangeRateServiceProvider;
use Modules\Dashboard\Infrastructure\Laravel\Provider\DashboardServiceProvider;
use Modules\DevTools\Infrastructure\Laravel\Provider\DevToolsServiceProvider;
use Modules\Donation\Infrastructure\Laravel\Provider\DonationServiceProvider;
use Modules\Export\Infrastructure\Laravel\Provider\ExportServiceProvider;
use Modules\Localization\Infrastructure\Laravel\Provider\FilamentTranslationServiceProvider;
use Modules\Notification\Infrastructure\Laravel\Provider\NotificationServiceProvider;
use Modules\Organization\Infrastructure\Laravel\Provider\LivewireTenancyServiceProvider;
use Modules\Organization\Infrastructure\Laravel\Provider\OrganizationServiceProvider;
use Modules\Search\Infrastructure\Laravel\Provider\SearchServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\BladeServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\BreadcrumbServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\CommandServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\EventBusServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\HexagonalFactoryServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\HexagonalMigrationServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\ModuleDiscoveryServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\SharedServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\SuperSeederServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\ViewComponentServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Providers\HorizonServiceProvider;
use Modules\Shared\Infrastructure\Laravel\ReadModelServiceProvider;
use Modules\Tenancy\Infrastructure\Laravel\Provider\TenancyServiceProvider;
use Modules\User\Infrastructure\Laravel\Provider\UserServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    SocialiteServiceProvider::class,
    LaravelLocalizationServiceProvider::class,

    // Core Shared Infrastructure - Order matters!
    EventBusServiceProvider::class,
    ModuleDiscoveryServiceProvider::class,
    HexagonalFactoryServiceProvider::class,
    HexagonalMigrationServiceProvider::class,
    CommandServiceProvider::class,
    SharedServiceProvider::class,
    ReadModelServiceProvider::class,

    // Domain Service Providers - Core business logic
    UserServiceProvider::class,
    AuthServiceProvider::class,
    OrganizationServiceProvider::class,
    TenancyServiceProvider::class,

    // Business Domain Modules
    CampaignServiceProvider::class,
    DonationServiceProvider::class,
    CategoryServiceProvider::class,
    CurrencyServiceProvider::class,
    ExchangeRateServiceProvider::class,

    // Support Modules
    NotificationServiceProvider::class,
    SearchServiceProvider::class,
    ExportServiceProvider::class,
    AuditServiceProvider::class,
    BookmarkServiceProvider::class,

    // Admin & Dashboard
    AdminServiceProvider::class,
    DashboardServiceProvider::class,

    // Infrastructure & Tools
    CacheWarmingServiceProvider::class,
    DevToolsServiceProvider::class,
    FilamentTranslationServiceProvider::class,

    // Tenancy Infrastructure - Must be after core tenancy
    LivewireTenancyServiceProvider::class,

    // UI & Presentation Layer - Load last
    BladeServiceProvider::class,
    BreadcrumbServiceProvider::class,
    ViewComponentServiceProvider::class,
    SuperSeederServiceProvider::class,
];

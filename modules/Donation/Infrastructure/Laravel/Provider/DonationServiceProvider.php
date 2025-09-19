<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Donation\Application\ReadModel\DonationSummaryReadModelBuilder;
use Modules\Donation\Application\Service\CampaignDonationService;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\Repository\PaymentAttemptRepositoryInterface;
use Modules\Donation\Domain\Repository\PaymentGatewayRepositoryInterface;
use Modules\Donation\Domain\Repository\PaymentRepositoryInterface;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Infrastructure\Gateway\MockPaymentGateway;
use Modules\Donation\Infrastructure\Laravel\Observers\DonationCountObserver;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationEloquentRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationExportRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationRelationRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\PaymentAttemptEloquentRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\PaymentEloquentRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\PaymentGatewayRepository;
use Modules\Shared\Domain\Export\DonationExportRepositoryInterface;
use Modules\Shared\Domain\Repository\DonationRelationRepositoryInterface;
use Modules\Shared\Domain\Service\CampaignDonationServiceInterface;

class DonationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DonationRepositoryInterface::class,
            DonationEloquentRepository::class,
        );

        $this->app->bind(
            PaymentGatewayRepositoryInterface::class,
            PaymentGatewayRepository::class,
        );

        $this->app->bind(
            DonationExportRepositoryInterface::class,
            DonationExportRepository::class,
        );

        $this->app->bind(
            DonationRelationRepositoryInterface::class,
            DonationRelationRepository::class,
        );

        $this->app->bind(
            CampaignDonationServiceInterface::class,
            CampaignDonationService::class,
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentEloquentRepository::class,
        );

        $this->app->bind(
            PaymentAttemptRepositoryInterface::class,
            PaymentAttemptEloquentRepository::class,
        );

        // Bind PaymentGatewayInterface to MockPaymentGateway for testing
        // In production, this would be bound to a real gateway based on configuration
        $this->app->bind(
            PaymentGatewayInterface::class,
            MockPaymentGateway::class,
        );

        // Register read model builders
        $this->app->singleton(DonationSummaryReadModelBuilder::class);
    }

    public function boot(): void
    {
        // Register model observers for maintaining donation counts
        Donation::observe(DonationCountObserver::class);

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');
    }
}

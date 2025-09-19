<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Application\Event\CampaignActivatedEvent;
use Modules\Campaign\Application\Event\CampaignCompletedEvent;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Application\Event\DonationCreatedEvent;
use Modules\Donation\Application\Event\DonationFailedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Application\Command\MarkNotificationAsReadCommandHandler;
use Modules\Notification\Application\Command\SendNotificationCommandHandler;
use Modules\Notification\Application\Query\GetNotificationMetricsQueryHandler;
use Modules\Notification\Application\Query\GetNotificationMetricsQueryHandlerInterface;
use Modules\Notification\Application\Query\GetUnreadNotificationCountQueryHandler;
use Modules\Notification\Application\Query\GetUserNotificationsQueryHandler;
use Modules\Notification\Application\Service\NotificationDeliveryService;
use Modules\Notification\Application\Service\NotificationService;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Domain\Event\NotificationFailedEvent;
use Modules\Notification\Domain\Event\NotificationReadEvent;
use Modules\Notification\Domain\Event\NotificationSentEvent;
use Modules\Notification\Domain\Repository\NotificationPreferencesRepositoryInterface;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Infrastructure\Broadcasting\BroadcastingCommandsProvider;
use Modules\Notification\Infrastructure\Broadcasting\BroadcastingServiceProvider;
use Modules\Notification\Infrastructure\Laravel\Listeners\BroadcastNotificationSentListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\CampaignActivatedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\CampaignCompletedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\CampaignCreatedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\DonationCompletedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\DonationFailedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\DonationReceivedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\HandleNotificationFailureListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\LogNotificationCreatedListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\OrganizationVerifiedNotificationListener;
use Modules\Notification\Infrastructure\Laravel\Listeners\UpdateNotificationMetricsListener;
use Modules\Notification\Infrastructure\Laravel\Repository\NotificationEloquentRepository;
use Modules\Notification\Infrastructure\Laravel\Repository\NotificationPreferencesEloquentRepository;
use Modules\Notification\Infrastructure\Laravel\Service\NotificationPerformanceMonitor;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;

/**
 * Service provider for the Notification domain module.
 *
 * Registers all notification-related services, repositories, and configurations
 * following hexagonal architecture principles.
 */
final class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration from main config file
        $this->mergeConfigFrom(config_path('notification.php'), 'notification');

        // Bind repository interfaces to implementations
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationEloquentRepository::class,
        );

        $this->app->bind(
            NotificationPreferencesRepositoryInterface::class,
            NotificationPreferencesEloquentRepository::class,
        );

        // Register infrastructure services
        $this->app->singleton(NotificationPerformanceMonitor::class);

        // Register command handlers
        $this->app->bind(CreateNotificationCommandHandler::class);
        $this->app->bind(MarkNotificationAsReadCommandHandler::class);
        $this->app->bind(SendNotificationCommandHandler::class);

        // Register query handlers
        $this->app->bind(GetUserNotificationsQueryHandler::class);
        $this->app->bind(GetUnreadNotificationCountQueryHandler::class);

        // Register application services
        $this->app->bind(NotificationDeliveryService::class);
        $this->app->singleton(NotificationService::class);

        // Register API Platform processors - bind the notification module's query handler
        $this->app->bind(
            GetNotificationMetricsQueryHandlerInterface::class,
            GetNotificationMetricsQueryHandler::class
        );

        // Register console commands when running in console
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Routes are handled by API Platform, no need to load traditional routes

        // Register event listeners
        $this->registerEventListeners();

        // Register broadcasting services
        $this->registerBroadcastingServices();

        // Configuration is already in the config directory, no publishing needed
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            NotificationRepositoryInterface::class,
            NotificationPreferencesRepositoryInterface::class,
            NotificationService::class,
            NotificationDeliveryService::class,
            NotificationPerformanceMonitor::class,
            CreateNotificationCommandHandler::class,
            MarkNotificationAsReadCommandHandler::class,
            SendNotificationCommandHandler::class,
            GetUserNotificationsQueryHandler::class,
            GetUnreadNotificationCountQueryHandler::class,
        ];
    }

    /**
     * Register domain event listeners.
     */
    private function registerEventListeners(): void
    {
        $events = $this->app->make('events');

        // Register event listeners for notification domain events
        $events->listen(
            NotificationCreatedEvent::class,
            LogNotificationCreatedListener::class,
        );

        $events->listen(
            NotificationSentEvent::class,
            BroadcastNotificationSentListener::class,
        );

        $events->listen(
            NotificationReadEvent::class,
            UpdateNotificationMetricsListener::class,
        );

        $events->listen(
            NotificationFailedEvent::class,
            HandleNotificationFailureListener::class,
        );

        // Register listeners for events from other domain modules
        $this->registerCrossDomainEventListeners();
    }

    /**
     * Register event listeners for events from other domain modules.
     */
    private function registerCrossDomainEventListeners(): void
    {
        // Skip cross-domain event listeners during unit testing to prevent database access
        if ($this->app->environment('testing') &&
            config('app.unit_testing_mode', false)) {
            return;
        }

        $events = $this->app->make('events');

        // Listen to campaign events
        $events->listen(
            CampaignCreatedEvent::class,
            CampaignCreatedNotificationListener::class,
        );

        $events->listen(
            CampaignActivatedEvent::class,
            CampaignActivatedNotificationListener::class,
        );

        $events->listen(
            CampaignCompletedEvent::class,
            CampaignCompletedNotificationListener::class,
        );

        // Listen to donation events
        $events->listen(
            DonationCreatedEvent::class,
            DonationReceivedNotificationListener::class,
        );

        $events->listen(
            DonationCompletedEvent::class,
            DonationCompletedNotificationListener::class,
        );

        $events->listen(
            DonationFailedEvent::class,
            DonationFailedNotificationListener::class,
        );

        // Listen to organization events
        $events->listen(
            OrganizationVerifiedEvent::class,
            OrganizationVerifiedNotificationListener::class,
        );

    }

    /**
     * Register broadcasting services and commands.
     */
    private function registerBroadcastingServices(): void
    {
        // Register the broadcasting service provider
        $this->app->register(BroadcastingServiceProvider::class);

        // Register the broadcasting commands provider
        $this->app->register(BroadcastingCommandsProvider::class);
    }
}

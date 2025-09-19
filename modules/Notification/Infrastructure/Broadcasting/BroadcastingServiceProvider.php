<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Application\Event\CampaignActivatedEvent;
use Modules\Campaign\Application\Event\CampaignCompletedEvent;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Donation\Application\Event\DonationFailedEvent;
use Modules\Donation\Application\Event\DonationProcessedEvent;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Infrastructure\Broadcasting\Listeners\CampaignBroadcastListener;
use Modules\Notification\Infrastructure\Broadcasting\Listeners\DonationBroadcastListener;
use Modules\Notification\Infrastructure\Broadcasting\Listeners\NotificationBroadcastListener;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;

/**
 * Service provider for notification broadcasting infrastructure.
 *
 * Registers broadcasting services, listeners, and event mappings for
 * real-time notification delivery through WebSocket channels.
 */
class BroadcastingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadChannelDefinitions();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerBroadcastingServices();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            NotificationBroadcaster::class,
        ];
    }

    /**
     * Register broadcasting services.
     */
    private function registerBroadcastingServices(): void
    {
        $this->app->singleton(NotificationBroadcaster::class, fn ($app): NotificationBroadcaster => new NotificationBroadcaster(
            logger: $app['log'],
        ));
    }

    /**
     * Register event listeners for broadcasting.
     */
    private function registerEventListeners(): void
    {
        // Notification broadcasting
        Event::listen(
            NotificationCreatedEvent::class,
            NotificationBroadcastListener::class,
        );

        // Donation event broadcasting
        Event::listen(
            DonationProcessedEvent::class,
            [DonationBroadcastListener::class, 'handleDonationProcessed'],
        );

        Event::listen(
            DonationFailedEvent::class,
            [DonationBroadcastListener::class, 'handlePaymentFailed'],
        );

        // Campaign event broadcasting
        Event::listen(
            CampaignCreatedEvent::class,
            [CampaignBroadcastListener::class, 'handleCampaignCreated'],
        );

        Event::listen(
            CampaignActivatedEvent::class,
            [CampaignBroadcastListener::class, 'handleCampaignActivated'],
        );

        Event::listen(
            CampaignCompletedEvent::class,
            [CampaignBroadcastListener::class, 'handleCampaignCompleted'],
        );
    }

    /**
     * Load channel definitions for broadcasting authorization.
     */
    private function loadChannelDefinitions(): void
    {
        // Channel definitions are loaded in routes/channels.php
        // This method is here for potential future custom channel loading
    }
}

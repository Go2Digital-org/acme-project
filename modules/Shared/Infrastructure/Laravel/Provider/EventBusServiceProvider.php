<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Domain\Event\DomainEventInterface;
use Modules\Shared\Domain\Event\EventBusInterface;
use Modules\Shared\Infrastructure\Laravel\Event\LaravelEventBus;
use Modules\Shared\Infrastructure\Laravel\Listeners\CampaignEventListener;
use Modules\Shared\Infrastructure\Laravel\Listeners\DonationEventListener;
use Modules\Shared\Infrastructure\Laravel\Listeners\OrganizationEventListener;
use Psr\Log\LoggerInterface;

/**
 * Service Provider for Event Bus and Domain Event Infrastructure
 *
 * This provider registers the event bus implementation and sets up
 * event listeners for cross-module communication.
 */
class EventBusServiceProvider extends ServiceProvider
{
    /**
     * Event to listener mappings for automatic registration
     *
     * @var array<string, mixed>
     */
    private array $eventListeners = [
        // Organization Events
        'organization.created' => [
            OrganizationEventListener::class . '@handle',
        ],
        'organization.verified' => [
            OrganizationEventListener::class . '@handle',
        ],
        'organization.activated' => [
            OrganizationEventListener::class . '@handle',
        ],
        'organization.deactivated' => [
            OrganizationEventListener::class . '@handle',
        ],

        // Campaign Events
        'campaign.created' => [
            CampaignEventListener::class . '@handle',
        ],
        'campaign.activated' => [
            CampaignEventListener::class . '@handle',
        ],
        'campaign.completed' => [
            CampaignEventListener::class . '@handle',
        ],
        'campaign.closed' => [
            CampaignEventListener::class . '@handle',
        ],
        'campaign.donation_received' => [
            CampaignEventListener::class . '@handle',
        ],

        // Donation Events
        'donation.created' => [
            DonationEventListener::class . '@handle',
        ],
        'donation.completed' => [
            DonationEventListener::class . '@handle',
        ],
        'donation.failed' => [
            DonationEventListener::class . '@handle',
        ],
        'donation.refunded' => [
            DonationEventListener::class . '@handle',
        ],
        'donation.cancelled' => [
            DonationEventListener::class . '@handle',
        ],
    ];

    /**
     * Register services
     */
    public function register(): void
    {
        // Bind EventBusInterface to Laravel implementation
        $this->app->singleton(fn ($app): EventBusInterface => new LaravelEventBus(
            dispatcher: $app->make(Dispatcher::class),
            logger: $app->make(LoggerInterface::class)
        ));

        // Register event listeners as singletons for better performance
        $this->app->singleton(OrganizationEventListener::class);
        $this->app->singleton(CampaignEventListener::class);
        $this->app->singleton(DonationEventListener::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register event listeners with Laravel's event dispatcher
        $this->registerEventListeners();

        // Register queue configuration for domain events
        $this->registerQueueConfiguration();

        // Set up event bus subscriptions
        $this->setupEventBusSubscriptions();
    }

    /**
     * Register event listeners with Laravel's event system
     */
    private function registerEventListeners(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);

        foreach ($this->eventListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                // Convert 'Class@method' format to proper callable
                if (str_contains((string) $listener, '@')) {
                    [$class, $method] = explode('@', (string) $listener);
                    $dispatcher->listen($eventName, [$class, $method]);
                } elseif (class_exists($listener)) {
                    // Ensure it's a valid class string
                    $dispatcher->listen($eventName, $listener);
                }
            }
        }
    }

    /**
     * Register queue configuration for domain events
     */
    private function registerQueueConfiguration(): void
    {
        // Register queue names for domain event processing
        $queueNames = [
            'domain-events' => 'Main domain event processing queue',
            'organization-events' => 'Organization-specific event processing',
            'campaign-events' => 'Campaign-specific event processing',
            'donation-events' => 'Donation-specific event processing',
            'event-handlers' => 'General event handler processing',
        ];

        // Add configuration to the queue manager
        foreach (array_keys($queueNames) as $queueName) {
            config([
                "queue.connections.database.queues.{$queueName}" => [
                    'driver' => 'database',
                    'table' => 'jobs',
                    'queue' => $queueName,
                    'retry_after' => 90,
                    'after_commit' => true,
                ],
            ]);
        }
    }

    /**
     * Set up additional event bus subscriptions
     */
    private function setupEventBusSubscriptions(): void
    {
        $eventBus = $this->app->make(EventBusInterface::class);

        // Register additional cross-cutting concerns
        $eventBus->subscribe('*', function ($event): void {
            // Only log domain events, not all Laravel events
            if ($event instanceof DomainEventInterface) {
                $this->app->make(LoggerInterface::class)->debug('Domain event processed', [
                    'event_name' => $event->getEventName(),
                    'context' => $event->getContext(),
                    'aggregate_id' => $event->getAggregateId(),
                    'processing_time' => microtime(true),
                ]);
            }
        });

        // Subscribe to all organization events for analytics
        foreach (['organization.created', 'organization.verified', 'organization.activated'] as $eventName) {
            $eventBus->subscribe($eventName, function ($event): void {
                // This could trigger analytics updates, metrics collection, etc.
                $this->app->make(LoggerInterface::class)->info('Organization analytics trigger', [
                    'event' => $event->getEventName(),
                    'organization_id' => $event->getAggregateId(),
                ]);
            });
        }

        // Subscribe to campaign milestone events
        foreach (['campaign.created', 'campaign.completed', 'campaign.closed'] as $eventName) {
            $eventBus->subscribe($eventName, function ($event): void {
                // This could trigger reporting, milestone tracking, etc.
                $this->app->make(LoggerInterface::class)->info('Campaign milestone reached', [
                    'event' => $event->getEventName(),
                    'campaign_id' => $event->getAggregateId(),
                ]);
            });
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            EventBusInterface::class,
            OrganizationEventListener::class,
            CampaignEventListener::class,
            DonationEventListener::class,
        ];
    }
}

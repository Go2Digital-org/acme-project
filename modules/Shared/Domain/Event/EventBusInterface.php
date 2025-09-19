<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Event;

/**
 * Event Bus interface for publishing and handling domain events across modules
 *
 * This interface provides a contract for event bus implementations that enable
 * decoupled communication between bounded contexts in the hexagonal architecture.
 */
interface EventBusInterface
{
    /**
     * Publish a domain event to all registered handlers
     *
     * @param  DomainEventInterface  $event  The domain event to publish
     */
    public function publish(DomainEventInterface $event): void;

    /**
     * Publish multiple domain events in a batch
     *
     * @param  array<string, mixed>  $events  Array of domain events to publish
     */
    public function publishMany(array $events): void;

    /**
     * Publish an event asynchronously (queued)
     *
     * @param  DomainEventInterface  $event  The domain event to publish asynchronously
     */
    public function publishAsync(DomainEventInterface $event): void;

    /**
     * Register an event handler for a specific event
     *
     * @param  string  $eventName  The name of the event to handle
     * @param  callable|string  $handler  The handler callable or class name
     */
    public function subscribe(string $eventName, callable|string $handler): void;

    /**
     * Remove all handlers for a specific event
     *
     * @param  string  $eventName  The name of the event to unsubscribe from
     */
    public function unsubscribe(string $eventName): void;

    /**
     * Get all registered handlers for debugging purposes
     *
     * @return array<string, mixed> Map of event names to their handlers
     */
    public function getHandlers(): array;
}

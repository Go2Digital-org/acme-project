<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Event;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;
use Modules\Shared\Domain\Event\DomainEventInterface;
use Modules\Shared\Domain\Event\EventBusInterface;
use Modules\Shared\Infrastructure\Laravel\Jobs\ProcessDomainEventJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Laravel implementation of the Event Bus
 *
 * This implementation uses Laravel's event system as the underlying mechanism
 * for publishing and handling domain events across modules.
 */
class LaravelEventBus implements EventBusInterface
{
    /** @var array<string, array<callable|string>> */
    private array $handlers = [];

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Publish a domain event to all registered handlers
     */
    public function publish(DomainEventInterface $event): void
    {
        try {
            $this->logger->debug('Publishing domain event', [
                'event_name' => $event->getEventName(),
                'event_context' => $event->getContext(),
                'aggregate_id' => $event->getAggregateId(),
                'occurred_at' => $event->getOccurredAt()->format('Y-m-d H:i:s.u'),
            ]);

            // Dispatch through Laravel's event system
            $this->dispatcher->dispatch($event->getEventName(), [$event]);

            // Also dispatch to any manually registered handlers
            $this->dispatchToHandlers($event);

        } catch (Throwable $exception) {
            $this->logger->error('Failed to publish domain event', [
                'event_name' => $event->getEventName(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Publish multiple domain events in a batch
     */
    public function publishMany(array $events): void
    {
        foreach ($events as $event) {
            if (! $event instanceof DomainEventInterface) {
                throw new InvalidArgumentException('All events must implement DomainEventInterface');
            }

            $this->publish($event);
        }
    }

    /**
     * Publish an event asynchronously using Laravel's queue system
     */
    public function publishAsync(DomainEventInterface $event): void
    {
        try {
            $this->logger->debug('Publishing domain event asynchronously', [
                'event_name' => $event->getEventName(),
                'event_context' => $event->getContext(),
                'aggregate_id' => $event->getAggregateId(),
            ]);

            // Dispatch through queue for async processing
            ProcessDomainEventJob::dispatch($event)
                ->onQueue('domain-events')
                ->delay(now()->addSeconds(1)); // Small delay to ensure transaction commits

        } catch (Throwable $exception) {
            $this->logger->error('Failed to publish domain event asynchronously', [
                'event_name' => $event->getEventName(),
                'error' => $exception->getMessage(),
            ]);

            // Fallback to synchronous processing
            $this->publish($event);
        }
    }

    /**
     * Register an event handler for a specific event
     */
    public function subscribe(string $eventName, callable|string $handler): void
    {
        if (! isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }

        $this->handlers[$eventName][] = $handler;

        // Also register with Laravel's event dispatcher
        if (is_string($handler)) {
            $this->dispatcher->listen($eventName, $handler);
        }
    }

    /**
     * Remove all handlers for a specific event
     */
    public function unsubscribe(string $eventName): void
    {
        unset($this->handlers[$eventName]);

        // Remove from Laravel's event dispatcher
        $this->dispatcher->forget($eventName);

        $this->logger->debug('Unsubscribed from event', [
            'event_name' => $eventName,
        ]);
    }

    /**
     * Get all registered handlers for debugging purposes
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Dispatch event to manually registered handlers
     */
    private function dispatchToHandlers(DomainEventInterface $event): void
    {
        $eventName = $event->getEventName();

        if (! isset($this->handlers[$eventName])) {
            return;
        }

        foreach ($this->handlers[$eventName] as $handler) {
            try {
                if (is_callable($handler)) {
                    $handler($event);

                    continue;
                }
                if (! is_string($handler)) {
                    continue;
                }
                if (! class_exists($handler)) {
                    continue;
                }

                $handlerInstance = app($handler);

                if ($handlerInstance instanceof ShouldQueue) {
                    // Queue the handler if it implements ShouldQueue
                    dispatch(function () use ($handlerInstance, $event): void {
                        if (method_exists($handlerInstance, 'handle')) {
                            $handlerInstance->handle($event);
                        }
                    })->onQueue('event-handlers');

                    continue;
                }

                if (method_exists($handlerInstance, 'handle')) {
                    $handlerInstance->handle($event); // @phpstan-ignore-line
                }
            } catch (Throwable $exception) {
                $this->logger->error('Event handler failed', [
                    'event_name' => $eventName,
                    'handler' => is_callable($handler) ? 'callable' : $handler,
                    'error' => $exception->getMessage(),
                ]);

                // Continue processing other handlers even if one fails
                continue;
            }
        }
    }
}

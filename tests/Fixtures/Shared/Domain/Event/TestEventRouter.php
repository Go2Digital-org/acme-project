<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Domain\Event;

use Modules\Shared\Domain\Event\DomainEventInterface;

/**
 * Event Router for testing filtering and routing
 */
class TestEventRouter
{
    private array $handlers = [];

    /**
     * Register an event handler for a specific event type.
     *
     * @param  callable(DomainEventInterface): void  $handler
     */
    public function register(string $eventType, callable $handler): void
    {
        $this->handlers[$eventType] = $handler;
    }

    public function route(DomainEventInterface $event): void
    {
        $eventType = $event->getEventName();
        if (isset($this->handlers[$eventType])) {
            $this->handlers[$eventType]($event);
        }
    }

    /**
     * @return array<string, callable>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}

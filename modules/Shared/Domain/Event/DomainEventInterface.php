<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Event;

use DateTimeImmutable;

/**
 * Domain Event interface for all events in the system
 *
 * This interface ensures all domain events have consistent structure and metadata
 * for proper event handling across bounded contexts.
 */
interface DomainEventInterface
{
    /**
     * Get the unique name/type of this event
     *
     * @return string Event name in dot notation (e.g., 'organization.created')
     */
    public function getEventName(): string;

    /**
     * Get when this event occurred
     *
     * @return DateTimeImmutable Timestamp when the event was created
     */
    public function getOccurredAt(): DateTimeImmutable;

    /**
     * Get the event data as an associative array
     *
     * This method should return all relevant data needed by event handlers
     * to process the event without requiring access to the original domain object.
     *
     * @return array<string, mixed> Event data suitable for serialization
     */
    public function getEventData(): array;

    /**
     * Get the aggregate ID that this event relates to
     *
     * @return string|int|null The ID of the aggregate root that generated this event
     */
    public function getAggregateId(): string|int|null;

    /**
     * Get the version/sequence number of this event for the aggregate
     *
     * @return int Event version for optimistic concurrency control
     */
    public function getEventVersion(): int;

    /**
     * Get the context/module that generated this event
     *
     * @return string The bounded context name (e.g., 'Organization', 'Campaign')
     */
    public function getContext(): string;

    /**
     * Check if this event should be processed asynchronously
     *
     * @return bool True if the event can be queued for async processing
     */
    public function isAsync(): bool;
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Event;

use Modules\Notification\Domain\Model\Notification;
use Modules\Shared\Application\Event\AbstractDomainEvent;
use Throwable;

/**
 * Domain event fired when a notification fails to be delivered.
 */
class NotificationFailedEvent extends AbstractDomainEvent
{
    /**
     * @param  array<string, mixed>  $failureContext
     */
    public function __construct(
        public Notification $notification,
        public string $failureReason,
        public ?Throwable $exception = null,
        /** @var array<string, mixed> */
        public array $failureContext = [],
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'notification.failed';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'notifiable_id' => $this->notification->notifiable_id,
            'type' => $this->notification->type,
            'channel' => $this->notification->channel,
            'failure_reason' => $this->failureReason,
            'exception_class' => $this->exception instanceof Throwable ? $this->exception::class : null,
            'exception_message' => $this->exception?->getMessage(),
            'failure_context' => $this->failureContext,
            'failed_at' => $this->getOccurredAt()->format('c'),
        ];
    }

    /**
     * Get priority for logging this failure.
     */
    public function getLogPriority(): string
    {
        return $this->notification->isHighPriority() ? 'error' : 'warning';
    }

    public function getAggregateId(): string|int|null
    {
        return $this->notification->id;
    }

    public function getEventVersion(): int
    {
        return 1;
    }

    public function getContext(): string
    {
        return 'Notification';
    }

    public function isAsync(): bool
    {
        return true;
    }
}

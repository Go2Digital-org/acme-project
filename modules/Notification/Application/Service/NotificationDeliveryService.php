<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Service;

use Modules\Notification\Domain\Model\Notification;

/**
 * Service for delivering notifications through various channels.
 */
class NotificationDeliveryService
{
    /**
     * Deliver a notification through its configured channel.
     *
     * @param  array<string, mixed>  $options
     */
    public function deliver(Notification $notification, array $options = []): DeliveryResult
    {
        // This is a simplified implementation
        // In a real system, you'd have different delivery strategies

        return new DeliveryResult(
            successful: true,
            channel: $notification->channel ?? 'unknown',
            metadata: $options,
        );
    }

    /**
     * Send a notification through its configured channel (alias for deliver).
     *
     * @param  array<string, mixed>  $options
     */
    public function send(Notification $notification, array $options = []): DeliveryResult
    {
        return $this->deliver($notification, $options);
    }
}

/**
 * Result of a notification delivery attempt.
 */
final readonly class DeliveryResult
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $errorContext
     */
    public function __construct(
        private bool $successful,
        private string $channel,
        /** @var array<string, mixed> */
        private array $metadata = [],
        private string $errorMessage = '',
        /** @var array<string, mixed> */
        private array $errorContext = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorContext(): array
    {
        return $this->errorContext;
    }
}

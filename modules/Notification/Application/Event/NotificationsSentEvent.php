<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Event;

/**
 * Event fired when multiple notifications are sent successfully.
 */
class NotificationsSentEvent
{
    /**
     * @param  array<string>  $notificationIds
     * @param  array<string>  $channels
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly array $notificationIds,
        public readonly array $channels,
        public readonly array $metadata = [],
    ) {}

    /**
     * @return array<string>
     */
    public function getNotificationIds(): array
    {
        return $this->notificationIds;
    }

    /**
     * @return array<string>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get the total number of notifications sent.
     */
    public function getCount(): int
    {
        return count($this->notificationIds);
    }
}

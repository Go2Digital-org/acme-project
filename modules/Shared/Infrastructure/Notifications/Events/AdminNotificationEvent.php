<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $channelName,
        public readonly string $event,
        /** @var array<string, mixed> */
        public readonly array $data,
        public readonly bool $isPrivate = false,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel|PrivateChannel
    {
        if ($this->isPrivate) {
            return new PrivateChannel($this->channelName);
        }

        return new Channel($this->channelName);
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return $this->event;
    }

    /**
     * Get the data to broadcast.
     */
    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge($this->data, [
            'timestamp' => now()->toISOString(),
            'channel' => $this->channelName,
        ]);
    }

    /**
     * The queue connection to use when broadcasting.
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldBroadcast(): bool
    {
        // Don't broadcast test events in production
        return ! (app()->environment('production') && str_contains($this->event, 'test'));
    }
}

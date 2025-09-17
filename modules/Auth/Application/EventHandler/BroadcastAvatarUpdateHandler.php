<?php

declare(strict_types=1);

namespace Modules\Auth\Application\EventHandler;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Domain\Event\AvatarUpdatedEvent;

final readonly class BroadcastAvatarUpdateHandler
{
    public function handle(AvatarUpdatedEvent $event): void
    {
        try {
            // For now, we'll log the event
            // In production, this would broadcast via WebSocket/Pusher
            Log::info('Avatar updated for user', [
                'user_id' => $event->getUserId(),
                'photo_url' => $event->getPhotoUrl(),
                'timestamp' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
            ]);

            // Broadcast to user's private channel (when WebSocket is configured)
            // broadcast(new AvatarUpdatedBroadcast($event))->toOthers();

            // For now, we'll rely on frontend polling or page refresh
            // The avatar component handles updates via JavaScript events
        } catch (Exception $e) {
            Log::error('Failed to broadcast avatar update', [
                'user_id' => $event->getUserId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

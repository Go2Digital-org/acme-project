<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\NotificationHelpers;
use Modules\User\Infrastructure\Laravel\Models\User;

class ListNotificationsController
{
    use AuthenticatedUserTrait;
    use NotificationHelpers;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Fetch user's notifications for navigation dropdown.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUserOrNull($request);

        if (! $user instanceof User) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        // Get the last 5 unread notifications
        $notifications = $this->notificationService->getRecentUnreadNotifications($user, 5)
            ->map(function ($notification): array {
                /** @var DatabaseNotification $notification */
                $type = $notification->getAttribute('type');
                $createdAt = $notification->getAttribute('created_at');

                return [
                    'id' => $notification->getAttribute('id'),
                    'type' => $this->getNotificationTypeLabel((string) $type),
                    'title' => $this->getNotificationTitle($notification),
                    'message' => $this->getNotificationMessage($notification),
                    'created_at' => $createdAt,
                    'time_ago' => $this->getTimeAgo($createdAt),
                    'icon_color' => $this->getNotificationIconColor((string) $type),
                    'url' => $this->getNotificationUrl($notification),
                ];
            });

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
}

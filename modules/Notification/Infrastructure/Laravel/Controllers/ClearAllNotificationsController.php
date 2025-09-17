<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Service\NotificationService;

/**
 * Controller for marking all notifications as read.
 */
final class ClearAllNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $clearedCount = $this->notificationService->markAllAsRead((string) $user->id);

        return response()->json([
            'success' => true,
            'message' => "Marked {$clearedCount} notifications as read",
            'cleared_count' => $clearedCount,
            'unread_count' => 0,
        ]);
    }
}

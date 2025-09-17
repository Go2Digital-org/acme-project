<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Service\NotificationService;

/**
 * Controller for listing user notifications.
 *
 * Handles HTTP requests for retrieving notifications with pagination,
 * filtering, and real-time updates for the frontend dropdown.
 */
final class ListNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Get paginated notifications for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(5, (int) ($request->get('per_page', $request->get('itemsPerPage', 20)))));
        $status = $request->get('status');
        $type = $request->get('type');
        $readAt = $request->get('read_at');

        $notifications = $this->notificationService->getUserNotifications(
            userId: (string) $user->id,
            page: $page,
            perPage: $perPage,
            status: $status,
            type: $type,
            readAt: $readAt,
        );

        $unreadCount = $this->notificationService->getUnreadCount((string) $user->id);

        return response()->json([
            'notifications' => $notifications->items(),
            'unread_count' => $unreadCount,
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'has_more_pages' => $notifications->hasMorePages(),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Application\Service\NotificationService;
use Modules\Notification\Domain\Exception\NotificationException;

/**
 * Controller for marking notifications as read.
 */
final class MarkNotificationAsReadController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Mark a specific notification as read.
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $success = $this->notificationService->markAsRead($id, (string) $user->id);

            if ($success) {
                $unreadCount = $this->notificationService->getUnreadCount((string) $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'unread_count' => $unreadCount,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
            ], 422);
        } catch (NotificationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

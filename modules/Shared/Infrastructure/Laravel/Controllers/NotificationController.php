<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $itemsPerPage = (int) ($request->get('itemsPerPage', 20));
        $query = $user->notifications();

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('read_at')) {
            if ($request->get('read_at') === 'null') {
                $query->whereNull('read_at');
            } else {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate($itemsPerPage);

        if ($request->expectsJson()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $user->unreadNotifications()->count(),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'has_more_pages' => $notifications->hasMorePages(),
                ],
            ]);
        }

        /** @var view-string $viewName */
        $viewName = 'notifications.index';

        return view($viewName, ['notifications' => $notifications]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $notification = $user
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            abort(404, 'Notification not found');
        }

        $notification->markAsRead();

        return response()->json([
            'id' => $notification->getAttribute('id'),
            'type' => $notification->getAttribute('type'),
            'notifiableType' => $notification->getAttribute('notifiable_type'),
            'notifiableId' => $notification->getAttribute('notifiable_id'),
            'data' => $notification->getAttribute('data'),
            'readAt' => $notification->getAttribute('read_at')?->toISOString(),
            'createdAt' => $notification->getAttribute('created_at')?->toISOString(),
            'updatedAt' => $notification->getAttribute('updated_at')?->toISOString(),
        ]);
    }

    public function clearAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $user
            ->notifications()
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([]);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'All notifications marked as read.');
    }

    public function showAll(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $notifications = $user
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        /** @var view-string $viewName */
        $viewName = 'notifications.all';

        return view($viewName, ['notifications' => $notifications]);
    }
}

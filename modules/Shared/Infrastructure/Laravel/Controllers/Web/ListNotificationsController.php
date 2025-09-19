<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Requests\Web\ListNotificationsRequest;
use Modules\User\Infrastructure\Laravel\Models\User;

class ListNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Display a listing of the user's notifications.
     */
    public function __invoke(ListNotificationsRequest $request): View|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $this->notificationService->getNotifications(
            $user,
            $request->getPerPage(),
        );

        // Format notifications for both JSON and view responses
        /** @var array<string, mixed> $formattedNotifications */
        $formattedNotifications = [];
        foreach ($notifications->items() as $notification) {
            /** @var array<string, mixed> $data */
            $data = is_array($notification->getAttribute('data')) ?
                    $notification->getAttribute('data') :
                    json_decode((string) $notification->getAttribute('data'), true);

            /** @var Carbon|null $createdAt */
            $createdAt = $notification->getAttribute('created_at');
            /** @var Carbon|null $readAt */
            $readAt = $notification->getAttribute('read_at');

            $formattedNotifications[] = [
                'id' => $notification->getAttribute('id'),
                'title' => $data['title'] ?? $data['campaign_title'] ?? 'Notification',
                'message' => $data['message'] ?? $data['description'] ?? '',
                'type' => $data['type'] ?? 'info',
                'time_ago' => $createdAt ? $createdAt->diffForHumans() : 'Just now',
                'icon_color' => $readAt ? 'bg-gray-400' : 'bg-blue-500',
                'url' => $data['action_url'] ?? $data['url'] ?? null,
                'is_read' => $readAt !== null,
                'read_at' => $readAt,
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'notifications' => $formattedNotifications,
                'unread_count' => $this->notificationService->getUnreadCount($user),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                ],
            ]);
        }

        // Replace items with formatted ones for view
        /** @var LengthAwarePaginator<int, mixed> $notifications */
        $notifications->setCollection(collect($formattedNotifications));

        /** @var view-string $viewName */
        $viewName = 'notifications.index';

        return view($viewName, ['notifications' => $notifications]);
    }
}

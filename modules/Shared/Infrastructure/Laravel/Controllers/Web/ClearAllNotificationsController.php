<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Requests\Web\ClearAllNotificationsRequest;
use Modules\User\Infrastructure\Laravel\Models\User;

class ClearAllNotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Mark all notifications as read for the user.
     */
    public function __invoke(ClearAllNotificationsRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $success = $this->notificationService->clearAll($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'All notifications marked as read' : 'No notifications to clear',
            ]);
        }

        $message = $success
            ? 'All notifications marked as read.'
            : 'No notifications to clear.';

        return redirect()->route('notifications.index')
            ->with('success', $message);
    }
}

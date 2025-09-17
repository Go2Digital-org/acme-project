<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Requests\Web\MarkNotificationAsReadRequest;
use Modules\User\Infrastructure\Laravel\Models\User;

class MarkNotificationAsReadController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Mark a specific notification as read.
     */
    public function __invoke(MarkNotificationAsReadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $success = $this->notificationService->markAsRead(
            $user,
            $request->getNotificationId(),
        );

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json(['success' => true]);
    }
}

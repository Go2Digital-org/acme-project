<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Infrastructure\Laravel\Models\User;

class MarkNotificationAsReadController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Mark a specific notification as read.
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $user = $this->getAuthenticatedUserOrNull($request);

        if (! $user instanceof User) {
            return response()->json(['success' => false], 401);
        }

        $success = $this->notificationService->markAsRead($user, $id);

        if (! $success) {
            return response()->json(['success' => false], 404);
        }

        return response()->json(['success' => true]);
    }
}

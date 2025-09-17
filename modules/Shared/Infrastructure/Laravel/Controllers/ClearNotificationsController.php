<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shared\Application\Service\NotificationService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Infrastructure\Laravel\Models\User;

class ClearNotificationsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Clear all notifications for the user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUserOrNull($request);

        if (! $user instanceof User) {
            return response()->json(['success' => false], 401);
        }

        $success = $this->notificationService->clearAll($user);

        return response()->json(['success' => $success]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Service\SessionManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class DestroyOtherSessionsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private SessionManagementService $sessionService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);
        $sessionStore = session();
        $currentSessionId = $sessionStore->getId();

        $this->sessionService->deleteOtherSessions($user->getId(), $currentSessionId);

        return response()->json([
            'message' => 'All other sessions terminated successfully.',
        ]);
    }
}

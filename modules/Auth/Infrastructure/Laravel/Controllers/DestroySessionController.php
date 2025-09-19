<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Services\SessionManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class DestroySessionController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private SessionManagementService $sessionService,
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);
        $password = $request->input('password');

        $this->sessionService->deleteSession($sessionId, $user->getId(), $password);

        return response()->json([
            'message' => 'Session terminated successfully.',
        ]);
    }
}

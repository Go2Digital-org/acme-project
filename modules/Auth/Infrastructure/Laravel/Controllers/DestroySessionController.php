<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Service\SessionManagementService;

final readonly class DestroySessionController
{
    public function __construct(
        private SessionManagementService $sessionService,
    ) {}

    public function __invoke(Request $request, string $sessionId): JsonResponse
    {
        $this->sessionService->deleteSession($sessionId);

        return response()->json([
            'message' => 'Session terminated successfully.',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Models\User;

final class LogoutController
{
    /**
     * Handle employee logout and token revocation.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Get the Laravel user model for token management
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $user->currentAccessToken()->delete();
        }

        return ApiResponse::success(
            message: 'Successfully logged out.',
        );
    }
}

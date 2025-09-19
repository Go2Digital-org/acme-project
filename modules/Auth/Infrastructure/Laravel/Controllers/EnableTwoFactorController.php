<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Services\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class EnableTwoFactorController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'enable' => ['required', 'boolean'],
        ]);

        $user = $this->getAuthenticatedUser($request);

        $this->profileService->enableTwoFactor(
            userId: $user->getId(),
            enable: $request->boolean('enable'),
        );

        return response()->json([
            'message' => $request->boolean('enable')
                ? 'Two-factor authentication enabled successfully.'
                : 'Two-factor authentication disabled successfully.',
        ]);
    }
}

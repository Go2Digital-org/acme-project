<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Service\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class UpdateProfileInformationController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->getAuthenticatedUserId($request)],
        ]);

        $user = $this->getAuthenticatedUser($request);

        $this->profileService->updateProfile(
            userId: $user->getId(),
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
        );

        return response()->json([
            'message' => 'Profile information updated successfully.',
        ]);
    }
}

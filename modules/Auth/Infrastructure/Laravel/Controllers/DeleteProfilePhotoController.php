<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Application\Services\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class DeleteProfilePhotoController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        // Delete the physical file if it exists
        $photoPath = $user->getProfilePhotoPath();

        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }

        // Update profile to remove photo path
        $this->profileService->updateProfile(
            userId: $user->getId(),
            name: $user->getFullName(),
            email: $user->getEmailString(),
            profilePhoto: null,
        );

        return response()->json([
            'message' => 'Profile photo removed successfully.',
            'photo_url' => null, // Return null since photo is deleted
        ]);
    }
}

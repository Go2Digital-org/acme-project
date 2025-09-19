<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\File;
use Modules\Auth\Application\Services\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class UploadProfilePhotoController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'profile_photo' => [
                'required',
                File::image()
                    ->max(2048) // 2MB max
                    ->dimensions(new Dimensions([
                        'min_width' => 100,
                        'min_height' => 100,
                        'max_width' => 2000,
                        'max_height' => 2000,
                    ])),
            ],
        ]);

        $user = $this->getAuthenticatedUser($request);

        // Delete old photo if it exists
        $oldPhotoPath = $user->getProfilePhotoPath();

        if ($oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        // Store new photo
        $profilePhoto = $request->file('profile_photo');

        if ($profilePhoto === null) {
            return response()->json(['message' => 'No file provided.'], 400);
        }

        $storedPath = $profilePhoto->store('profile-photos', 'public');

        if ($storedPath === false) {
            return response()->json(['message' => 'Failed to store profile photo.'], 500);
        }

        // Update user profile with new photo path
        $this->profileService->updateProfile(
            userId: $user->getId(),
            name: $user->getFullName(),
            email: $user->getEmailString(),
            profilePhoto: $storedPath,
        );

        $photoUrl = Storage::disk('public')->url($storedPath);

        return response()->json([
            'message' => 'Profile photo uploaded successfully.',
            'photo_url' => $photoUrl,
        ]);
    }
}

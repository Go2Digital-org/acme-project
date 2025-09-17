<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Application\Request\UpdateProfileRequest;
use Modules\Auth\Application\Service\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class UpdateProfileController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->getAuthenticatedUser($request);

        $profilePhoto = $request->file('profile_photo');
        $profilePhotoPath = null;

        if ($profilePhoto !== null) {
            $storedPath = $profilePhoto->store('profile-photos', 'public');
            $profilePhotoPath = $storedPath !== false ? $storedPath : null;
        }

        $this->profileService->updateProfile(
            userId: $user->getId(),
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            profilePhoto: $profilePhotoPath,
        );

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully.');
    }
}

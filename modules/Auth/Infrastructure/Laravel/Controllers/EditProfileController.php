<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Auth\Application\Services\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class EditProfileController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);
        $profile = $this->profileService->getUserProfile($user->getId());

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}

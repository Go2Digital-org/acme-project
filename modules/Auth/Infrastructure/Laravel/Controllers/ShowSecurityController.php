<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Auth\Application\Service\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

/**
 * Show Security Settings Controller.
 *
 * Handles displaying security settings page with user profile data.
 */
final readonly class ShowSecurityController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);
        $profile = $this->profileService->getUserProfile($user->getId());

        return view('profile.security', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}

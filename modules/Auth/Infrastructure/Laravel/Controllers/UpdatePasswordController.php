<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Application\Request\UpdatePasswordRequest;
use Modules\Auth\Application\Service\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class UpdatePasswordController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $this->getAuthenticatedUser($request);

        $this->profileService->updatePassword(
            userId: $user->getId(),
            currentPassword: $request->string('current_password')->toString(),
            newPassword: $request->string('password')->toString(),
        );

        return redirect()->route('profile.security')
            ->with('success', 'Password updated successfully.');
    }
}

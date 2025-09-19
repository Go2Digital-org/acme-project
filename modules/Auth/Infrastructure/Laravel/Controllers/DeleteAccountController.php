<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Services\ProfileManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class DeleteAccountController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ProfileManagementService $profileService,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $this->getAuthenticatedUser($request);

        $this->profileService->deleteAccount(
            userId: $user->getId(),
            password: $request->string('password')->toString(),
        );

        return redirect()->route('welcome')
            ->with('success', 'Your account has been deleted successfully.');
    }
}

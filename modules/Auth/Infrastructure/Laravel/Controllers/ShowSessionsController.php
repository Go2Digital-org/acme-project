<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Auth\Application\Service\SessionManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class ShowSessionsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private SessionManagementService $sessionService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);
        $sessions = $this->sessionService->getUserSessions($user->getId());

        return view('profile.sessions', [
            'user' => $user,
            'sessions' => $sessions,
        ]);
    }
}

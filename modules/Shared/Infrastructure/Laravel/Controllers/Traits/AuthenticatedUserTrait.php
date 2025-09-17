<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Traits;

use Illuminate\Http\Request;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpFoundation\Response;

trait AuthenticatedUserTrait
{
    /**
     * Get the authenticated user or throw an exception.
     */
    protected function getAuthenticatedUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication required.');
        }

        return $user;
    }

    /**
     * Get the authenticated user or return null.
     */
    protected function getAuthenticatedUserOrNull(Request $request): ?User
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user;
    }

    /**
     * Get the authenticated user ID or throw an exception.
     */
    protected function getAuthenticatedUserId(Request $request): int
    {
        $user = $this->getAuthenticatedUser($request);

        return $user->getId();
    }

    /**
     * Check if the authenticated user owns the resource.
     */
    protected function ensureUserOwnsResource(Request $request, int $resourceUserId): void
    {
        $authenticatedUserId = $this->getAuthenticatedUserId($request);

        if ($authenticatedUserId !== $resourceUserId) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');
        }
    }

    /**
     * Check if the authenticated user owns the resource and return the user.
     */
    protected function getAuthenticatedUserAndEnsureOwnership(Request $request, int $resourceUserId): User
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user->getId() !== $resourceUserId) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');
        }

        return $user;
    }
}

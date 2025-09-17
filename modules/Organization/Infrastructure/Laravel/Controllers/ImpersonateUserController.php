<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Stancl\Tenancy\Features\UserImpersonation;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for handling user impersonation via tokens.
 *
 * This controller uses Laravel Tenancy's built-in UserImpersonation feature
 * which properly handles authentication within the tenant context.
 */
final class ImpersonateUserController
{
    /**
     * Handle user impersonation request.
     *
     * Uses Laravel Tenancy's UserImpersonation::makeResponse() method which:
     * 1. Validates the token exists and hasn't expired
     * 2. Authenticates the user within the tenant context
     * 3. Deletes the token for security
     * 4. Redirects to the specified URL
     *
     * @param  string  $token  The impersonation token
     */
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        try {
            // Use Laravel Tenancy's built-in impersonation handler
            // This properly handles the authentication in the tenant context
            return UserImpersonation::makeResponse($token);

        } catch (InvalidArgumentException $e) {
            // Handle invalid token or validation errors
            throw new NotFoundHttpException('Invalid or expired impersonation token: ' . $e->getMessage());
        } catch (Exception $e) {
            // Handle other errors
            throw new AccessDeniedHttpException('Failed to impersonate user: ' . $e->getMessage());
        }
    }
}

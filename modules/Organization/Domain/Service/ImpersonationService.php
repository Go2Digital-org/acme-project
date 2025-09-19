<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Service;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Stancl\Tenancy\Database\Models\ImpersonationToken;

/**
 * Domain service for handling user impersonation within tenant context.
 *
 * This service encapsulates the business logic for validating and executing
 * user impersonation, ensuring security and tenant isolation.
 */
class ImpersonationService
{
    private const DEFAULT_TTL_SECONDS = 60;

    /**
     * Validate and execute user impersonation using a token.
     *
     * @param  string  $token  The impersonation token
     * @param  Organization  $tenant  The current tenant organization
     * @return ImpersonationToken The validated token model
     *
     * @throws InvalidArgumentException If token is invalid or expired
     */
    public function impersonateWithToken(string $token, Organization $tenant): ImpersonationToken
    {
        $tokenModel = $this->findToken($token);

        $this->validateTenantOwnership($tokenModel, $tenant);
        $this->validateTokenExpiry($tokenModel);

        $this->authenticateUser($tokenModel);
        $this->deleteToken($tokenModel);

        return $tokenModel;
    }

    /**
     * Find the impersonation token or fail.
     */
    private function findToken(string $token): ImpersonationToken
    {
        $tokenModel = ImpersonationToken::where('token', $token)->first();

        if (! $tokenModel) {
            throw new InvalidArgumentException('Invalid impersonation token');
        }

        return $tokenModel;
    }

    /**
     * Validate that the token belongs to the current tenant.
     */
    private function validateTenantOwnership(ImpersonationToken $tokenModel, Organization $tenant): void
    {
        if (((string) $tokenModel->tenant_id) !== ($tenant->getTenantKey())) { // @phpstan-ignore-line
            throw new InvalidArgumentException('Token does not belong to current tenant');
        }
    }

    /**
     * Validate that the token has not expired.
     */
    private function validateTokenExpiry(ImpersonationToken $tokenModel): void
    {
        $ttl = config('tenancy.impersonation.ttl', self::DEFAULT_TTL_SECONDS);

        if ($tokenModel->created_at->diffInSeconds(Carbon::now()) > $ttl) { // @phpstan-ignore-line
            throw new InvalidArgumentException('Impersonation token has expired');
        }
    }

    /**
     * Authenticate the user specified in the token.
     */
    private function authenticateUser(ImpersonationToken $tokenModel): void
    {
        // Log the authentication attempt
        Log::info('ImpersonationService: Starting authentication', [
            'user_id' => $tokenModel->user_id, // @phpstan-ignore-line
            'guard' => $tokenModel->auth_guard, // @phpstan-ignore-line
            'tenant_initialized' => tenancy()->initialized,
            'tenant_id' => tenant()?->getTenantKey(),
            'current_db' => DB::connection()->getDatabaseName(),
            'tenant_db' => DB::connection('tenant')->getDatabaseName(),
        ]);

        // Fetch the user from the tenant database explicitly
        // This ensures we get the correct user from the tenant, not the central database
        $user = User::on('tenant')->find($tokenModel->user_id); // @phpstan-ignore-line

        if (! $user instanceof User) {
            throw new InvalidArgumentException('User not found in tenant database');
        }

        Log::info('ImpersonationService: User found', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_connection' => $user->getConnection()->getDatabaseName(),
        ]);

        // Login with the user object instead of just the ID
        // This ensures Laravel uses the correct user instance from the tenant database
        Auth::guard($tokenModel->auth_guard)->login($user); // @phpstan-ignore-line

        // Force session save to ensure authentication persists
        session()->save();

        // Verify authentication worked
        $authUser = Auth::guard($tokenModel->auth_guard)->user(); // @phpstan-ignore-line
        Log::info('ImpersonationService: After login', [
            'authenticated' => Auth::guard($tokenModel->auth_guard)->check(), // @phpstan-ignore-line
            'auth_user_id' => $authUser?->id,
            'auth_user_name' => $authUser?->name,
            'auth_user_email' => $authUser?->email,
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Delete the used token for security.
     */
    private function deleteToken(ImpersonationToken $tokenModel): void
    {
        $tokenModel->delete();
    }

    /**
     * Generate the redirect URL after successful impersonation.
     *
     * @param  string|null  $locale  The locale to use for the redirect
     * @return string The localized dashboard URL
     */
    public function getRedirectUrl(?string $locale = null): string
    {
        $locale = ($locale ?: app()->getLocale()) ?: 'en';

        // Log before redirect
        Log::info('ImpersonationService: Generating redirect URL', [
            'locale' => $locale,
            'auth_check' => Auth::check(),
            'auth_user' => Auth::user()?->email,
            'session_id' => session()->getId(),
        ]);

        // Use Laravel Localization to generate proper localized URL
        $url = app('laravellocalization')->getLocalizedURL($locale, '/dashboard');

        // Ensure we always return a string (fallback to regular dashboard URL)
        return $url !== false ? $url : url('/dashboard');
    }
}

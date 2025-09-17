<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to detect and recover from session/cookie issues that cause 404 errors.
 *
 * This middleware prevents the common issue where switching between domains or environments
 * causes all routes to return 404 due to stale or corrupted session data.
 */
class SessionRecoveryMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if session is accessible and valid
            $this->validateSession($request);

            return $next($request);
        } catch (Exception $e) {
            // Log the session error for debugging
            Log::warning('Session recovery triggered', [
                'error' => $e->getMessage(),
                'host' => $request->getHost(),
                'path' => $request->path(),
                'session_domain' => config('session.domain'),
                'app_url' => config('app.url'),
            ]);

            // Clear problematic session and regenerate
            $this->recoverSession($request);

            // Continue with fresh session
            return $next($request);
        }
    }

    /**
     * Validate that the current session is compatible with the current environment.
     *
     * @throws Exception if session is invalid or incompatible
     */
    private function validateSession(Request $request): void
    {
        // Check if we can read from session
        if (! $this->canAccessSession()) {
            throw new Exception('Cannot access session storage');
        }

        // Check for domain mismatch
        $sessionDomain = Session::get('_session_domain');
        $currentDomain = $this->getCurrentDomain($request);

        if ($sessionDomain && $sessionDomain !== $currentDomain) {
            throw new Exception(sprintf(
                'Session domain mismatch: session=%s, current=%s',
                $sessionDomain,
                $currentDomain
            ));
        }

        // Check for environment mismatch
        $sessionEnv = Session::get('_session_env');
        $currentEnv = app()->environment();

        if ($sessionEnv && $sessionEnv !== $currentEnv) {
            throw new Exception(sprintf(
                'Session environment mismatch: session=%s, current=%s',
                $sessionEnv,
                $currentEnv
            ));
        }

        // Check for app key mismatch (indicates encryption key changed)
        $sessionAppKeyHash = Session::get('_app_key_hash');
        $currentAppKeyHash = $this->getAppKeyHash();

        if ($sessionAppKeyHash && $sessionAppKeyHash !== $currentAppKeyHash) {
            throw new Exception('Application encryption key has changed');
        }

        // Store current environment info in session for future validation
        $this->storeSessionMetadata($request);
    }

    /**
     * Check if session storage is accessible.
     */
    private function canAccessSession(): bool
    {
        try {
            // Attempt to read a test value from session
            Session::get('_test_access', null);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get the current domain for session validation.
     */
    private function getCurrentDomain(Request $request): string
    {
        // Use configured session domain if set, otherwise use request host
        $configuredDomain = config('session.domain');

        if ($configuredDomain) {
            // Remove leading dot for comparison
            return ltrim((string) $configuredDomain, '.');
        }

        return $request->getHost();
    }

    /**
     * Get a hash of the application key for validation.
     */
    private function getAppKeyHash(): string
    {
        $appKey = config('app.key', '');

        return substr(md5((string) $appKey), 0, 8);
    }

    /**
     * Store metadata in session for future validation.
     */
    private function storeSessionMetadata(Request $request): void
    {
        Session::put('_session_domain', $this->getCurrentDomain($request));
        Session::put('_session_env', app()->environment());
        Session::put('_app_key_hash', $this->getAppKeyHash());
        Session::put('_session_created_at', now()->toIso8601String());
    }

    /**
     * Recover from a corrupted or incompatible session.
     */
    private function recoverSession(Request $request): void
    {
        try {
            // Flush all session data
            Session::flush();

            // Regenerate session ID to prevent fixation attacks
            Session::regenerate(true);

            // Store fresh metadata
            $this->storeSessionMetadata($request);

            // Set a flag indicating session was recovered
            Session::put('_session_recovered', true);
            Session::put('_session_recovered_at', now()->toIso8601String());

            Log::info('Session successfully recovered', [
                'host' => $request->getHost(),
                'path' => $request->path(),
                'new_session_id' => Session::getId(),
            ]);
        } catch (Exception $e) {
            // If recovery fails, log but don't throw to prevent breaking the request
            Log::error('Session recovery failed', [
                'error' => $e->getMessage(),
                'host' => $request->getHost(),
            ]);
        }
    }
}

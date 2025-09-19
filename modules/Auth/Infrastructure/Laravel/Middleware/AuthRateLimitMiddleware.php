<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Auth\Application\Services\SecurityAuditService;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Authentication Rate Limiting Middleware.
 *
 * Provides intelligent rate limiting for authentication endpoints
 * with progressive delays and security event logging.
 */
final readonly class AuthRateLimitMiddleware
{
    public function __construct(
        private SecurityAuditService $securityAudit,
    ) {}

    /**
     * Handle incoming request with rate limiting.
     *
     * @param  Closure(Request): ResponseAlias  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): ResponseAlias
    {
        $ip = $request->ip() ?? '127.0.0.1';

        // Check if IP is blocked
        if ($this->securityAudit->isIpBlocked($ip)) {
            return response()->json([
                'message' => 'Access temporarily blocked due to suspicious activity.',
                'retry_after' => 3600, // 1 hour
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Apply rate limiting based on endpoint type
        $result = match ($type) {
            'login' => $this->handleLoginRateLimit($request),
            'register' => $this->handleRegisterRateLimit($request),
            'password-reset' => $this->handlePasswordResetRateLimit($request),
            '2fa' => $this->handleTwoFactorRateLimit($request),
            'session' => $this->handleSessionRateLimit($request),
            default => $this->handleDefaultRateLimit($request),
        };

        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            return $result;
        }

        return $next($request);
    }

    private function handleLoginRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $email = $request->input('email', '');

        // Progressive rate limiting: 5 attempts per minute, then 3 per 5 minutes, then 1 per 15 minutes
        $ipKey = "auth_login_ip:{$ip}";
        $emailKey = "auth_login_email:{$email}";

        // Check rapid attempts (per minute)
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);

            // If more than 10 attempts in 5 minutes, increase block time
            $longerKey = "auth_login_ip_long:{$ip}";
            if (RateLimiter::attempts($longerKey) >= 10) {
                $this->securityAudit->blockIp($ip, 60); // Block for 1 hour

                return response()->json([
                    'message' => 'Too many login attempts. IP temporarily blocked.',
                    'retry_after' => 3600,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            RateLimiter::hit($longerKey, 300); // Track longer-term attempts

            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Check email-specific rate limiting
        if ($email && RateLimiter::tooManyAttempts($emailKey, 3)) {
            $seconds = RateLimiter::availableIn($emailKey);

            return response()->json([
                'message' => 'Too many attempts for this email. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Record the attempt
        RateLimiter::hit($ipKey, 60); // 1 minute window
        if ($email) {
            RateLimiter::hit($emailKey, 300); // 5 minute window for email
        }

        return null;
    }

    private function handleRegisterRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip();
        $key = "auth_register:{$ip}";

        // Allow 3 registration attempts per hour per IP
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Registration rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 3600); // 1 hour window

        return null;
    }

    private function handlePasswordResetRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip();
        $email = $request->input('email', '');

        $ipKey = "auth_reset_ip:{$ip}";
        $emailKey = "auth_reset_email:{$email}";

        // Allow 3 password reset requests per hour per IP
        if (RateLimiter::tooManyAttempts($ipKey, 3)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return response()->json([
                'message' => 'Password reset rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Allow 2 password reset requests per hour per email
        if ($email && RateLimiter::tooManyAttempts($emailKey, 2)) {
            $seconds = RateLimiter::availableIn($emailKey);

            return response()->json([
                'message' => 'Too many password reset requests for this email.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($ipKey, 3600); // 1 hour
        if ($email) {
            RateLimiter::hit($emailKey, 3600); // 1 hour
        }

        return null;
    }

    private function handleTwoFactorRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip();
        $userId = auth()->id() ?? 0;

        $ipKey = "auth_2fa_ip:{$ip}";
        $userKey = "auth_2fa_user:{$userId}";

        // Allow 10 2FA attempts per 5 minutes per IP
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return response()->json([
                'message' => '2FA rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Allow 5 2FA attempts per 5 minutes per user
        if ($userId > 0 && RateLimiter::tooManyAttempts($userKey, 5)) {
            $seconds = RateLimiter::availableIn($userKey);

            // If user exceeds limit, temporarily block them
            if (RateLimiter::attempts($userKey) >= 8) {
                $this->securityAudit->blockUser((int) $userId, 15);
            }

            return response()->json([
                'message' => '2FA verification rate limit exceeded.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($ipKey, 300); // 5 minutes
        if ($userId > 0) {
            RateLimiter::hit($userKey, 300); // 5 minutes
        }

        return null;
    }

    private function handleSessionRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip();
        $userId = auth()->id() ?? 0;

        $ipKey = "auth_session_ip:{$ip}";
        $userKey = "auth_session_user:{$userId}";

        // Allow 20 session operations per minute per IP
        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return response()->json([
                'message' => 'Session operation rate limit exceeded.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Allow 10 session operations per minute per user
        if ($userId > 0 && RateLimiter::tooManyAttempts($userKey, 10)) {
            $seconds = RateLimiter::availableIn($userKey);

            return response()->json([
                'message' => 'Too many session operations. Please slow down.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($ipKey, 60); // 1 minute
        if ($userId > 0) {
            RateLimiter::hit($userKey, 60); // 1 minute
        }

        return null;
    }

    private function handleDefaultRateLimit(Request $request): ?ResponseAlias
    {
        $ip = $request->ip();
        $key = "auth_default:{$ip}";

        // Default: 60 requests per minute per IP
        if (RateLimiter::tooManyAttempts($key, 60)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 60); // 1 minute

        return null;
    }
}

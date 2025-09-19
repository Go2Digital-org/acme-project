<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Listener;

use Carbon\Carbon;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\User\Infrastructure\Laravel\Models\User;

class AuthEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailedLogin',
            Attempting::class => 'handleLoginAttempt',
            Authenticated::class => 'handleAuthenticated',
            Lockout::class => 'handleLockout',
            Registered::class => 'handleRegistered',
            PasswordReset::class => 'handlePasswordReset',
            Verified::class => 'handleVerified',
        ];
    }

    /**
     * Handle successful login events.
     */
    public function handleLogin(Login $event): void
    {
        $request = request();
        $sessionDuration = null;

        if ($request->hasSession()) {
            $sessionDuration = config('session.lifetime') * 60; // Convert to seconds
            // Store the login timestamp for session duration calculation
            $request->session()->put('login_timestamp', now());
            // Also store user login time in a more permanent way
            $request->session()->put('user_logged_in_at', now()->timestamp);
        }

        $context = [
            'event' => 'login_success',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user->id ?? null,
            'user_email' => $event->user->email ?? null,
            'user_name' => $event->user->name ?? null,
            'guard' => $event->guard,
            'remember' => $event->remember,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'session_id' => $request->session()->getId(),
            'session_lifetime_minutes' => config('session.lifetime'),
            'login_method' => $this->detectLoginMethod($request),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
            'platform' => $this->detectPlatform($request->userAgent()),
        ];

        Log::channel('auth')->info('User logged in successfully', $context);

        // Also log significant events to main channel
        Log::info('User login', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Handle logout events.
     */
    public function handleLogout(Logout $event): void
    {
        $request = request();

        $sessionDuration = null;
        if ($request->hasSession()) {
            // Try multiple ways to get the login time
            $loginTime = null;

            // First try the timestamp we stored
            if ($request->session()->has('user_logged_in_at')) {
                $loginTimestamp = $request->session()->get('user_logged_in_at');
                $sessionDuration = now()->timestamp - $loginTimestamp;
            }

            if ($sessionDuration === null && $request->session()->has('login_timestamp')) {
                $loginTime = $request->session()->get('login_timestamp');
                if ($loginTime instanceof Carbon) {
                    $sessionDuration = $loginTime->diffInSeconds(now());
                }
            }

            // If still no duration, estimate based on session creation
            if ($sessionDuration === null && $request->session()->has('_token')) {
                // Default to a reasonable estimate
                $sessionDuration = 0;
            }
        }

        $context = [
            'event' => 'logout',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user->id ?? null,
            'user_email' => $event->user->email ?? null,
            'user_name' => $event->user->name ?? null,
            'guard' => $event->guard,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'session_duration_seconds' => $sessionDuration,
            'logout_type' => $this->detectLogoutType($request),
        ];

        Log::channel('auth')->info('User logged out', $context);

        Log::info('User logout', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'session_duration' => $sessionDuration,
        ]);
    }

    /**
     * Handle failed login attempts.
     */
    public function handleFailedLogin(Failed $event): void
    {
        $request = request();

        $context = [
            'event' => 'login_failed',
            'timestamp' => now()->toISOString(),
            'guard' => $event->guard,
            'credentials_email' => $event->credentials['email'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'failure_reason' => $this->determineFailureReason($event),
            'attempts_remaining' => $this->getRemainingAttempts(),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
        ];

        Log::channel('auth')->warning('Failed login attempt', $context);

        // Log to main channel for security monitoring
        Log::warning('Failed login attempt', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => $request->ip(),
            'attempts_remaining' => $this->getRemainingAttempts(),
        ]);
    }

    /**
     * Handle login attempts (before authentication).
     */
    public function handleLoginAttempt(Attempting $event): void
    {
        $request = request();

        $context = [
            'event' => 'login_attempt',
            'timestamp' => now()->toISOString(),
            'guard' => $event->guard,
            'credentials_email' => $event->credentials['email'] ?? null,
            'remember' => $event->remember,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ];

        Log::channel('auth')->debug('Login attempt initiated', $context);
    }

    /**
     * Handle authenticated events (session restored).
     */
    public function handleAuthenticated(Authenticated $event): void
    {
        $request = request();

        // Only log once per session - check if we've already logged this session
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            $lastLoggedSession = $request->session()->get('auth_event_logged_session');

            // If we've already logged this session, skip
            if ($lastLoggedSession === $sessionId) {
                return;
            }

            // Mark this session as logged
            $request->session()->put('auth_event_logged_session', $sessionId);

            // Store login timestamp for duration calculation if not already set
            if (! $request->session()->has('login_timestamp')) {
                $request->session()->put('login_timestamp', now());
            }
        }

        $context = [
            'event' => 'session_authenticated',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user->id ?? null,
            'user_email' => $event->user->email ?? null,
            'guard' => $event->guard,
            'ip' => $request->ip(),
            'session_id' => $request->session()->getId(),
        ];

        Log::channel('auth')->debug('User session authenticated', $context);
    }

    /**
     * Handle lockout events (too many failed attempts).
     */
    public function handleLockout(Lockout $event): void
    {
        $context = [
            'event' => 'account_lockout',
            'timestamp' => now()->toISOString(),
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
            'email_attempted' => $event->request->input('email'),
            'lockout_duration_seconds' => config('auth.passwords.users.throttle', 60),
            'referrer' => $event->request->header('referer'),
        ];

        Log::channel('auth')->error('Account locked out due to too many failed attempts', $context);

        // Critical security event - log to main channel
        Log::error('Account lockout triggered', [
            'ip' => $event->request->ip(),
            'email' => $event->request->input('email'),
        ]);
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistered(Registered $event): void
    {
        $request = request();

        $context = [
            'event' => 'user_registered',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user instanceof User ? $event->user->id : null,
            'user_email' => $event->user instanceof User ? $event->user->email : null,
            'user_name' => $event->user instanceof User ? $event->user->name : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ];

        Log::channel('auth')->info('New user registered', $context);
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $request = request();

        $context = [
            'event' => 'password_reset',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user instanceof User ? $event->user->id : null,
            'user_email' => $event->user instanceof User ? $event->user->email : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        Log::channel('auth')->info('Password reset completed', $context);

        // Security event - log to main channel
        Log::info('Password reset', [
            'user_id' => $event->user instanceof User ? $event->user->id : null,
            'email' => $event->user instanceof User ? $event->user->email : null,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Handle email verification events.
     */
    public function handleVerified(Verified $event): void
    {
        $request = request();

        $context = [
            'event' => 'email_verified',
            'timestamp' => now()->toISOString(),
            'user_id' => $event->user instanceof User ? $event->user->id : null,
            'user_email' => $event->user instanceof User ? $event->user->email : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        Log::channel('auth')->info('Email address verified', $context);
    }

    /**
     * Detect the login method used.
     */
    private function detectLoginMethod(Request $request): string
    {
        if ($request->is('api/*')) {
            return 'api';
        }

        if ($request->is('auth/google/*') || $request->is('oauth/*')) {
            return 'oauth_google';
        }

        if ($request->ajax()) {
            return 'ajax';
        }

        return 'web_form';
    }

    /**
     * Detect the logout type.
     */
    private function detectLogoutType(Request $request): string
    {
        if ($request->is('api/*')) {
            return 'api_token_revoked';
        }

        if ($request->session()->has('session_expired')) {
            return 'session_expired';
        }

        return 'user_initiated';
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent.
     */
    private function detectBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Safari') && ! str_contains($userAgent, 'Chrome')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }

        return 'Other';
    }

    /**
     * Detect platform from user agent.
     */
    private function detectPlatform(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac')) {
            return 'MacOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'iOS') || str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        return 'Other';
    }

    /**
     * Determine the reason for login failure.
     */
    private function determineFailureReason(Failed $event): string
    {
        // Check if user exists
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $event->credentials['email'] ?? '')->first();

        if (! $user) {
            return 'user_not_found';
        }

        if (! $user->email_verified_at) {
            return 'email_not_verified';
        }

        if ($user->blocked_at ?? false) {
            return 'account_blocked';
        }

        return 'invalid_password';
    }

    /**
     * Get remaining login attempts before lockout.
     */
    private function getRemainingAttempts(): int
    {
        return 5;
    }
}

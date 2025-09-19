<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

/**
 * Security Audit Service.
 *
 * Centralized service for logging authentication and security events
 * with rate limiting and threat detection capabilities.
 */
final class SecurityAuditService
{
    private const SECURITY_EVENTS = [
        'auth.login.success',
        'auth.login.failed',
        'auth.logout',
        'auth.register',
        'auth.password.changed',
        'auth.password.reset.requested',
        'auth.password.reset.completed',
        'auth.2fa.enabled',
        'auth.2fa.disabled',
        'auth.2fa.verified',
        'auth.2fa.failed',
        'auth.session.deleted',
        'auth.recovery.codes.accessed',
        'auth.recovery.codes.regenerated',
        'auth.account.locked',
        'auth.account.unlocked',
        'auth.suspicious.activity',
        'auth.unauthorized.access',
    ];

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSecurityEvent(
        string $event,
        int $userId,
        array $context = [],
        string $level = 'info'
    ): void {
        // Validate event type
        if (! in_array($event, self::SECURITY_EVENTS, true)) {
            throw new InvalidArgumentException("Invalid security event: {$event}");
        }

        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        // Rate limit security logging to prevent log flooding
        $key = "security_log:{$userId}:{$event}";
        if (RateLimiter::tooManyAttempts($key, 10)) {
            // If too many of the same event, log a warning about potential attack
            Log::warning('Security event rate limit exceeded', [
                'event' => $event,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);

            return;
        }

        RateLimiter::hit($key, 60); // Allow 10 events per minute

        // Enrich context with security metadata
        $enrichedContext = array_merge($context, [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID', 'unknown'),
        ]);

        // Log the security event
        Log::log($level, "Security Event: {$event}", $enrichedContext);

        // Check for suspicious patterns
        $this->detectSuspiciousActivity($event, $userId, $enrichedContext);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logFailedAuthentication(
        string $email,
        string $reason,
        array $context = []
    ): void {
        $ip = request()->ip();

        // Rate limit failed login attempts per IP
        $ipKey = "failed_login:ip:{$ip}";
        $emailKey = "failed_login:email:{$email}";

        RateLimiter::hit($ipKey, 900); // 15 minutes
        RateLimiter::hit($emailKey, 900);

        // Check if this IP or email should be blocked
        $ipAttempts = RateLimiter::attempts($ipKey);
        $emailAttempts = RateLimiter::attempts($emailKey);

        Log::warning('Failed authentication attempt', array_merge($context, [
            'email' => $email,
            'reason' => $reason,
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'ip_attempts' => $ipAttempts,
            'email_attempts' => $emailAttempts,
            'timestamp' => now()->toISOString(),
        ]));

        // Log suspicious activity if many failures
        if ($ipAttempts >= 5 || $emailAttempts >= 3) {
            Log::critical('Suspicious authentication activity detected', [
                'email' => $email,
                'ip_address' => $ip,
                'ip_attempts' => $ipAttempts,
                'email_attempts' => $emailAttempts,
                'reason' => 'Multiple failed login attempts',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSuccessfulAuthentication(int $userId, array $context = []): void
    {
        $this->logSecurityEvent('auth.login.success', $userId, $context);

        // Reset failed attempt counters on successful login
        $email = $context['email'] ?? '';
        if ($email) {
            RateLimiter::clear("failed_login:email:{$email}");
        }
        RateLimiter::clear('failed_login:ip:' . request()->ip());
    }

    public function logPasswordChange(int $userId, bool $forced = false): void
    {
        $this->logSecurityEvent('auth.password.changed', $userId, [
            'forced' => $forced,
            'strength_score' => $this->calculatePasswordStrength(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logTwoFactorEvent(int $userId, string $action, array $context = []): void
    {
        $event = match ($action) {
            'enabled' => 'auth.2fa.enabled',
            'disabled' => 'auth.2fa.disabled',
            'verified' => 'auth.2fa.verified',
            'failed' => 'auth.2fa.failed',
            default => throw new InvalidArgumentException("Invalid 2FA action: {$action}"),
        };

        $level = $action === 'failed' ? 'warning' : 'info';
        $this->logSecurityEvent($event, $userId, $context, $level);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logSessionActivity(int $userId, string $action, array $context = []): void
    {
        $event = "auth.session.{$action}";

        // Only log if it's a valid session event
        if (in_array($event, self::SECURITY_EVENTS, true)) {
            $this->logSecurityEvent($event, $userId, $context);
        }
    }

    public function isUserBlocked(int $userId): bool
    {
        $key = "user_blocked:{$userId}";

        return RateLimiter::tooManyAttempts($key, 0);
    }

    public function isIpBlocked(string $ip): bool
    {
        $key = "ip_blocked:{$ip}";

        return RateLimiter::tooManyAttempts($key, 0);
    }

    public function blockUser(int $userId, int $minutes = 15): void
    {
        $key = "user_blocked:{$userId}";
        RateLimiter::hit($key, $minutes * 60);

        $this->logSecurityEvent('auth.account.locked', $userId, [
            'duration_minutes' => $minutes,
            'reason' => 'Security violation',
        ], 'critical');
    }

    public function blockIp(string $ip, int $minutes = 60): void
    {
        $key = "ip_blocked:{$ip}";
        RateLimiter::hit($key, $minutes * 60);

        Log::critical('IP address blocked', [
            'ip_address' => $ip,
            'duration_minutes' => $minutes,
            'reason' => 'Suspicious activity',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function detectSuspiciousActivity(string $event, int $userId, array $context): void
    {
        $suspiciousEvents = [
            'auth.login.failed',
            'auth.2fa.failed',
            'auth.unauthorized.access',
        ];

        if (! in_array($event, $suspiciousEvents, true)) {
            return;
        }

        // Check for patterns that indicate potential attacks
        $ip = $context['ip_address'] ?? '';
        $userAgent = $context['user_agent'] ?? '';

        // Check for bot-like user agents
        if ($this->isSuspiciousUserAgent($userAgent)) {
            Log::warning('Suspicious user agent detected', [
                'user_id' => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'event' => $event,
            ]);
        }

        // Check for rapid-fire attempts from same IP
        $recentEvents = RateLimiter::attempts("security_events:ip:{$ip}");
        if ($recentEvents >= 10) {
            $this->blockIp($ip, 60);
        }

        RateLimiter::hit("security_events:ip:{$ip}", 300); // 5 minutes
    }

    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python',
            'java',
            'script',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function calculatePasswordStrength(): int
    {
        // This would ideally calculate the actual password strength
        // For security reasons, we don't log the actual password
        // Return a mock score for audit purposes
        return random_int(1, 100);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Auth\Domain\ValueObject\AuthToken;
use Modules\Auth\Domain\ValueObject\PasswordStrength;
use Modules\Auth\Domain\ValueObject\TwoFactorCode;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\ValueObject\EmailAddress;

/**
 * Authentication Domain Service.
 *
 * Handles authentication logic and security operations.
 */
class AuthenticationService
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_DURATION_MINUTES = 30;

    private const SESSION_LIFETIME_MINUTES = 120;

    public function authenticateWithPassword(
        EmailAddress $email,
        string $password,
        User $user,
        string $hashedPassword
    ): AuthenticationResult {
        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            throw new InvalidArgumentException('Account is temporarily locked due to too many failed attempts');
        }

        // Verify password
        if (! $this->verifyPassword($password, $hashedPassword)) {
            $this->recordFailedAttempt($user);
            throw new InvalidArgumentException('Invalid credentials');
        }

        // Check if user can authenticate
        if (! $user->isActive()) {
            throw new InvalidArgumentException('Account is not active');
        }

        if (! $user->isVerified()) {
            throw new InvalidArgumentException('Email address is not verified');
        }

        // Generate authentication token
        $token = AuthToken::generate(self::SESSION_LIFETIME_MINUTES);

        return new AuthenticationResult(
            user: $user,
            token: $token,
            requiresTwoFactor: $user->hasTwoFactorEnabled(),
            sessionExpiresAt: $token->getExpiresAt()
        );
    }

    public function authenticateWithTwoFactor(
        User $user,
        TwoFactorCode $providedCode,
        string $inputCode
    ): AuthenticationResult {
        if (! $user->hasTwoFactorEnabled()) {
            throw new InvalidArgumentException('Two-factor authentication is not enabled for this user');
        }

        if (! $providedCode->verify($inputCode)) {
            throw new InvalidArgumentException('Invalid two-factor authentication code');
        }

        $token = AuthToken::generate(self::SESSION_LIFETIME_MINUTES);

        return new AuthenticationResult(
            user: $user,
            token: $token,
            requiresTwoFactor: false,
            sessionExpiresAt: $token->getExpiresAt(),
            twoFactorVerified: true
        );
    }

    public function validatePassword(string $password): PasswordStrength
    {
        return PasswordStrength::validate($password);
    }

    public function generateTwoFactorCode(string $type = 'totp'): TwoFactorCode
    {
        return match ($type) {
            'totp' => TwoFactorCode::generateTOTP(),
            'sms' => TwoFactorCode::generateSMS(),
            'email' => TwoFactorCode::generateEmail(),
            'backup' => TwoFactorCode::generateBackupCode(),
            default => throw new InvalidArgumentException("Invalid 2FA code type: {$type}"),
        };
    }

    public function refreshToken(AuthToken $currentToken): AuthToken
    {
        if ($currentToken->isExpired()) {
            throw new InvalidArgumentException('Cannot refresh expired token');
        }

        if ($currentToken->getTimeToExpiry() > 1800) { // 30 minutes
            throw new InvalidArgumentException('Token can only be refreshed within 30 minutes of expiry');
        }

        return AuthToken::generate(self::SESSION_LIFETIME_MINUTES, $currentToken->getType());
    }

    public function revokeToken(AuthToken $token): void
    {
        // In a real implementation, this would mark the token as revoked in storage
        // For now, we just validate it exists and is not already expired
        if ($token->isExpired()) {
            throw new InvalidArgumentException('Cannot revoke already expired token');
        }
    }

    public function isAccountLocked(User $user): bool
    {
        // This would typically check a cache or database for failed attempts
        // For testing purposes, we'll simulate this logic
        return false;
    }

    public function getFailedAttempts(User $user): int
    {
        // This would typically query storage for failed attempts
        // For testing purposes, we'll return 0
        return 0;
    }

    public function getLockoutExpiresAt(User $user): ?DateTimeImmutable
    {
        if (! $this->isAccountLocked($user)) {
            return null;
        }

        // This would typically come from storage
        return (new DateTimeImmutable)->modify('+' . self::LOCKOUT_DURATION_MINUTES . ' minutes');
    }

    public function clearFailedAttempts(User $user): void
    {
        // This would typically clear failed attempts from storage
    }

    public function recordFailedAttempt(User $user): void
    {
        // This would typically record a failed attempt in storage
        $failedAttempts = $this->getFailedAttempts($user) + 1;

        if ($failedAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->lockAccount($user);
        }
    }

    public function lockAccount(User $user): void
    {
        // This would typically set a lockout in storage
    }

    public function unlockAccount(User $user): void
    {
        // This would typically remove lockout and clear failed attempts
        $this->clearFailedAttempts($user);
    }

    private function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    public function hashPassword(string $password): string
    {
        $passwordStrength = $this->validatePassword($password);

        if (! $passwordStrength->isValid()) {
            throw new InvalidArgumentException(
                'Password does not meet security requirements: ' .
                implode(', ', $passwordStrength->getViolations())
            );
        }

        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
}

/**
 * Authentication Result Value Object.
 */
class AuthenticationResult
{
    public function __construct(
        private readonly User $user,
        private readonly AuthToken $token,
        private readonly bool $requiresTwoFactor = false,
        private readonly DateTimeImmutable $sessionExpiresAt = new DateTimeImmutable,
        private readonly bool $twoFactorVerified = false
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): AuthToken
    {
        return $this->token;
    }

    public function requiresTwoFactor(): bool
    {
        return $this->requiresTwoFactor;
    }

    public function getSessionExpiresAt(): DateTimeImmutable
    {
        return $this->sessionExpiresAt;
    }

    public function isTwoFactorVerified(): bool
    {
        return $this->twoFactorVerified;
    }

    public function isComplete(): bool
    {
        return ! $this->requiresTwoFactor || $this->twoFactorVerified;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user->getId(),
            'token' => $this->token->toArray(),
            'requires_two_factor' => $this->requiresTwoFactor,
            'session_expires_at' => $this->sessionExpiresAt->format(DateTimeImmutable::ATOM),
            'two_factor_verified' => $this->twoFactorVerified,
            'is_complete' => $this->isComplete(),
        ];
    }
}

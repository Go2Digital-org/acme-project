<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Two-Factor Authentication Code Value Object.
 *
 * Represents a secure 2FA code with expiration and validation.
 */
class TwoFactorCode
{
    private readonly string $code;

    private readonly DateTimeImmutable $expiresAt;

    private readonly string $type;

    private readonly int $attempts;

    public function __construct(
        string $code,
        DateTimeImmutable $expiresAt,
        string $type = 'totp',
        int $attempts = 0
    ) {
        $this->validateCode($code);
        $this->validateType($type);
        $this->validateExpiry($expiresAt);
        $this->validateAttempts($attempts);

        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->type = $type;
        $this->attempts = $attempts;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable;

        return $now > $this->expiresAt;
    }

    public function isValid(?DateTimeImmutable $now = null): bool
    {
        return ! $this->isExpired($now) && ! $this->isBlocked();
    }

    public function isBlocked(): bool
    {
        return $this->attempts >= $this->getMaxAttempts();
    }

    public function getMaxAttempts(): int
    {
        return match ($this->type) {
            'totp' => 3,
            'sms' => 5,
            'email' => 5,
            'backup' => 1,
            default => 3,
        };
    }

    public function getRemainingAttempts(): int
    {
        return max(0, $this->getMaxAttempts() - $this->attempts);
    }

    public function getTimeToExpiry(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable;

        return max(0, $this->expiresAt->getTimestamp() - $now->getTimestamp());
    }

    public function incrementAttempts(): self
    {
        return new self(
            $this->code,
            $this->expiresAt,
            $this->type,
            $this->attempts + 1
        );
    }

    public function matches(string $inputCode): bool
    {
        return hash_equals($this->code, $inputCode);
    }

    public function verify(string $inputCode, ?DateTimeImmutable $now = null): bool
    {
        if (! $this->isValid($now)) {
            return false;
        }

        return $this->matches($inputCode);
    }

    public static function generateTOTP(int $validitySeconds = 300): self
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new DateTimeImmutable)->modify("+{$validitySeconds} seconds");

        return new self($code, $expiresAt, 'totp');
    }

    public static function generateSMS(int $validityMinutes = 10): self
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new DateTimeImmutable)->modify("+{$validityMinutes} minutes");

        return new self($code, $expiresAt, 'sms');
    }

    public static function generateEmail(int $validityMinutes = 15): self
    {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new DateTimeImmutable)->modify("+{$validityMinutes} minutes");

        return new self($code, $expiresAt, 'email');
    }

    public static function generateBackupCode(): self
    {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $expiresAt = (new DateTimeImmutable)->modify('+1 year');

        return new self($code, $expiresAt, 'backup');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'expires_at' => $this->expiresAt->format(DateTimeImmutable::ATOM),
            'type' => $this->type,
            'attempts' => $this->attempts,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'is_blocked' => $this->isBlocked(),
            'remaining_attempts' => $this->getRemainingAttempts(),
        ];
    }

    private function validateCode(string $code): void
    {
        if (trim($code) === '') {
            throw new InvalidArgumentException('2FA code cannot be empty');
        }

        if (strlen($code) < 4) {
            throw new InvalidArgumentException('2FA code must be at least 4 characters long');
        }

        if (strlen($code) > 16) {
            throw new InvalidArgumentException('2FA code cannot be longer than 16 characters');
        }
    }

    private function validateType(string $type): void
    {
        $allowedTypes = ['totp', 'sms', 'email', 'backup'];

        if (! in_array(strtolower($type), $allowedTypes, true)) {
            throw new InvalidArgumentException(
                '2FA code type must be one of: ' . implode(', ', $allowedTypes)
            );
        }
    }

    private function validateExpiry(DateTimeImmutable $expiresAt): void
    {
        $now = new DateTimeImmutable;
        $maxExpiry = $now->modify('+1 year');

        if ($expiresAt > $maxExpiry) {
            throw new InvalidArgumentException('2FA code expiry cannot be more than 1 year in the future');
        }
    }

    private function validateAttempts(int $attempts): void
    {
        if ($attempts < 0) {
            throw new InvalidArgumentException('2FA code attempts cannot be negative');
        }

        if ($attempts > 100) {
            throw new InvalidArgumentException('2FA code attempts cannot exceed 100');
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Authentication Token Value Object.
 *
 * Represents a secure authentication token with expiration and validation.
 */
class AuthToken
{
    private readonly string $token;

    private readonly DateTimeImmutable $expiresAt;

    private readonly string $type;

    public function __construct(
        string $token,
        DateTimeImmutable $expiresAt,
        string $type = 'bearer'
    ) {
        $this->validateToken($token);
        $this->validateType($type);
        $this->validateExpiry($expiresAt);

        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->type = $type;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable;

        return $now > $this->expiresAt;
    }

    public function isValid(?DateTimeImmutable $now = null): bool
    {
        return ! $this->isExpired($now);
    }

    public function getTimeToExpiry(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable;

        return max(0, $this->expiresAt->getTimestamp() - $now->getTimestamp());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'expires_at' => $this->expiresAt->format(DateTimeImmutable::ATOM),
            'type' => $this->type,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['token'],
            new DateTimeImmutable($data['expires_at']),
            $data['type'] ?? 'bearer'
        );
    }

    public static function generate(int $expiryMinutes = 60, string $type = 'bearer'): self
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable)->modify("+{$expiryMinutes} minutes");

        return new self($token, $expiresAt, $type);
    }

    private function validateToken(string $token): void
    {
        if (trim($token) === '') {
            throw new InvalidArgumentException('Token cannot be empty');
        }

        if (strlen($token) < 16) {
            throw new InvalidArgumentException('Token must be at least 16 characters long');
        }

        if (! ctype_alnum($token)) {
            throw new InvalidArgumentException('Token must contain only alphanumeric characters');
        }
    }

    private function validateType(string $type): void
    {
        $allowedTypes = ['bearer', 'api', 'refresh', 'access'];

        if (! in_array(strtolower($type), $allowedTypes, true)) {
            throw new InvalidArgumentException(
                'Token type must be one of: ' . implode(', ', $allowedTypes)
            );
        }
    }

    private function validateExpiry(DateTimeImmutable $expiresAt): void
    {
        $now = new DateTimeImmutable;

        if ($expiresAt <= $now) {
            throw new InvalidArgumentException('Token expiry must be in the future');
        }

        $maxExpiry = $now->modify('+1 year');
        if ($expiresAt > $maxExpiry) {
            throw new InvalidArgumentException('Token expiry cannot be more than 1 year in the future');
        }
    }
}

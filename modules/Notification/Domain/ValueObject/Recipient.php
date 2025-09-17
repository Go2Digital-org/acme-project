<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

use InvalidArgumentException;
use Modules\User\Domain\ValueObject\EmailAddress;
use Stringable;

/**
 * Notification recipient value object
 */
class Recipient implements Stringable
{
    public function __construct(
        public readonly EmailAddress $email,
        public readonly ?string $name = null,
        public readonly ?int $userId = null
    ) {
        if ($name !== null && trim($name) === '') {
            throw new InvalidArgumentException('Recipient name cannot be empty string');
        }

        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
    }

    public static function fromEmail(string $email, ?string $name = null): self
    {
        return new self(new EmailAddress($email), $name);
    }

    public static function fromUser(int $userId, string $email, ?string $name = null): self
    {
        return new self(new EmailAddress($email), $name, $userId);
    }

    public function isRegisteredUser(): bool
    {
        return $this->userId !== null;
    }

    public function getDisplayName(): string
    {
        return $this->name ?? $this->email->getLocalPart();
    }

    public function equals(Recipient $other): bool
    {
        return $this->email->equals($other->email)
            && $this->name === $other->name
            && $this->userId === $other->userId;
    }

    public function __toString(): string
    {
        if ($this->name) {
            return "{$this->name} <{$this->email}>";
        }

        return (string) $this->email;
    }
}

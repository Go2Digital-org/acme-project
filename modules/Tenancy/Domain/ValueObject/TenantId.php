<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringable;

/**
 * Tenant ID Value Object.
 *
 * Represents a unique identifier for a tenant in the system.
 * Uses UUID v4 for global uniqueness.
 */
final readonly class TenantId implements Stringable
{
    private UuidInterface $value;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException("Invalid UUID format: {$value}");
        }

        $this->value = Uuid::fromString($value);
    }

    /**
     * Generate a new random tenant ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    /**
     * Create from an existing UUID string.
     */
    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    /**
     * Get the raw value.
     */
    public function value(): string
    {
        return $this->value->toString();
    }

    /**
     * Get the string representation of the tenant ID.
     */
    public function toString(): string
    {
        return $this->value->toString();
    }

    /**
     * Get the string representation without dashes.
     */
    public function toShortString(): string
    {
        return str_replace('-', '', $this->value->toString());
    }

    /**
     * Check equality with another TenantId.
     */
    public function equals(TenantId $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * String representation for type coercion.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}

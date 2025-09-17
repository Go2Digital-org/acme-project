<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Organization identifier value object
 */
class OrganizationId implements Stringable
{
    public function __construct(
        public readonly int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Organization ID must be a positive integer');
        }
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    public function equals(OrganizationId $other): bool
    {
        return $this->value === $other->value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

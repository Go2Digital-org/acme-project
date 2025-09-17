<?php

declare(strict_types=1);

namespace Modules\Team\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Team identifier value object
 */
class TeamId implements Stringable
{
    public function __construct(
        public readonly int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Team ID must be a positive integer');
        }
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    public function equals(TeamId $other): bool
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

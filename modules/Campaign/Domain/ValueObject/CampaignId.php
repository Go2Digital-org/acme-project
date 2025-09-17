<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Campaign identifier value object
 */
class CampaignId implements Stringable
{
    public function __construct(
        public readonly int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Campaign ID must be a positive integer');
        }
    }

    public static function fromInt(int $id): self
    {
        return new self($id);
    }

    public function equals(CampaignId $other): bool
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

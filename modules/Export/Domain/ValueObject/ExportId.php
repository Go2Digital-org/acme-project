<?php

declare(strict_types=1);

namespace Modules\Export\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringable;

final readonly class ExportId implements Stringable
{
    private function __construct(
        public UuidInterface $value
    ) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    public static function fromString(string $id): self
    {
        if (! Uuid::isValid($id)) {
            throw new InvalidArgumentException("Invalid UUID format: {$id}");
        }

        return new self(Uuid::fromString($id));
    }

    public static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid);
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}

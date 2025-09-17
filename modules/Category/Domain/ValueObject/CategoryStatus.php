<?php

declare(strict_types=1);

namespace Modules\Category\Domain\ValueObject;

enum CategoryStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function active(): self
    {
        return self::ACTIVE;
    }

    public static function inactive(): self
    {
        return self::INACTIVE;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
        };
    }
}

<?php

declare(strict_types=1);

namespace Modules\Import\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class ImportType implements Stringable
{
    private const ALLOWED_TYPES = [
        'campaigns',
        'donations',
        'organizations',
        'users',
        'employees',
    ];

    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function campaigns(): self
    {
        return new self('campaigns');
    }

    public static function donations(): self
    {
        return new self('donations');
    }

    public static function organizations(): self
    {
        return new self('organizations');
    }

    public static function users(): self
    {
        return new self('users');
    }

    public static function employees(): self
    {
        return new self('employees');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ImportType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isCampaigns(): bool
    {
        return $this->value === 'campaigns';
    }

    public function isDonations(): bool
    {
        return $this->value === 'donations';
    }

    public function isOrganizations(): bool
    {
        return $this->value === 'organizations';
    }

    public function isUsers(): bool
    {
        return $this->value === 'users';
    }

    public function isEmployees(): bool
    {
        return $this->value === 'employees';
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (! in_array($this->value, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid import type "%s". Allowed types are: %s',
                    $this->value,
                    implode(', ', self::ALLOWED_TYPES)
                )
            );
        }
    }
}

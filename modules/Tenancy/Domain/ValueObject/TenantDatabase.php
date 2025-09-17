<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Tenant Database Value Object.
 *
 * Represents the database name for a tenant.
 * Ensures valid database naming conventions.
 */
final readonly class TenantDatabase implements Stringable
{
    private string $value;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create from tenant ID.
     */
    public static function fromTenantId(TenantId $tenantId): self
    {
        $databaseName = 'tenant_' . str_replace('-', '_', $tenantId->toString());

        return new self($databaseName);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $database): self
    {
        return new self($database);
    }

    /**
     * Get the database name.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get string representation.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Validate database name.
     *
     * @throws InvalidArgumentException
     */
    private function validate(string $value): void
    {
        if ($value === '' || $value === '0') {
            throw new InvalidArgumentException('Database name cannot be empty');
        }

        if (strlen($value) > 64) {
            throw new InvalidArgumentException('Database name cannot exceed 64 characters');
        }

        if (! preg_match('/^\w+$/', $value)) {
            throw new InvalidArgumentException(
                'Database name can only contain alphanumeric characters and underscores'
            );
        }

        if (preg_match('/^\d/', $value)) {
            throw new InvalidArgumentException('Database name cannot start with a number');
        }
    }

    /**
     * String representation for type coercion.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}

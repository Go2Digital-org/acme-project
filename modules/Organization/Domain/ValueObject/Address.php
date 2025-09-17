<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Address value object for organization contact info
 */
class Address implements Stringable
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $country,
        public readonly ?string $unit = null
    ) {
        if (trim($street) === '') {
            throw new InvalidArgumentException('Street address cannot be empty');
        }

        if (trim($city) === '') {
            throw new InvalidArgumentException('City cannot be empty');
        }

        if (trim($state) === '') {
            throw new InvalidArgumentException('State cannot be empty');
        }

        if (trim($postalCode) === '') {
            throw new InvalidArgumentException('Postal code cannot be empty');
        }

        if (trim($country) === '') {
            throw new InvalidArgumentException('Country cannot be empty');
        }

        if ($unit !== null && trim($unit) === '') {
            throw new InvalidArgumentException('Unit cannot be empty string');
        }
    }

    public static function create(
        string $street,
        string $city,
        string $state,
        string $postalCode,
        string $country,
        ?string $unit = null
    ): self {
        return new self($street, $city, $state, $postalCode, $country, $unit);
    }

    public function hasUnit(): bool
    {
        return $this->unit !== null;
    }

    public function getFullStreetAddress(): string
    {
        if ($this->unit) {
            return "{$this->unit} {$this->street}";
        }

        return $this->street;
    }

    public function getFormattedAddress(): string
    {
        $parts = [
            $this->getFullStreetAddress(),
            "{$this->city}, {$this->state} {$this->postalCode}",
            $this->country,
        ];

        return implode("\n", $parts);
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country
            && $this->unit === $other->unit;
    }

    public function __toString(): string
    {
        return str_replace("\n", ', ', $this->getFormattedAddress());
    }
}

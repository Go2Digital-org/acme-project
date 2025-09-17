<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObject;

use InvalidArgumentException;
use Modules\User\Domain\ValueObject\EmailAddress;
use Stringable;

/**
 * Organization contact information value object
 */
class ContactInfo implements Stringable
{
    public function __construct(
        public readonly EmailAddress $email,
        public readonly ?string $phone = null,
        public readonly ?Address $address = null,
        public readonly ?string $website = null,
        public readonly ?string $contactPerson = null
    ) {
        if ($phone !== null && ! $this->isValidPhone($phone)) {
            throw new InvalidArgumentException('Invalid phone number format');
        }

        if ($website !== null && ! $this->isValidWebsite($website)) {
            throw new InvalidArgumentException('Invalid website URL format');
        }

        if ($contactPerson !== null && trim($contactPerson) === '') {
            throw new InvalidArgumentException('Contact person name cannot be empty');
        }
    }

    public static function minimal(string $email): self
    {
        return new self(new EmailAddress($email));
    }

    public static function complete(
        string $email,
        ?string $phone = null,
        ?Address $address = null,
        ?string $website = null,
        ?string $contactPerson = null
    ): self {
        return new self(
            new EmailAddress($email),
            $phone,
            $address,
            $website,
            $contactPerson
        );
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null;
    }

    public function hasAddress(): bool
    {
        return $this->address instanceof Address;
    }

    public function hasWebsite(): bool
    {
        return $this->website !== null;
    }

    public function hasContactPerson(): bool
    {
        return $this->contactPerson !== null;
    }

    public function equals(ContactInfo $other): bool
    {
        return $this->email->equals($other->email)
            && $this->phone === $other->phone
            && $this->addressEquals($other->address)
            && $this->website === $other->website
            && $this->contactPerson === $other->contactPerson;
    }

    private function addressEquals(?Address $otherAddress): bool
    {
        if (! $this->address instanceof Address && ! $otherAddress instanceof Address) {
            return true;
        }

        if (! $this->address instanceof Address || ! $otherAddress instanceof Address) {
            return false;
        }

        return $this->address->equals($otherAddress);
    }

    public function withPhone(string $phone): self
    {
        return new self(
            $this->email,
            $phone,
            $this->address,
            $this->website,
            $this->contactPerson
        );
    }

    public function withAddress(Address $address): self
    {
        return new self(
            $this->email,
            $this->phone,
            $address,
            $this->website,
            $this->contactPerson
        );
    }

    public function withWebsite(string $website): self
    {
        return new self(
            $this->email,
            $this->phone,
            $this->address,
            $website,
            $this->contactPerson
        );
    }

    public function withContactPerson(string $contactPerson): self
    {
        return new self(
            $this->email,
            $this->phone,
            $this->address,
            $this->website,
            $contactPerson
        );
    }

    private function isValidPhone(string $phone): bool
    {
        // Basic phone validation - can be enhanced
        $cleaned = preg_replace('/[^\d+\-\s\(\)]/', '', $phone);

        return strlen((string) $cleaned) >= 7 && strlen((string) $cleaned) <= 20;
    }

    private function isValidWebsite(string $website): bool
    {
        return filter_var($website, FILTER_VALIDATE_URL) !== false;
    }

    public function __toString(): string
    {
        $parts = [(string) $this->email];

        if ($this->phone) {
            $parts[] = $this->phone;
        }

        if ($this->address instanceof Address) {
            $parts[] = (string) $this->address;
        }

        return implode(' | ', $parts);
    }
}

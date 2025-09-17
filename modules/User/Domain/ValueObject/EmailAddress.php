<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;
use ValueError;

/**
 * Email Address Value Object.
 *
 * Ensures email addresses are valid and normalized.
 */
class EmailAddress implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        $this->validate();
    }

    public function getDomain(): string
    {
        $atPosition = strpos($this->value, '@');

        if ($atPosition === false) {
            throw new InvalidArgumentException('Invalid email format: @ symbol not found');
        }

        return substr($this->value, $atPosition + 1);
    }

    public function getLocalPart(): string
    {
        $atPosition = strpos($this->value, '@');

        if ($atPosition === false) {
            throw new InvalidArgumentException('Invalid email format: @ symbol not found');
        }

        return substr($this->value, 0, $atPosition);
    }

    public function isAcmeEmail(): bool
    {
        $domain = strtolower($this->getDomain());

        return str_ends_with($domain, '.acme.com') || $domain === 'acme.com';
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(): void
    {
        if ($this->value === '' || $this->value === '0') {
            throw new InvalidArgumentException('Email address cannot be empty');
        }

        if (strlen($this->value) > 255) {
            throw new InvalidArgumentException('Email address is too long (max 255 characters)');
        }

        // Convert IDN domain to ASCII for validation
        try {
            $emailForValidation = $this->convertIdnToAscii($this->value);
        } catch (ValueError) {
            throw new InvalidArgumentException("Invalid email address: {$this->value}");
        }

        if (! filter_var($emailForValidation, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$this->value}");
        }
    }

    private function convertIdnToAscii(string $email): string
    {
        $atPosition = strpos($email, '@');

        if ($atPosition === false) {
            return $email;
        }

        $localPart = substr($email, 0, $atPosition);
        $domain = substr($email, $atPosition + 1);

        // Skip IDN conversion if domain is empty
        if ($domain === '') {
            return $email;
        }

        // Convert IDN domain to ASCII if idn_to_ascii is available
        if (function_exists('idn_to_ascii')) {
            try {
                $asciiDomain = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);

                if ($asciiDomain !== false) {
                    return $localPart . '@' . $asciiDomain;
                }
            } catch (ValueError) {
                // Return original email on IDN conversion error
                return $email;
            }
        }

        return $email;
    }
}

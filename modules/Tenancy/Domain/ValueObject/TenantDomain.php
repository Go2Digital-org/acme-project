<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Tenant Domain Value Object.
 *
 * Represents a subdomain for a tenant.
 * Validates domain format and ensures uniqueness constraints.
 */
final readonly class TenantDomain implements Stringable
{
    private string $subdomain;

    private string $baseDomain;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $subdomain, string $baseDomain = 'acme-corp-optimy.test')
    {
        $this->validateSubdomain($subdomain);
        $this->validateBaseDomain($baseDomain);

        $this->subdomain = strtolower($subdomain);
        $this->baseDomain = strtolower($baseDomain);
    }

    /**
     * Create from subdomain only.
     */
    public static function fromSubdomain(string $subdomain): self
    {
        return new self($subdomain, config('tenancy.central_domains.0', 'acme-corp-optimy.test'));
    }

    /**
     * Create from full domain.
     */
    public static function fromFullDomain(string $fullDomain): self
    {
        $parts = explode('.', $fullDomain);

        if (count($parts) < 3) {
            throw new InvalidArgumentException("Invalid domain format: {$fullDomain}");
        }

        $subdomain = array_shift($parts);
        $baseDomain = implode('.', $parts);

        return new self($subdomain, $baseDomain);
    }

    /**
     * Get the subdomain part.
     */
    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    /**
     * Get the base domain.
     */
    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    /**
     * Get the full domain (subdomain.basedomain).
     */
    public function getFullDomain(): string
    {
        return $this->subdomain . '.' . $this->baseDomain;
    }

    /**
     * Check if this is a reserved subdomain.
     */
    public function isReserved(): bool
    {
        $reserved = [
            'www', 'admin', 'api', 'app', 'mail',
            'ftp', 'ssh', 'test', 'dev', 'staging',
            'production', 'demo', 'portal', 'secure',
        ];

        return in_array($this->subdomain, $reserved, true);
    }

    /**
     * Validate subdomain format.
     *
     * @throws InvalidArgumentException
     */
    private function validateSubdomain(string $subdomain): void
    {
        if ($subdomain === '' || $subdomain === '0') {
            throw new InvalidArgumentException('Subdomain cannot be empty');
        }

        if (strlen($subdomain) < 3) {
            throw new InvalidArgumentException('Subdomain must be at least 3 characters long');
        }

        if (strlen($subdomain) > 63) {
            throw new InvalidArgumentException('Subdomain cannot exceed 63 characters');
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', strtolower($subdomain))) {
            throw new InvalidArgumentException(
                'Subdomain can only contain lowercase letters, numbers, and hyphens. ' .
                'It must start and end with a letter or number.'
            );
        }
    }

    /**
     * Validate base domain format.
     *
     * @throws InvalidArgumentException
     */
    private function validateBaseDomain(string $baseDomain): void
    {
        if ($baseDomain === '' || $baseDomain === '0') {
            throw new InvalidArgumentException('Base domain cannot be empty');
        }

        if (! filter_var('http://' . $baseDomain, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid base domain format: {$baseDomain}");
        }
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->getFullDomain();
    }
}

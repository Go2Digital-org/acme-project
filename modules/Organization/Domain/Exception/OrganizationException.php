<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Exception;

use DomainException;
use Modules\Organization\Domain\Model\Organization;

final class OrganizationException extends DomainException
{
    public static function alreadyVerified(Organization $organization): self
    {
        return new self(
            sprintf('Organization "%s" is already verified.', $organization->getName()),
        );
    }

    public static function notVerified(Organization $organization): self
    {
        return new self(
            sprintf('Organization "%s" is not verified.', $organization->getName()),
        );
    }

    public static function alreadyActive(Organization $organization): self
    {
        return new self(
            sprintf('Organization "%s" is already active.', $organization->getName()),
        );
    }

    public static function alreadyInactive(Organization $organization): self
    {
        return new self(
            sprintf('Organization "%s" is already inactive.', $organization->getName()),
        );
    }

    public static function cannotCreateCampaigns(Organization $organization): self
    {
        return new self(
            sprintf(
                'Organization "%s" cannot create campaigns. It must be both active and verified.',
                $organization->getName(),
            ),
        );
    }

    public static function invalidEmail(string $email): self
    {
        return new self(
            sprintf('Invalid email address: "%s".', $email),
        );
    }

    public static function invalidWebsite(string $website): self
    {
        return new self(
            sprintf('Invalid website URL: "%s".', $website),
        );
    }

    public static function invalidPhone(string $phone): self
    {
        return new self(
            sprintf('Invalid phone number: "%s".', $phone),
        );
    }

    public static function duplicateRegistrationNumber(string $registrationNumber): self
    {
        return new self(
            sprintf('An organization with registration number "%s" already exists.', $registrationNumber),
        );
    }

    public static function duplicateTaxId(string $taxId): self
    {
        return new self(
            sprintf('An organization with tax ID "%s" already exists.', $taxId),
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function missingRequiredFields(array $fields): self
    {
        return new self(
            sprintf('Missing required fields: %s.', implode(', ', $fields)),
        );
    }

    public static function notFound(?int $id = null): self
    {
        return $id
            ? new self("Organization with ID {$id} not found")
            : new self('Organization not found');
    }

    public static function unauthorizedAccess(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Unauthorized access to organization '{$organization->getName()}'")
            : new self('Unauthorized access to organization');
    }

    public static function cannotActivate(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Organization '{$organization->getName()}' cannot be activated")
            : new self('Organization cannot be activated');
    }

    public static function cannotDeactivate(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Organization '{$organization->getName()}' cannot be deactivated")
            : new self('Organization cannot be deactivated');
    }

    public static function cannotUpdate(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Organization '{$organization->getName()}' cannot be updated")
            : new self('Organization cannot be updated');
    }

    public static function cannotVerify(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Organization '{$organization->getName()}' cannot be verified")
            : new self('Organization cannot be verified');
    }

    public static function notEligibleForVerification(?Organization $organization = null): self
    {
        return $organization instanceof Organization
            ? new self("Organization '{$organization->getName()}' is not eligible for verification")
            : new self('Organization is not eligible for verification');
    }

    public static function nameAlreadyExists(string $name): self
    {
        return new self(
            sprintf('An organization with name "%s" already exists.', $name),
        );
    }

    public static function registrationNumberAlreadyExists(string $registrationNumber): self
    {
        return new self(
            sprintf('An organization with registration number "%s" already exists.', $registrationNumber),
        );
    }

    public static function taxIdAlreadyExists(string $taxId): self
    {
        return new self(
            sprintf('An organization with tax ID "%s" already exists.', $taxId),
        );
    }
}

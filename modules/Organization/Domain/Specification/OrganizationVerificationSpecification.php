<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Specification;

use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Domain\Contract\OrganizationInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if an organization can be verified.
 *
 * An organization can be verified when:
 * - It is currently active
 * - It has a valid name (not default/empty)
 * - It has a valid registration number
 * - It has a valid tax ID
 * - It has a valid email address
 * - It has a category assigned
 * - It is not already verified
 */
class OrganizationVerificationSpecification extends CompositeSpecification
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        // Support both interface and concrete class for backwards compatibility
        if (! ($candidate instanceof Organization) && ! ($candidate instanceof OrganizationInterface)) {
            return false;
        }

        // Cast to concrete class to access properties
        if (! ($candidate instanceof Organization)) {
            // This is an interface implementation, we need to convert or handle differently
            return false;
        }

        $org = $candidate;

        // Organization must not already be verified
        if ($org->is_verified) {
            return false;
        }

        // Organization must be active
        if (! $org->is_active) {
            return false;
        }

        // Must have valid basic information
        $name = $candidate->getName();
        if (in_array(trim($name), ['', '0'], true) || $name === 'Unnamed Organization') {
            return false;
        }

        // Must have registration number
        if (in_array(trim($candidate->registration_number ?? ''), ['', '0'], true)) {
            return false;
        }

        // Must have tax ID
        if (in_array(trim($candidate->tax_id ?? ''), ['', '0'], true)) {
            return false;
        }

        // Must have valid email
        if (in_array(trim($org->email ?? ''), ['', '0'], true)) {
            return false;
        }

        // Validate email format
        if (! filter_var($org->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Must have category
        return ! in_array(trim($candidate->category ?? ''), ['', '0'], true);
    }
}

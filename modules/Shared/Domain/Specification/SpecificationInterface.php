<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

/**
 * Core specification interface for implementing business rules.
 *
 * The Specification Pattern encapsulates business rules in a reusable,
 * composable way that can be easily tested and maintained.
 */
interface SpecificationInterface
{
    /**
     * Check if the candidate satisfies this specification.
     *
     * @param  mixed  $candidate  The object to evaluate against this specification
     * @return bool True if the candidate satisfies the specification
     */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * Combine this specification with another using AND logic.
     *
     * @param  SpecificationInterface  $specification  The specification to combine with
     * @return SpecificationInterface A new specification representing AND logic
     */
    public function and(SpecificationInterface $specification): SpecificationInterface;

    /**
     * Combine this specification with another using OR logic.
     *
     * @param  SpecificationInterface  $specification  The specification to combine with
     * @return SpecificationInterface A new specification representing OR logic
     */
    public function or(SpecificationInterface $specification): SpecificationInterface;

    /**
     * Negate this specification using NOT logic.
     *
     * @return SpecificationInterface A new specification representing NOT logic
     */
    public function not(): SpecificationInterface;
}

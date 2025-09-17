<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

/**
 * NOT specification that negates another specification using logical NOT.
 *
 * This specification is satisfied when the wrapped specification
 * is NOT satisfied by the candidate.
 */
class NotSpecification extends CompositeSpecification
{
    public function __construct(
        private readonly SpecificationInterface $specification
    ) {}

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return ! $this->specification->isSatisfiedBy($candidate);
    }
}

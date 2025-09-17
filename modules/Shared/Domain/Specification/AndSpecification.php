<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

/**
 * AND specification that combines two specifications using logical AND.
 *
 * This specification is satisfied when both left and right specifications
 * are satisfied by the candidate.
 */
class AndSpecification extends CompositeSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right
    ) {}

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

/**
 * OR specification that combines two specifications using logical OR.
 *
 * This specification is satisfied when either left or right specification
 * is satisfied by the candidate.
 */
class OrSpecification extends CompositeSpecification
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
        if ($this->left->isSatisfiedBy($candidate)) {
            return true;
        }

        return $this->right->isSatisfiedBy($candidate);
    }
}

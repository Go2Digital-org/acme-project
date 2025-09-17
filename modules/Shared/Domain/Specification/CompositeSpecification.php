<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

/**
 * Abstract base class for specifications that provides composite operations.
 *
 * This class implements the composite pattern for specifications,
 * allowing them to be combined using AND, OR, and NOT operations.
 */
abstract class CompositeSpecification implements SpecificationInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * {@inheritdoc}
     */
    public function and(SpecificationInterface $specification): SpecificationInterface
    {
        return new AndSpecification($this, $specification);
    }

    /**
     * {@inheritdoc}
     */
    public function or(SpecificationInterface $specification): SpecificationInterface
    {
        return new OrSpecification($this, $specification);
    }

    /**
     * {@inheritdoc}
     */
    public function not(): SpecificationInterface
    {
        return new NotSpecification($this);
    }
}

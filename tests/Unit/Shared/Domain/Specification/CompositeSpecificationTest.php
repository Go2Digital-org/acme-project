<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Specification;

use Modules\Shared\Domain\Specification\CompositeSpecification;
use PHPUnit\Framework\TestCase;

class CompositeSpecificationTest extends TestCase
{
    private MockSpecification $trueSpec;

    private MockSpecification $falseSpec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trueSpec = new MockSpecification(true);
        $this->falseSpec = new MockSpecification(false);
    }

    public function test_and_specification_returns_true_when_both_specs_are_satisfied(): void
    {
        $andSpec = $this->trueSpec->and($this->trueSpec);

        $this->assertTrue($andSpec->isSatisfiedBy('test'));
    }

    public function test_and_specification_returns_false_when_left_spec_not_satisfied(): void
    {
        $andSpec = $this->falseSpec->and($this->trueSpec);

        $this->assertFalse($andSpec->isSatisfiedBy('test'));
    }

    public function test_and_specification_returns_false_when_right_spec_not_satisfied(): void
    {
        $andSpec = $this->trueSpec->and($this->falseSpec);

        $this->assertFalse($andSpec->isSatisfiedBy('test'));
    }

    public function test_and_specification_returns_false_when_both_specs_not_satisfied(): void
    {
        $andSpec = $this->falseSpec->and($this->falseSpec);

        $this->assertFalse($andSpec->isSatisfiedBy('test'));
    }

    public function test_or_specification_returns_true_when_left_spec_satisfied(): void
    {
        $orSpec = $this->trueSpec->or($this->falseSpec);

        $this->assertTrue($orSpec->isSatisfiedBy('test'));
    }

    public function test_or_specification_returns_true_when_right_spec_satisfied(): void
    {
        $orSpec = $this->falseSpec->or($this->trueSpec);

        $this->assertTrue($orSpec->isSatisfiedBy('test'));
    }

    public function test_or_specification_returns_true_when_both_specs_satisfied(): void
    {
        $orSpec = $this->trueSpec->or($this->trueSpec);

        $this->assertTrue($orSpec->isSatisfiedBy('test'));
    }

    public function test_or_specification_returns_false_when_both_specs_not_satisfied(): void
    {
        $orSpec = $this->falseSpec->or($this->falseSpec);

        $this->assertFalse($orSpec->isSatisfiedBy('test'));
    }

    public function test_not_specification_returns_false_when_spec_satisfied(): void
    {
        $notSpec = $this->trueSpec->not();

        $this->assertFalse($notSpec->isSatisfiedBy('test'));
    }

    public function test_not_specification_returns_true_when_spec_not_satisfied(): void
    {
        $notSpec = $this->falseSpec->not();

        $this->assertTrue($notSpec->isSatisfiedBy('test'));
    }

    public function test_complex_specification_combinations(): void
    {
        // (true AND false) OR (true AND true) = false OR true = true
        $complexSpec = $this->trueSpec->and($this->falseSpec)
            ->or($this->trueSpec->and($this->trueSpec));

        $this->assertTrue($complexSpec->isSatisfiedBy('test'));
    }

    public function test_nested_not_specification(): void
    {
        // NOT(NOT(true)) = NOT(false) = true
        $nestedNotSpec = $this->trueSpec->not()->not();

        $this->assertTrue($nestedNotSpec->isSatisfiedBy('test'));
    }
}

/**
 * Mock specification for testing composite operations.
 */
class MockSpecification extends CompositeSpecification
{
    public function __construct(private readonly bool $result) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->result;
    }
}

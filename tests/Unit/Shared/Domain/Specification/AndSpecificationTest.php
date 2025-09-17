<?php

declare(strict_types=1);

use Modules\Shared\Domain\Specification\AndSpecification;
use Modules\Shared\Domain\Specification\SpecificationInterface;

describe('AndSpecification', function () {
    beforeEach(function () {
        $this->trueSpec = new class implements SpecificationInterface
        {
            public function isSatisfiedBy(mixed $candidate): bool
            {
                return true;
            }

            public function and(SpecificationInterface $specification): SpecificationInterface
            {
                return new AndSpecification($this, $specification);
            }

            public function or(SpecificationInterface $specification): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
            }

            public function not(): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\NotSpecification($this);
            }
        };

        $this->falseSpec = new class implements SpecificationInterface
        {
            public function isSatisfiedBy(mixed $candidate): bool
            {
                return false;
            }

            public function and(SpecificationInterface $specification): SpecificationInterface
            {
                return new AndSpecification($this, $specification);
            }

            public function or(SpecificationInterface $specification): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
            }

            public function not(): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\NotSpecification($this);
            }
        };

        $this->conditionalSpec = new class implements SpecificationInterface
        {
            public function isSatisfiedBy(mixed $candidate): bool
            {
                return is_int($candidate) && $candidate > 10;
            }

            public function and(SpecificationInterface $specification): SpecificationInterface
            {
                return new AndSpecification($this, $specification);
            }

            public function or(SpecificationInterface $specification): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
            }

            public function not(): SpecificationInterface
            {
                return new \Modules\Shared\Domain\Specification\NotSpecification($this);
            }
        };
    });

    describe('Construction', function () {
        it('creates AND specification with two specifications', function () {
            $andSpec = new AndSpecification($this->trueSpec, $this->falseSpec);

            expect($andSpec)->toBeInstanceOf(AndSpecification::class)
                ->and($andSpec)->toBeInstanceOf(SpecificationInterface::class);
        });
    });

    describe('Logical AND Operations', function () {
        it('returns true when both specifications are true', function () {
            $andSpec = new AndSpecification($this->trueSpec, $this->trueSpec);

            expect($andSpec->isSatisfiedBy('any_candidate'))->toBeTrue();
        });

        it('returns false when left specification is false', function () {
            $andSpec = new AndSpecification($this->falseSpec, $this->trueSpec);

            expect($andSpec->isSatisfiedBy('any_candidate'))->toBeFalse();
        });

        it('returns false when right specification is false', function () {
            $andSpec = new AndSpecification($this->trueSpec, $this->falseSpec);

            expect($andSpec->isSatisfiedBy('any_candidate'))->toBeFalse();
        });

        it('returns false when both specifications are false', function () {
            $andSpec = new AndSpecification($this->falseSpec, $this->falseSpec);

            expect($andSpec->isSatisfiedBy('any_candidate'))->toBeFalse();
        });
    });

    describe('Short-circuit Evaluation', function () {
        it('evaluates left specification first', function () {
            $leftEvaluated = false;
            $rightEvaluated = false;

            $leftSpec = new class($leftEvaluated) implements SpecificationInterface
            {
                public function __construct(private bool &$evaluated) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->evaluated = true;

                    return false; // Always false to test short-circuit
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $rightSpec = new class($rightEvaluated) implements SpecificationInterface
            {
                public function __construct(private bool &$evaluated) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->evaluated = true;

                    return true;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $andSpec = new AndSpecification($leftSpec, $rightSpec);
            $result = $andSpec->isSatisfiedBy('test');

            expect($result)->toBeFalse()
                ->and($leftEvaluated)->toBeTrue()
                ->and($rightEvaluated)->toBeFalse(); // PHP short-circuits, right spec not evaluated
        });
    });

    describe('Complex Specifications', function () {
        it('works with conditional specifications', function () {
            $positiveSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_int($candidate) && $candidate > 0;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $evenSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_int($candidate) && $candidate % 2 === 0;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $positiveEvenSpec = new AndSpecification($positiveSpec, $evenSpec);

            expect($positiveEvenSpec->isSatisfiedBy(4))->toBeTrue() // positive and even
                ->and($positiveEvenSpec->isSatisfiedBy(3))->toBeFalse() // positive but odd
                ->and($positiveEvenSpec->isSatisfiedBy(-2))->toBeFalse() // even but negative
                ->and($positiveEvenSpec->isSatisfiedBy(0))->toBeFalse() // even but not positive
                ->and($positiveEvenSpec->isSatisfiedBy('string'))->toBeFalse(); // not integer
        });
    });

    describe('Different Data Types', function () {
        it('handles string candidates', function () {
            $lengthSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_string($candidate) && strlen($candidate) > 5;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $containsSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_string($candidate) && str_contains($candidate, 'test');
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $longContainsTestSpec = new AndSpecification($lengthSpec, $containsSpec);

            expect($longContainsTestSpec->isSatisfiedBy('testing123'))->toBeTrue()
                ->and($longContainsTestSpec->isSatisfiedBy('test'))->toBeFalse() // too short
                ->and($longContainsTestSpec->isSatisfiedBy('longstring'))->toBeFalse() // no 'test'
                ->and($longContainsTestSpec->isSatisfiedBy('short'))->toBeFalse(); // both conditions fail
        });

        it('handles array candidates', function () {
            $countSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && count($candidate) >= 3;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $hasKeySpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && array_key_exists('required', $candidate);
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $largeArrayWithKeySpec = new AndSpecification($countSpec, $hasKeySpec);

            expect($largeArrayWithKeySpec->isSatisfiedBy(['a', 'b', 'required' => 'value']))->toBeTrue()
                ->and($largeArrayWithKeySpec->isSatisfiedBy(['required' => 'value']))->toBeFalse() // too small
                ->and($largeArrayWithKeySpec->isSatisfiedBy(['a', 'b', 'c']))->toBeFalse() // no required key
                ->and($largeArrayWithKeySpec->isSatisfiedBy(['a', 'b']))->toBeFalse(); // both conditions fail
        });

        it('handles object candidates', function () {
            $obj1 = new stdClass;
            $obj1->name = 'test';
            $obj1->value = 100;

            $obj2 = new stdClass;
            $obj2->name = 'other';
            $obj2->value = 50;

            $nameSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_object($candidate) && property_exists($candidate, 'name') && $candidate->name === 'test';
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $valueSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_object($candidate) && property_exists($candidate, 'value') && $candidate->value > 75;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $testObjectWithHighValueSpec = new AndSpecification($nameSpec, $valueSpec);

            expect($testObjectWithHighValueSpec->isSatisfiedBy($obj1))->toBeTrue()
                ->and($testObjectWithHighValueSpec->isSatisfiedBy($obj2))->toBeFalse();
        });
    });

    describe('Null and Edge Cases', function () {
        it('handles null candidates', function () {
            $nullSafeSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate !== null;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $andSpec = new AndSpecification($nullSafeSpec, $this->trueSpec);

            expect($andSpec->isSatisfiedBy(null))->toBeFalse()
                ->and($andSpec->isSatisfiedBy('not null'))->toBeTrue();
        });

        it('handles false values correctly', function () {
            $booleanSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_bool($candidate);
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $andSpec = new AndSpecification($booleanSpec, $this->trueSpec);

            expect($andSpec->isSatisfiedBy(true))->toBeTrue()
                ->and($andSpec->isSatisfiedBy(false))->toBeTrue() // false is a boolean
                ->and($andSpec->isSatisfiedBy(0))->toBeFalse(); // 0 is not a boolean
        });

        it('handles empty collections', function () {
            $nonEmptySpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    if (is_array($candidate)) {
                        return count($candidate) > 0;
                    }
                    if (is_string($candidate)) {
                        return strlen($candidate) > 0;
                    }

                    return true;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new \Modules\Shared\Domain\Specification\NotSpecification($this);
                }
            };

            $andSpec = new AndSpecification($nonEmptySpec, $this->trueSpec);

            expect($andSpec->isSatisfiedBy([]))->toBeFalse()
                ->and($andSpec->isSatisfiedBy(''))->toBeFalse()
                ->and($andSpec->isSatisfiedBy(['item']))->toBeTrue()
                ->and($andSpec->isSatisfiedBy('text'))->toBeTrue();
        });
    });
});

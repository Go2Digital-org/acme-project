<?php

declare(strict_types=1);

use Modules\Shared\Domain\Specification\AndSpecification;
use Modules\Shared\Domain\Specification\NotSpecification;
use Modules\Shared\Domain\Specification\OrSpecification;
use Modules\Shared\Domain\Specification\SpecificationInterface;

describe('OrSpecification', function () {
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
                return new OrSpecification($this, $specification);
            }

            public function not(): SpecificationInterface
            {
                return new NotSpecification($this);
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
                return new OrSpecification($this, $specification);
            }

            public function not(): SpecificationInterface
            {
                return new NotSpecification($this);
            }
        };
    });

    describe('Construction', function () {
        it('creates OR specification with two specifications', function () {
            $orSpec = new OrSpecification($this->trueSpec, $this->falseSpec);

            expect($orSpec)->toBeInstanceOf(OrSpecification::class)
                ->and($orSpec)->toBeInstanceOf(SpecificationInterface::class);
        });
    });

    describe('Logical OR Operations', function () {
        it('returns true when both specifications are true', function () {
            $orSpec = new OrSpecification($this->trueSpec, $this->trueSpec);

            expect($orSpec->isSatisfiedBy('any_candidate'))->toBeTrue();
        });

        it('returns true when left specification is true', function () {
            $orSpec = new OrSpecification($this->trueSpec, $this->falseSpec);

            expect($orSpec->isSatisfiedBy('any_candidate'))->toBeTrue();
        });

        it('returns true when right specification is true', function () {
            $orSpec = new OrSpecification($this->falseSpec, $this->trueSpec);

            expect($orSpec->isSatisfiedBy('any_candidate'))->toBeTrue();
        });

        it('returns false when both specifications are false', function () {
            $orSpec = new OrSpecification($this->falseSpec, $this->falseSpec);

            expect($orSpec->isSatisfiedBy('any_candidate'))->toBeFalse();
        });
    });

    describe('Short-circuit Evaluation', function () {
        it('short-circuits when left specification is true', function () {
            $leftEvaluated = false;
            $rightEvaluated = false;

            $leftSpec = new class($leftEvaluated) implements SpecificationInterface
            {
                public function __construct(private bool &$evaluated) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->evaluated = true;

                    return true; // Always true to test short-circuit
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $rightSpec = new class($rightEvaluated) implements SpecificationInterface
            {
                public function __construct(private bool &$evaluated) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->evaluated = true;

                    return false;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $orSpec = new OrSpecification($leftSpec, $rightSpec);
            $result = $orSpec->isSatisfiedBy('test');

            expect($result)->toBeTrue()
                ->and($leftEvaluated)->toBeTrue()
                ->and($rightEvaluated)->toBeFalse(); // Should short-circuit
        });

        it('evaluates both when left specification is false', function () {
            $leftEvaluated = false;
            $rightEvaluated = false;

            $leftSpec = new class($leftEvaluated) implements SpecificationInterface
            {
                public function __construct(private bool &$evaluated) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->evaluated = true;

                    return false;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
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
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $orSpec = new OrSpecification($leftSpec, $rightSpec);
            $result = $orSpec->isSatisfiedBy('test');

            expect($result)->toBeTrue()
                ->and($leftEvaluated)->toBeTrue()
                ->and($rightEvaluated)->toBeTrue(); // Both should be evaluated
        });
    });

    describe('Complex Specifications', function () {
        it('works with conditional specifications for inclusive conditions', function () {
            $adminSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && isset($candidate['role']) && $candidate['role'] === 'admin';
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $ownerSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && isset($candidate['is_owner']) && $candidate['is_owner'] === true;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $canAccessSpec = new OrSpecification($adminSpec, $ownerSpec);

            $admin = ['role' => 'admin', 'is_owner' => false];
            $owner = ['role' => 'user', 'is_owner' => true];
            $adminOwner = ['role' => 'admin', 'is_owner' => true];
            $regular = ['role' => 'user', 'is_owner' => false];

            expect($canAccessSpec->isSatisfiedBy($admin))->toBeTrue()
                ->and($canAccessSpec->isSatisfiedBy($owner))->toBeTrue()
                ->and($canAccessSpec->isSatisfiedBy($adminOwner))->toBeTrue()
                ->and($canAccessSpec->isSatisfiedBy($regular))->toBeFalse();
        });

        it('combines with different data type specifications', function () {
            $stringSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_string($candidate) && strlen($candidate) > 0;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $positiveNumberSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_numeric($candidate) && $candidate > 0;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $validInputSpec = new OrSpecification($stringSpec, $positiveNumberSpec);

            expect($validInputSpec->isSatisfiedBy('valid string'))->toBeTrue()
                ->and($validInputSpec->isSatisfiedBy(42))->toBeTrue()
                ->and($validInputSpec->isSatisfiedBy(3.14))->toBeTrue()
                ->and($validInputSpec->isSatisfiedBy(''))->toBeFalse() // empty string
                ->and($validInputSpec->isSatisfiedBy(0))->toBeFalse() // zero
                ->and($validInputSpec->isSatisfiedBy(-5))->toBeFalse() // negative
                ->and($validInputSpec->isSatisfiedBy(null))->toBeFalse(); // null
        });
    });

    describe('Nested OR Specifications', function () {
        it('handles multiple OR conditions', function () {
            $spec1 = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === 'A';
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $spec2 = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === 'B';
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $spec3 = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === 'C';
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            // (A OR B) OR C
            $orSpec1 = new OrSpecification($spec1, $spec2);
            $orSpec2 = new OrSpecification($orSpec1, $spec3);

            expect($orSpec2->isSatisfiedBy('A'))->toBeTrue()
                ->and($orSpec2->isSatisfiedBy('B'))->toBeTrue()
                ->and($orSpec2->isSatisfiedBy('C'))->toBeTrue()
                ->and($orSpec2->isSatisfiedBy('D'))->toBeFalse();
        });
    });

    describe('Performance Characteristics', function () {
        it('evaluates efficiently with complex conditions', function () {
            $expensiveSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    // Simulate expensive operation
                    usleep(1000); // 1ms delay

                    return false;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $quickSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return true; // Quick and always true
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $orSpec = new OrSpecification($quickSpec, $expensiveSpec);

            $start = microtime(true);
            $result = $orSpec->isSatisfiedBy('test');
            $duration = microtime(true) - $start;

            expect($result)->toBeTrue()
                ->and($duration)->toBeLessThan(0.0005); // Should be much faster than 1ms due to short-circuit
        });
    });

    describe('Edge Cases with Collections', function () {
        it('handles empty and non-empty collection conditions', function () {
            $emptyArraySpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && count($candidate) === 0;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $hasSpecificKeySpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate) && array_key_exists('special', $candidate);
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $allowedArraySpec = new OrSpecification($emptyArraySpec, $hasSpecificKeySpec);

            expect($allowedArraySpec->isSatisfiedBy([]))->toBeTrue() // empty array
                ->and($allowedArraySpec->isSatisfiedBy(['special' => 'value']))->toBeTrue() // has special key
                ->and($allowedArraySpec->isSatisfiedBy(['special' => 'value', 'other' => 'data']))->toBeTrue() // has special key with other data
                ->and($allowedArraySpec->isSatisfiedBy(['other' => 'data']))->toBeFalse() // non-empty without special key
                ->and($allowedArraySpec->isSatisfiedBy(['a', 'b', 'c']))->toBeFalse(); // non-empty indexed array
        });
    });

    describe('Type Safety', function () {
        it('handles mixed type specifications safely', function () {
            $nullSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === null;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $booleanFalseSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === false;
                }

                public function and(SpecificationInterface $specification): SpecificationInterface
                {
                    return new AndSpecification($this, $specification);
                }

                public function or(SpecificationInterface $specification): SpecificationInterface
                {
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $falsyValueSpec = new OrSpecification($nullSpec, $booleanFalseSpec);

            expect($falsyValueSpec->isSatisfiedBy(null))->toBeTrue()
                ->and($falsyValueSpec->isSatisfiedBy(false))->toBeTrue()
                ->and($falsyValueSpec->isSatisfiedBy(0))->toBeFalse() // 0 is falsy but not null or false
                ->and($falsyValueSpec->isSatisfiedBy(''))->toBeFalse() // empty string is falsy but not null or false
                ->and($falsyValueSpec->isSatisfiedBy([]))->toBeFalse() // empty array is falsy but not null or false
                ->and($falsyValueSpec->isSatisfiedBy(true))->toBeFalse();
        });
    });
});

<?php

declare(strict_types=1);

use Modules\Shared\Domain\Specification\AndSpecification;
use Modules\Shared\Domain\Specification\NotSpecification;
use Modules\Shared\Domain\Specification\OrSpecification;
use Modules\Shared\Domain\Specification\SpecificationInterface;

describe('NotSpecification', function () {
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
        it('creates NOT specification with a specification', function () {
            $notSpec = new NotSpecification($this->trueSpec);

            expect($notSpec)->toBeInstanceOf(NotSpecification::class)
                ->and($notSpec)->toBeInstanceOf(SpecificationInterface::class);
        });
    });

    describe('Logical NOT Operations', function () {
        it('returns false when wrapped specification is true', function () {
            $notSpec = new NotSpecification($this->trueSpec);

            expect($notSpec->isSatisfiedBy('any_candidate'))->toBeFalse();
        });

        it('returns true when wrapped specification is false', function () {
            $notSpec = new NotSpecification($this->falseSpec);

            expect($notSpec->isSatisfiedBy('any_candidate'))->toBeTrue();
        });
    });

    describe('Double Negation', function () {
        it('handles double negation correctly', function () {
            $notNotTrueSpec = new NotSpecification(new NotSpecification($this->trueSpec));
            $notNotFalseSpec = new NotSpecification(new NotSpecification($this->falseSpec));

            expect($notNotTrueSpec->isSatisfiedBy('test'))->toBeTrue() // NOT(NOT(true)) = true
                ->and($notNotFalseSpec->isSatisfiedBy('test'))->toBeFalse(); // NOT(NOT(false)) = false
        });

        it('handles triple negation correctly', function () {
            $tripleNotTrueSpec = new NotSpecification(new NotSpecification(new NotSpecification($this->trueSpec)));
            $tripleNotFalseSpec = new NotSpecification(new NotSpecification(new NotSpecification($this->falseSpec)));

            expect($tripleNotTrueSpec->isSatisfiedBy('test'))->toBeFalse() // NOT(NOT(NOT(true))) = false
                ->and($tripleNotFalseSpec->isSatisfiedBy('test'))->toBeTrue(); // NOT(NOT(NOT(false))) = true
        });
    });

    describe('Complex Specifications', function () {
        it('negates conditional specifications correctly', function () {
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
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $notPositiveSpec = new NotSpecification($positiveSpec);

            expect($notPositiveSpec->isSatisfiedBy(5))->toBeFalse() // 5 is positive, so NOT positive = false
                ->and($notPositiveSpec->isSatisfiedBy(-3))->toBeTrue() // -3 is not positive, so NOT positive = true
                ->and($notPositiveSpec->isSatisfiedBy(0))->toBeTrue() // 0 is not positive, so NOT positive = true
                ->and($notPositiveSpec->isSatisfiedBy('string'))->toBeTrue(); // string is not positive, so NOT positive = true
        });

        it('works with string specifications', function () {
            $containsTestSpec = new class implements SpecificationInterface
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
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            $doesNotContainTestSpec = new NotSpecification($containsTestSpec);

            expect($doesNotContainTestSpec->isSatisfiedBy('testing'))->toBeFalse() // contains 'test'
                ->and($doesNotContainTestSpec->isSatisfiedBy('hello world'))->toBeTrue() // doesn't contain 'test'
                ->and($doesNotContainTestSpec->isSatisfiedBy('TEST'))->toBeTrue() // case sensitive, doesn't contain 'test'
                ->and($doesNotContainTestSpec->isSatisfiedBy(123))->toBeTrue(); // not a string, so doesn't contain 'test'
        });

        it('works with array specifications', function () {
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

            $notEmptyArraySpec = new NotSpecification($emptyArraySpec);

            expect($notEmptyArraySpec->isSatisfiedBy([]))->toBeFalse() // empty array
                ->and($notEmptyArraySpec->isSatisfiedBy(['item']))->toBeTrue() // not empty array
                ->and($notEmptyArraySpec->isSatisfiedBy('string'))->toBeTrue(); // not an array, so not empty array
        });
    });

    describe('Combining with Other Specifications', function () {
        it('works in AND combinations', function () {
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
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
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
                    return new OrSpecification($this, $specification);
                }

                public function not(): SpecificationInterface
                {
                    return new NotSpecification($this);
                }
            };

            // Positive AND NOT even = positive odd numbers
            $positiveOddSpec = new AndSpecification($positiveSpec, new NotSpecification($evenSpec));

            expect($positiveOddSpec->isSatisfiedBy(1))->toBeTrue() // positive and odd
                ->and($positiveOddSpec->isSatisfiedBy(3))->toBeTrue() // positive and odd
                ->and($positiveOddSpec->isSatisfiedBy(5))->toBeTrue() // positive and odd
                ->and($positiveOddSpec->isSatisfiedBy(2))->toBeFalse() // positive but even
                ->and($positiveOddSpec->isSatisfiedBy(4))->toBeFalse() // positive but even
                ->and($positiveOddSpec->isSatisfiedBy(-1))->toBeFalse() // odd but negative
                ->and($positiveOddSpec->isSatisfiedBy(0))->toBeFalse(); // even and not positive
        });

        it('works in OR combinations', function () {
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

            $emptyStringSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === '';
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

            // NOT null OR NOT empty string = has value
            $hasValueSpec = new OrSpecification(new NotSpecification($nullSpec), new NotSpecification($emptyStringSpec));

            expect($hasValueSpec->isSatisfiedBy('hello'))->toBeTrue() // not null and not empty
                ->and($hasValueSpec->isSatisfiedBy(0))->toBeTrue() // not null and not empty string
                ->and($hasValueSpec->isSatisfiedBy(false))->toBeTrue() // not null and not empty string
                ->and($hasValueSpec->isSatisfiedBy([]))->toBeTrue() // not null and not empty string
                ->and($hasValueSpec->isSatisfiedBy(null))->toBeTrue() // null but OR with NOT empty string
                ->and($hasValueSpec->isSatisfiedBy(''))->toBeTrue(); // empty string but OR with NOT null

            // Actually, this logic means it's always true because it's NOT null OR NOT empty string
            // Let's test a different combination: NOT null AND NOT empty string
            $hasNonEmptyValueSpec = new AndSpecification(new NotSpecification($nullSpec), new NotSpecification($emptyStringSpec));

            expect($hasNonEmptyValueSpec->isSatisfiedBy('hello'))->toBeTrue() // not null and not empty
                ->and($hasNonEmptyValueSpec->isSatisfiedBy(0))->toBeTrue() // not null and not empty string
                ->and($hasNonEmptyValueSpec->isSatisfiedBy(null))->toBeFalse() // null
                ->and($hasNonEmptyValueSpec->isSatisfiedBy(''))->toBeFalse(); // empty string
        });
    });

    describe('Performance and Evaluation', function () {
        it('evaluates wrapped specification exactly once', function () {
            $evaluationCount = 0;

            $countingSpec = new class($evaluationCount) implements SpecificationInterface
            {
                public function __construct(private int &$count) {}

                public function isSatisfiedBy(mixed $candidate): bool
                {
                    $this->count++;

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

            $notSpec = new NotSpecification($countingSpec);
            $result = $notSpec->isSatisfiedBy('test');

            expect($result)->toBeFalse()
                ->and($evaluationCount)->toBe(1);
        });

        it('handles expensive operations correctly', function () {
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

            $notExpensiveSpec = new NotSpecification($expensiveSpec);

            $start = microtime(true);
            $result = $notExpensiveSpec->isSatisfiedBy('test');
            $duration = microtime(true) - $start;

            expect($result)->toBeTrue() // NOT false = true
                ->and($duration)->toBeGreaterThan(0.0005); // Should take at least 1ms due to expensive operation
        });
    });

    describe('Edge Cases', function () {
        it('handles null values correctly', function () {
            $nullCheckSpec = new class implements SpecificationInterface
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

            $notNullSpec = new NotSpecification($nullCheckSpec);

            expect($notNullSpec->isSatisfiedBy(null))->toBeFalse()
                ->and($notNullSpec->isSatisfiedBy(0))->toBeTrue()
                ->and($notNullSpec->isSatisfiedBy(false))->toBeTrue()
                ->and($notNullSpec->isSatisfiedBy(''))->toBeTrue()
                ->and($notNullSpec->isSatisfiedBy([]))->toBeTrue();
        });

        it('handles boolean false values correctly', function () {
            $falseCheckSpec = new class implements SpecificationInterface
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

            $notFalseSpec = new NotSpecification($falseCheckSpec);

            expect($notFalseSpec->isSatisfiedBy(false))->toBeFalse()
                ->and($notFalseSpec->isSatisfiedBy(true))->toBeTrue()
                ->and($notFalseSpec->isSatisfiedBy(0))->toBeTrue() // 0 is not false
                ->and($notFalseSpec->isSatisfiedBy(''))->toBeTrue() // empty string is not false
                ->and($notFalseSpec->isSatisfiedBy(null))->toBeTrue(); // null is not false
        });

        it('handles type-specific specifications', function () {
            $stringTypeSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_string($candidate);
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

            $notStringSpec = new NotSpecification($stringTypeSpec);

            expect($notStringSpec->isSatisfiedBy('string'))->toBeFalse()
                ->and($notStringSpec->isSatisfiedBy(123))->toBeTrue()
                ->and($notStringSpec->isSatisfiedBy([]))->toBeTrue()
                ->and($notStringSpec->isSatisfiedBy(null))->toBeTrue()
                ->and($notStringSpec->isSatisfiedBy(true))->toBeTrue();
        });
    });

    describe('Nested Complex Scenarios', function () {
        it('handles deeply nested NOT specifications', function () {
            $baseSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return $candidate === 'target';
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

            // Create a chain: NOT(NOT(NOT(NOT(base))))
            $level1 = new NotSpecification($baseSpec); // NOT target
            $level2 = new NotSpecification($level1);   // NOT(NOT target) = target
            $level3 = new NotSpecification($level2);   // NOT(NOT(NOT target)) = NOT target
            $level4 = new NotSpecification($level3);   // NOT(NOT(NOT(NOT target))) = target

            expect($level4->isSatisfiedBy('target'))->toBeTrue()
                ->and($level4->isSatisfiedBy('other'))->toBeFalse();
        });

        it('works with complex business logic specifications', function () {
            $userSpec = new class implements SpecificationInterface
            {
                public function isSatisfiedBy(mixed $candidate): bool
                {
                    return is_array($candidate)
                        && isset($candidate['type'])
                        && $candidate['type'] === 'user'
                        && isset($candidate['active'])
                        && $candidate['active'] === true;
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

            $notActiveUserSpec = new NotSpecification($userSpec);

            $activeUser = ['type' => 'user', 'active' => true];
            $inactiveUser = ['type' => 'user', 'active' => false];
            $admin = ['type' => 'admin', 'active' => true];
            $incomplete = ['type' => 'user'];

            expect($notActiveUserSpec->isSatisfiedBy($activeUser))->toBeFalse() // is active user
                ->and($notActiveUserSpec->isSatisfiedBy($inactiveUser))->toBeTrue() // not active user
                ->and($notActiveUserSpec->isSatisfiedBy($admin))->toBeTrue() // not user type
                ->and($notActiveUserSpec->isSatisfiedBy($incomplete))->toBeTrue() // incomplete user data
                ->and($notActiveUserSpec->isSatisfiedBy('string'))->toBeTrue(); // not even an array
        });
    });
});

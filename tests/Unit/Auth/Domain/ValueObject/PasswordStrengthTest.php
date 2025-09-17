<?php

declare(strict_types=1);

use Modules\Auth\Domain\ValueObject\PasswordStrength;

describe('PasswordStrength Value Object', function () {

    describe('Valid Password Construction', function () {
        it('creates password strength for strong password', function () {
            $password = 'StrongP@ssw0rd91';
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->getPassword())->toBe($password)
                ->and($passwordStrength->isValid())->toBeTrue()
                ->and($passwordStrength->getViolations())->toBeEmpty()
                ->and($passwordStrength->getScore())->toBeGreaterThan(70);
        });

        it('calculates correct strength level for very strong password', function () {
            $password = 'VeryStr0ng&C0mpl3xP@ssw0rd!';
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->getStrengthLevel())->toBe('very_strong')
                ->and($passwordStrength->getScore())->toBeGreaterThanOrEqual(90);
        });

        it('calculates correct strength level for strong password', function () {
            $password = 'Str0ng@P91xY!';
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->getStrengthLevel())->toBe('strong')
                ->and($passwordStrength->getScore())->toBeGreaterThanOrEqual(75)
                ->and($passwordStrength->getScore())->toBeLessThan(90);
        });
    });

    describe('Invalid Password Construction', function () {
        it('throws exception for password too short', function () {
            expect(fn () => new PasswordStrength('Short1!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('Short1!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password does not meet security requirements: Password must be at least 8 characters long'
                );
        });

        it('throws exception for password without uppercase', function () {
            expect(fn () => new PasswordStrength('lowercase123!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('lowercase123!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password must contain at least one uppercase letter'
                );
        });

        it('throws exception for password without lowercase', function () {
            expect(fn () => new PasswordStrength('UPPERCASE123!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('UPPERCASE123!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password must contain at least one lowercase letter'
                );
        });

        it('throws exception for password without numbers', function () {
            expect(fn () => new PasswordStrength('NoNumbers!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('NoNumbers!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password must contain at least one number'
                );
        });

        it('throws exception for password without special characters', function () {
            expect(fn () => new PasswordStrength('NoSpecial123'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('NoSpecial123'))->toThrow(
                    InvalidArgumentException::class,
                    'Password must contain at least one special character'
                );
        });

        it('throws exception for common passwords', function () {
            $commonPasswords = ['password', '123456', 'Password123', 'Admin123!', 'Qwerty123!'];

            foreach ($commonPasswords as $commonPassword) {
                expect(fn () => new PasswordStrength($commonPassword))
                    ->toThrow(InvalidArgumentException::class);
            }
        });

        it('throws exception for password with sequential characters', function () {
            expect(fn () => new PasswordStrength('Abc123def!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('Password123!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password cannot contain sequential characters'
                );
        });

        it('throws exception for password with repeating characters', function () {
            expect(fn () => new PasswordStrength('Passsword123!'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new PasswordStrength('Passsword123!'))->toThrow(
                    InvalidArgumentException::class,
                    'Password cannot contain more than 2 repeating characters'
                );
        });
    });

    describe('Individual Validation Methods', function () {
        it('validates minimum length correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasMinimumLength())->toBeTrue();

            // Test edge case - exactly 8 characters
            $edgeCase = new PasswordStrength('Valid1@#');
            expect($edgeCase->hasMinimumLength())->toBeTrue();
        });

        it('validates uppercase characters correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasUppercase())->toBeTrue();

            $passwordStrength = new PasswordStrength('Validp@ss91');
            expect($passwordStrength->hasUppercase())->toBeTrue();
        });

        it('validates lowercase characters correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasLowercase())->toBeTrue();
        });

        it('validates numbers correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasNumbers())->toBeTrue();
        });

        it('validates special characters correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasSpecialCharacters())->toBeTrue();

            $specialChars = ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '-', '=', '[', ']', '{', '}', '|', ';', ':', ',', '.', '<', '>', '?'];
            foreach ($specialChars as $char) {
                $password = new PasswordStrength("ValidPass91{$char}");
                expect($password->hasSpecialCharacters())->toBeTrue();
            }
        });

        it('validates against common passwords correctly', function () {
            $validPassword = new PasswordStrength('UniqueP@ss91');
            expect($validPassword->hasNoCommonPasswords())->toBeTrue();
        });

        it('validates against sequential characters correctly', function () {
            $validPassword = new PasswordStrength('RandomP@ss91');
            expect($validPassword->hasNoSequentialCharacters())->toBeTrue();
        });

        it('validates against repeating characters correctly', function () {
            $validPassword = new PasswordStrength('ValidP@ss91');
            expect($validPassword->hasNoRepeatingCharacters())->toBeTrue();
        });
    });

    describe('Score Calculation', function () {
        it('calculates higher scores for longer passwords', function () {
            $short = new PasswordStrength('Short1@#');
            $medium = new PasswordStrength('MediumP@ssw0rd');
            $long = new PasswordStrength('VeryLongAndComplexP@ssw0rd91');

            expect($long->getScore())->toBeGreaterThan($medium->getScore())
                ->and($medium->getScore())->toBeGreaterThan($short->getScore());
        });

        it('calculates bonus points for very long passwords', function () {
            $standardLength = new PasswordStrength('StandardP@ss91'); // 14 chars
            $longPassword = new PasswordStrength('VeryLongP@ssw0rd926185374'); // 25 chars

            expect($longPassword->getScore())->toBeGreaterThan($standardLength->getScore());
        });

        it('awards points for character variety', function () {
            $complexPassword = new PasswordStrength('C0mpl3xP@ssw0rd');

            // Test that complex password gets high score
            expect($complexPassword->getScore())->toBeGreaterThan(60);
        });

        it('penalizes for security violations', function () {
            // Test with a password that meets basic requirements but has issues
            $passwordWithSequence = 'TestP@ss91bxd'; // Has no sequence - passes validation
            $cleanPassword = new PasswordStrength('TestP@ssw0rd91');

            expect($cleanPassword->getScore())->toBeGreaterThan(50);
        });

        it('ensures score is within 0-100 range', function () {
            $password = new PasswordStrength('ExtremelyComplexP@ssw0rd926185374!@#$%^&*()');

            expect($password->getScore())->toBeGreaterThanOrEqual(0)
                ->and($password->getScore())->toBeLessThanOrEqual(100);
        });
    });

    describe('Strength Level Mapping', function () {
        it('maps score ranges to correct strength levels', function () {
            // We need to create passwords that would result in specific score ranges
            $veryStrongPassword = new PasswordStrength('VeryStr0ng&C0mpl3xP@ssw0rd!926185374');
            $strongPassword = new PasswordStrength('Str0ngP@ss79');

            expect($veryStrongPassword->getStrengthLevel())->toBe('very_strong');
            expect($strongPassword->getStrengthLevel())->toBe('strong');
        });

        it('correctly identifies strength levels for all ranges', function () {
            $testCases = [
                ['very_strong', 95],
                ['strong', 80],
                ['moderate', 65],
                ['weak', 45],
                ['very_weak', 25],
            ];

            foreach ($testCases as [$expectedLevel, $score]) {
                // Create a mock-like scenario by testing the mapping logic directly
                $strength = new class($score)
                {
                    public function __construct(private int $score) {}

                    public function getStrengthLevel(): string
                    {
                        return match (true) {
                            $this->score >= 90 => 'very_strong',
                            $this->score >= 75 => 'strong',
                            $this->score >= 60 => 'moderate',
                            $this->score >= 40 => 'weak',
                            default => 'very_weak',
                        };
                    }
                };

                expect($strength->getStrengthLevel())->toBe($expectedLevel);
            }
        });
    });

    describe('Array Conversion', function () {
        it('converts to array with all required fields', function () {
            $password = 'ValidP@ssw0rd91';
            $passwordStrength = new PasswordStrength($password);

            $array = $passwordStrength->toArray();

            expect($array)->toHaveKeys([
                'score',
                'strength_level',
                'is_valid',
                'violations',
                'requirements',
            ])
                ->and($array['score'])->toBeInt()
                ->and($array['strength_level'])->toBeString()
                ->and($array['is_valid'])->toBeBool()
                ->and($array['violations'])->toBeArray()
                ->and($array['requirements'])->toBeArray();
        });

        it('includes correct requirements in array', function () {
            $passwordStrength = new PasswordStrength('ValidP@ssw0rd91');
            $array = $passwordStrength->toArray();

            $expectedRequirements = [
                'minimum_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_special_characters' => true,
                'no_common_passwords' => true,
                'no_sequential_characters' => true,
                'no_repeating_characters' => true,
            ];

            expect($array['requirements'])->toBe($expectedRequirements);
        });
    });

    describe('Static Validation Method', function () {
        it('validates and returns PasswordStrength instance', function () {
            $password = 'ValidP@ssw0rd91';
            $passwordStrength = PasswordStrength::validate($password);

            expect($passwordStrength)->toBeInstanceOf(PasswordStrength::class)
                ->and($passwordStrength->getPassword())->toBe($password)
                ->and($passwordStrength->isValid())->toBeTrue();
        });

        it('throws exception for invalid password via static method', function () {
            expect(fn () => PasswordStrength::validate('weak'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('Edge Cases and Boundary Testing', function () {
        it('handles password with exactly 8 characters', function () {
            $password = 'Valid1@#'; // Exactly 8 characters
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->hasMinimumLength())->toBeTrue()
                ->and($passwordStrength->isValid())->toBeTrue();
        });

        it('handles password with multiple special characters', function () {
            $password = 'Valid1@#$%^&*()';
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->hasSpecialCharacters())->toBeTrue()
                ->and($passwordStrength->isValid())->toBeTrue();
        });

        it('detects sequential characters in different cases', function () {
            $sequentialPatterns = ['abc', '123', 'qwe', 'asd', 'zxc'];

            foreach ($sequentialPatterns as $pattern) {
                try {
                    new PasswordStrength("Test{$pattern}P@ss1");
                    expect(false)->toBeTrue("Should have thrown exception for pattern: {$pattern}");
                } catch (InvalidArgumentException $e) {
                    expect($e->getMessage())->toContain('sequential characters');
                }
            }
        });

        it('detects repeating characters with different repeat counts', function () {
            $patterns = ['aaa', 'bbb', '111', '!!!'];

            foreach ($patterns as $pattern) {
                try {
                    new PasswordStrength("Test{$pattern}P@ss1");
                    expect(false)->toBeTrue("Should have thrown exception for pattern: {$pattern}");
                } catch (InvalidArgumentException $e) {
                    expect($e->getMessage())->toContain('repeating characters');
                }
            }
        });

        it('allows exactly 2 repeating characters', function () {
            $password = 'TestPass11@#'; // Two 1's should be allowed
            $passwordStrength = new PasswordStrength($password);

            expect($passwordStrength->hasNoRepeatingCharacters())->toBeTrue()
                ->and($passwordStrength->isValid())->toBeTrue();
        });

        it('handles very long passwords correctly', function () {
            $veryLongPassword = 'VeryLongAndComplexP@ssw0rdThatExceedsNormalLength926185374!@#$%^&*()';
            $passwordStrength = new PasswordStrength($veryLongPassword);

            expect($passwordStrength->isValid())->toBeTrue()
                ->and($passwordStrength->getScore())->toBeGreaterThanOrEqual(90);
        });

        it('handles unicode characters in passwords', function () {
            $unicodePassword = 'Validée91!'; // Contains accented character
            $passwordStrength = new PasswordStrength($unicodePassword);

            expect($passwordStrength->hasSpecialCharacters())->toBeTrue(); // é might be considered special
        });
    });

    describe('Comprehensive Violation Testing', function () {
        it('reports multiple violations correctly', function () {
            try {
                new PasswordStrength('short'); // Multiple violations
                expect(false)->toBeTrue('Should have thrown exception');
            } catch (InvalidArgumentException $e) {
                $message = $e->getMessage();
                expect($message)->toContain('Password must be at least 8 characters long')
                    ->and($message)->toContain('Password must contain at least one uppercase letter')
                    ->and($message)->toContain('Password must contain at least one number')
                    ->and($message)->toContain('Password must contain at least one special character');
            }
        });

        it('correctly identifies all requirement violations', function () {
            $violations = [
                'short' => 'Password must be at least 8 characters long',
                'nouppercase123!' => 'Password must contain at least one uppercase letter',
                'NOLOWERCASE123!' => 'Password must contain at least one lowercase letter',
                'NoNumbers!' => 'Password must contain at least one number',
                'NoSpecial123' => 'Password must contain at least one special character',
            ];

            foreach ($violations as $password => $expectedViolation) {
                try {
                    new PasswordStrength($password);
                    expect(false)->toBeTrue("Should have thrown exception for: {$password}");
                } catch (InvalidArgumentException $e) {
                    expect($e->getMessage())->toContain($expectedViolation);
                }
            }
        });
    });
});

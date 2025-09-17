<?php

declare(strict_types=1);

use Modules\Shared\Domain\Validation\StrongPasswordRule;

describe('StrongPasswordRule', function () {
    describe('Default Configuration', function () {
        it('validates strong password with all requirements', function () {
            $rule = new StrongPasswordRule;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStr0ng!', $fail);

            expect($error ?? null)->toBeNull();
        });

        it('fails for non-string values', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 123, $fail);

            expect($error)->toBe('The :attribute must be a string.');
        });

        it('fails for null values', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', null, $fail);

            expect($error)->toBe('The :attribute must be a string.');
        });

        it('fails for array values', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', ['password'], $fail);

            expect($error)->toBe('The :attribute must be a string.');
        });
    });

    describe('Length Requirements', function () {
        it('fails for passwords shorter than minimum length', function () {
            $rule = new StrongPasswordRule(minLength: 8);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'Short1!', $fail);

            expect($error)->toBe('The :attribute must be at least 8 characters long.');
        });

        it('passes for passwords exactly at minimum length', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'password', $fail);

            expect($error)->toBeNull();
        });

        it('accepts custom minimum length', function () {
            $rule = new StrongPasswordRule(minLength: 12);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'Short1!Pass', $fail); // 11 chars

            expect($error)->toBe('The :attribute must be at least 12 characters long.');
        });

        it('passes for very long passwords', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $longPassword = str_repeat('MyStr0ng!', 10); // 90 characters
            $rule->validate('password', $longPassword, $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Uppercase Letter Requirements', function () {
        it('fails when no uppercase letters present', function () {
            $rule = new StrongPasswordRule(requireUppercase: true);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'mystr0ng!', $fail);

            expect($error)->toBe('The :attribute must contain at least one uppercase letter.');
        });

        it('passes when uppercase letters present', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: true,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyPassword', $fail);

            expect($error)->toBeNull();
        });

        it('skips uppercase check when disabled', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'mylowercase', $fail);

            expect($error)->toBeNull();
        });

        it('detects uppercase in middle of password', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: true,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'passWord', $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Lowercase Letter Requirements', function () {
        it('fails when no lowercase letters present', function () {
            $rule = new StrongPasswordRule(requireLowercase: true);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MYSTR0NG!', $fail);

            expect($error)->toBe('The :attribute must contain at least one lowercase letter.');
        });

        it('passes when lowercase letters present', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: true,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'myPassword', $fail);

            expect($error)->toBeNull();
        });

        it('skips lowercase check when disabled', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'ALLUPPERCASE', $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Number Requirements', function () {
        it('fails when no numbers present', function () {
            $rule = new StrongPasswordRule(requireNumbers: true);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStrong!', $fail);

            expect($error)->toBe('The :attribute must contain at least one number.');
        });

        it('passes when numbers present', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: true,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'Password123', $fail);

            expect($error)->toBeNull();
        });

        it('detects single digit', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: true,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'password7', $fail);

            expect($error)->toBeNull();
        });

        it('detects multiple digits', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: true,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'pass123word', $fail);

            expect($error)->toBeNull();
        });

        it('skips number check when disabled', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'NoNumbers', $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Special Character Requirements', function () {
        it('fails when no special characters present', function () {
            $rule = new StrongPasswordRule(requireSpecialChars: true);
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStr0ng', $fail);

            expect($error)->toBe('The :attribute must contain at least one special character.');
        });

        it('passes when special characters present', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'password!', $fail);

            expect($error)->toBeNull();
        });

        it('accepts various special characters', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: true
            );

            $specialChars = ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '+', '=', '{', '}', '[', ']', '|', '\\', ':', ';', '"', "'", '<', '>', ',', '.', '?', '/', '~', '`'];

            foreach ($specialChars as $char) {
                $error = null;
                $fail = function (string $message) use (&$error) {
                    $error = $message;
                };

                $rule->validate('password', 'password' . $char, $fail);
                expect($error)->toBeNull();
            }
        });

        it('skips special character check when disabled', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'NoSpecialChars', $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Combined Requirements', function () {
        it('validates password with all requirements met', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStr0ng!Pass', $fail);

            expect($error)->toBeNull();
        });

        it('fails when length requirement not met first', function () {
            $rule = new StrongPasswordRule(
                minLength: 10,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStr0ng!', $fail); // 9 chars but has all other requirements

            expect($error)->toBe('The :attribute must be at least 10 characters long.');
        });

        it('fails when uppercase requirement not met', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'mystr0ng!pass', $fail); // No uppercase

            expect($error)->toBe('The :attribute must contain at least one uppercase letter.');
        });

        it('fails when lowercase requirement not met', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MYSTR0NG!PASS', $fail); // No lowercase

            expect($error)->toBe('The :attribute must contain at least one lowercase letter.');
        });

        it('fails when number requirement not met', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStrong!Pass', $fail); // No numbers

            expect($error)->toBe('The :attribute must contain at least one number.');
        });

        it('fails when special character requirement not met', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'MyStr0ngPass', $fail); // No special chars

            expect($error)->toBe('The :attribute must contain at least one special character.');
        });
    });

    describe('Edge Cases', function () {
        it('handles empty string', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', '', $fail);

            expect($error)->toBe('The :attribute must be at least 8 characters long.');
        });

        it('handles whitespace only', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', '        ', $fail); // 8 spaces

            expect($error)->toBe('The :attribute must contain at least one uppercase letter.');
        });

        it('handles unicode characters', function () {
            $rule = new StrongPasswordRule(
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'pässwörd', $fail); // 8 chars with unicode

            expect($error)->toBeNull();
        });

        it('handles extremely long passwords', function () {
            $rule = new StrongPasswordRule;
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $veryLongPassword = str_repeat('MyStr0ng!', 1000); // 9000 characters
            $rule->validate('password', $veryLongPassword, $fail);

            expect($error)->toBeNull();
        });
    });

    describe('Minimal Configuration', function () {
        it('allows very permissive configuration', function () {
            $rule = new StrongPasswordRule(
                minLength: 1,
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', 'a', $fail);

            expect($error)->toBeNull();
        });

        it('allows numbers only when other requirements disabled', function () {
            $rule = new StrongPasswordRule(
                minLength: 8,
                requireUppercase: false,
                requireLowercase: false,
                requireNumbers: false,
                requireSpecialChars: false
            );
            $error = null;
            $fail = function (string $message) use (&$error) {
                $error = $message;
            };

            $rule->validate('password', '12345678', $fail);

            expect($error)->toBeNull();
        });
    });
});

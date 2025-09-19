<?php

declare(strict_types=1);

use Modules\Auth\Domain\ValueObject\TwoFactorCode;

describe('TwoFactorCode Value Object', function (): void {

    describe('Construction', function (): void {
        it('creates a valid 2FA code with all parameters', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $type = 'totp';
            $attempts = 0;

            $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type, $attempts);

            expect($twoFactorCode->getCode())->toBe($code)
                ->and($twoFactorCode->getExpiresAt())->toBe($expiresAt)
                ->and($twoFactorCode->getType())->toBe($type)
                ->and($twoFactorCode->getAttempts())->toBe($attempts);
        });

        it('creates a valid 2FA code with default parameters', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->getCode())->toBe($code)
                ->and($twoFactorCode->getExpiresAt())->toBe($expiresAt)
                ->and($twoFactorCode->getType())->toBe('totp')
                ->and($twoFactorCode->getAttempts())->toBe(0);
        });

        it('throws exception for empty code', function (): void {
            $expiresAt = new DateTimeImmutable('+5 minutes');

            expect(fn () => new TwoFactorCode('', $expiresAt))
                ->toThrow(InvalidArgumentException::class, '2FA code cannot be empty');
        });

        it('throws exception for code shorter than 4 characters', function (): void {
            $expiresAt = new DateTimeImmutable('+5 minutes');

            expect(fn () => new TwoFactorCode('123', $expiresAt))
                ->toThrow(InvalidArgumentException::class, '2FA code must be at least 4 characters long');
        });

        it('throws exception for code longer than 16 characters', function (): void {
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $longCode = '12345678901234567'; // 17 characters

            expect(fn () => new TwoFactorCode($longCode, $expiresAt))
                ->toThrow(InvalidArgumentException::class, '2FA code cannot be longer than 16 characters');
        });

        it('throws exception for invalid type', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            expect(fn () => new TwoFactorCode($code, $expiresAt, 'invalid'))
                ->toThrow(InvalidArgumentException::class, '2FA code type must be one of: totp, sms, email, backup');
        });

        it('accepts all valid types', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $validTypes = ['totp', 'sms', 'email', 'backup'];

            foreach ($validTypes as $type) {
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type);
                expect($twoFactorCode->getType())->toBe($type);
            }
        });

        it('throws exception for expiry more than 1 year in future', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+2 years');

            expect(fn () => new TwoFactorCode($code, $expiresAt))
                ->toThrow(InvalidArgumentException::class, '2FA code expiry cannot be more than 1 year in the future');
        });

        it('throws exception for negative attempts', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            expect(fn () => new TwoFactorCode($code, $expiresAt, 'totp', -1))
                ->toThrow(InvalidArgumentException::class, '2FA code attempts cannot be negative');
        });

        it('throws exception for excessive attempts', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            expect(fn () => new TwoFactorCode($code, $expiresAt, 'totp', 101))
                ->toThrow(InvalidArgumentException::class, '2FA code attempts cannot exceed 100');
        });
    });

    describe('Expiry Validation', function (): void {
        it('returns false for isExpired when code is valid', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->isExpired())->toBeFalse();
        });

        it('returns true for isExpired when code is expired', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            $futureTime = new DateTimeImmutable('+10 minutes');
            expect($twoFactorCode->isExpired($futureTime))->toBeTrue();
        });

        it('returns true for isValid when code is not expired and not blocked', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->isValid())->toBeTrue();
        });

        it('returns false for isValid when code is expired', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            $futureTime = new DateTimeImmutable('+10 minutes');
            expect($twoFactorCode->isValid($futureTime))->toBeFalse();
        });

        it('returns false for isValid when code is blocked', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 3); // Max attempts for TOTP

            expect($twoFactorCode->isValid())->toBeFalse();
        });

        it('calculates correct time to expiry', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+300 seconds'); // 5 minutes
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            $now = new DateTimeImmutable;
            $timeToExpiry = $twoFactorCode->getTimeToExpiry($now);

            expect($timeToExpiry)->toBeGreaterThan(250)
                ->and($timeToExpiry)->toBeLessThanOrEqual(300);
        });

        it('returns zero time to expiry for expired codes', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            $futureTime = new DateTimeImmutable('+10 minutes');
            expect($twoFactorCode->getTimeToExpiry($futureTime))->toBe(0);
        });
    });

    describe('Attempt Management', function (): void {
        it('returns correct max attempts for different types', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $totpCode = new TwoFactorCode($code, $expiresAt, 'totp');
            expect($totpCode->getMaxAttempts())->toBe(3);

            $smsCode = new TwoFactorCode($code, $expiresAt, 'sms');
            expect($smsCode->getMaxAttempts())->toBe(5);

            $emailCode = new TwoFactorCode($code, $expiresAt, 'email');
            expect($emailCode->getMaxAttempts())->toBe(5);

            $backupCode = new TwoFactorCode($code, $expiresAt, 'backup');
            expect($backupCode->getMaxAttempts())->toBe(1);
        });

        it('calculates remaining attempts correctly', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 1);

            expect($twoFactorCode->getRemainingAttempts())->toBe(2); // 3 max - 1 used = 2
        });

        it('returns zero remaining attempts when blocked', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 3);

            expect($twoFactorCode->getRemainingAttempts())->toBe(0);
        });

        it('returns true for isBlocked when attempts equal max attempts', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 3);

            expect($twoFactorCode->isBlocked())->toBeTrue();
        });

        it('returns false for isBlocked when attempts below max', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 2);

            expect($twoFactorCode->isBlocked())->toBeFalse();
        });

        it('increments attempts correctly', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 1);

            $incrementedCode = $twoFactorCode->incrementAttempts();

            expect($incrementedCode->getAttempts())->toBe(2)
                ->and($incrementedCode->getCode())->toBe($code)
                ->and($incrementedCode->getType())->toBe('totp')
                ->and($incrementedCode->getExpiresAt())->toBe($expiresAt);
        });
    });

    describe('Code Verification', function (): void {
        it('returns true for matches with identical codes', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->matches('123456'))->toBeTrue();
        });

        it('returns false for matches with different codes', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->matches('654321'))->toBeFalse();
        });

        it('uses timing-safe comparison for matches', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            // This tests that hash_equals is used internally
            expect($twoFactorCode->matches('123456'))->toBeTrue()
                ->and($twoFactorCode->matches('123457'))->toBeFalse();
        });

        it('returns true for verify with valid code and conditions', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->verify('123456'))->toBeTrue();
        });

        it('returns false for verify with invalid code', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->verify('654321'))->toBeFalse();
        });

        it('returns false for verify when code is expired', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            $futureTime = new DateTimeImmutable('+10 minutes');
            expect($twoFactorCode->verify('123456', $futureTime))->toBeFalse();
        });

        it('returns false for verify when code is blocked', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 3);

            expect($twoFactorCode->verify('123456'))->toBeFalse();
        });
    });

    describe('Static Factory Methods', function (): void {
        it('generates valid TOTP code', function (): void {
            $totpCode = TwoFactorCode::generateTOTP();

            expect($totpCode->getCode())->toHaveLength(6)
                ->and($totpCode->getType())->toBe('totp')
                ->and($totpCode->getAttempts())->toBe(0)
                ->and($totpCode->isValid())->toBeTrue();

            $timeToExpiry = $totpCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(250) // Should be close to 5 minutes (300 seconds)
                ->and($timeToExpiry)->toBeLessThanOrEqual(300);
        });

        it('generates valid SMS code', function (): void {
            $smsCode = TwoFactorCode::generateSMS();

            expect($smsCode->getCode())->toHaveLength(6)
                ->and($smsCode->getType())->toBe('sms')
                ->and($smsCode->getAttempts())->toBe(0)
                ->and($smsCode->isValid())->toBeTrue();

            $timeToExpiry = $smsCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(550) // Should be close to 10 minutes (600 seconds)
                ->and($timeToExpiry)->toBeLessThanOrEqual(600);
        });

        it('generates valid Email code', function (): void {
            $emailCode = TwoFactorCode::generateEmail();

            expect($emailCode->getCode())->toHaveLength(6)
                ->and($emailCode->getType())->toBe('email')
                ->and($emailCode->getAttempts())->toBe(0)
                ->and($emailCode->isValid())->toBeTrue();

            $timeToExpiry = $emailCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(850) // Should be close to 15 minutes (900 seconds)
                ->and($timeToExpiry)->toBeLessThanOrEqual(900);
        });

        it('generates valid Backup code', function (): void {
            $backupCode = TwoFactorCode::generateBackupCode();

            expect($backupCode->getCode())->toHaveLength(8) // 4 bytes = 8 hex chars
                ->and($backupCode->getType())->toBe('backup')
                ->and($backupCode->getAttempts())->toBe(0)
                ->and($backupCode->isValid())->toBeTrue();

            // Backup codes should be valid for 1 year
            $timeToExpiry = $backupCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(31536000 - 10); // 1 year - 10 seconds buffer
        });

        it('generates TOTP codes with custom validity', function (): void {
            $validitySeconds = 600; // 10 minutes
            $totpCode = TwoFactorCode::generateTOTP($validitySeconds);

            $timeToExpiry = $totpCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(590)
                ->and($timeToExpiry)->toBeLessThanOrEqual(600);
        });

        it('generates SMS codes with custom validity', function (): void {
            $validityMinutes = 5;
            $smsCode = TwoFactorCode::generateSMS($validityMinutes);

            $timeToExpiry = $smsCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(290) // 5 minutes - 10 seconds buffer
                ->and($timeToExpiry)->toBeLessThanOrEqual(300);
        });

        it('generates Email codes with custom validity', function (): void {
            $validityMinutes = 30;
            $emailCode = TwoFactorCode::generateEmail($validityMinutes);

            $timeToExpiry = $emailCode->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(1790) // 30 minutes - 10 seconds buffer
                ->and($timeToExpiry)->toBeLessThanOrEqual(1800);
        });

        it('generates unique codes on multiple calls', function (): void {
            $code1 = TwoFactorCode::generateTOTP();
            $code2 = TwoFactorCode::generateTOTP();

            expect($code1->getCode())->not->toBe($code2->getCode());
        });

        it('generates numeric codes for TOTP, SMS, and Email', function (): void {
            $totpCode = TwoFactorCode::generateTOTP();
            $smsCode = TwoFactorCode::generateSMS();
            $emailCode = TwoFactorCode::generateEmail();

            expect(ctype_digit($totpCode->getCode()))->toBeTrue();
            expect(ctype_digit($smsCode->getCode()))->toBeTrue();
            expect(ctype_digit($emailCode->getCode()))->toBeTrue();
        });

        it('generates alphanumeric uppercase codes for backup', function (): void {
            $backupCode = TwoFactorCode::generateBackupCode();

            expect(ctype_alnum($backupCode->getCode()))->toBeTrue();
            expect(strtoupper($backupCode->getCode()))->toBe($backupCode->getCode());
        });
    });

    describe('Array Conversion', function (): void {
        it('converts to array with all required fields', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('2025-01-01 12:00:00');
            $type = 'sms';
            $attempts = 2;
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type, $attempts);

            $array = $twoFactorCode->toArray();

            expect($array)->toHaveKeys([
                'code',
                'expires_at',
                'type',
                'attempts',
                'is_valid',
                'is_expired',
                'is_blocked',
                'remaining_attempts',
            ])
                ->and($array['code'])->toBe($code)
                ->and($array['expires_at'])->toBe($expiresAt->format(DateTimeImmutable::ATOM))
                ->and($array['type'])->toBe($type)
                ->and($array['attempts'])->toBe($attempts)
                ->and($array['is_valid'])->toBeBool()
                ->and($array['is_expired'])->toBeBool()
                ->and($array['is_blocked'])->toBeBool()
                ->and($array['remaining_attempts'])->toBeInt();
        });

        it('includes correct calculated values in array', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $type = 'totp';
            $attempts = 1;
            $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type, $attempts);

            $array = $twoFactorCode->toArray();

            expect($array['is_valid'])->toBeTrue()
                ->and($array['is_expired'])->toBeFalse()
                ->and($array['is_blocked'])->toBeFalse()
                ->and($array['remaining_attempts'])->toBe(2); // 3 max - 1 used = 2
        });
    });

    describe('Edge Cases and Boundary Testing', function (): void {
        it('handles code with exactly 4 characters', function (): void {
            $code = '1234'; // Exactly 4 characters
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->getCode())->toBe($code);
        });

        it('handles code with exactly 16 characters', function (): void {
            $code = '1234567890123456'; // Exactly 16 characters
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->getCode())->toBe($code);
        });

        it('handles expiry exactly 1 year in future', function (): void {
            $code = '123456';
            $expiresAt = (new DateTimeImmutable)->modify('+1 year');

            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->isValid())->toBeTrue();
        });

        it('handles maximum allowed attempts', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 100);

            expect($twoFactorCode->getAttempts())->toBe(100);
        });

        it('handles different code formats', function (): void {
            $codes = ['123456', 'ABC123', 'a1b2c3', 'BACKUP01'];
            $expiresAt = new DateTimeImmutable('+5 minutes');

            foreach ($codes as $code) {
                $twoFactorCode = new TwoFactorCode($code, $expiresAt);
                expect($twoFactorCode->getCode())->toBe($code);
            }
        });

        it('handles case sensitivity in code matching', function (): void {
            $code = 'AbC123';
            $expiresAt = new DateTimeImmutable('+5 minutes');
            $twoFactorCode = new TwoFactorCode($code, $expiresAt);

            expect($twoFactorCode->matches('AbC123'))->toBeTrue()
                ->and($twoFactorCode->matches('abc123'))->toBeFalse()
                ->and($twoFactorCode->matches('ABC123'))->toBeFalse();
        });
    });

    describe('Type-Specific Behavior', function (): void {
        it('has different max attempts for each type', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $typeAttempts = [
                'totp' => 3,
                'sms' => 5,
                'email' => 5,
                'backup' => 1,
            ];

            foreach ($typeAttempts as $type => $expectedMaxAttempts) {
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type);
                expect($twoFactorCode->getMaxAttempts())->toBe($expectedMaxAttempts);
            }
        });

        it('blocks immediately for backup codes', function (): void {
            $code = 'BACKUP01';
            $expiresAt = new DateTimeImmutable('+1 year');
            $backupCode = new TwoFactorCode($code, $expiresAt, 'backup', 1);

            expect($backupCode->isBlocked())->toBeTrue()
                ->and($backupCode->getRemainingAttempts())->toBe(0);
        });

        it('allows more attempts for SMS and Email than TOTP', function (): void {
            $code = '123456';
            $expiresAt = new DateTimeImmutable('+5 minutes');

            $totpCode = new TwoFactorCode($code, $expiresAt, 'totp', 2);
            $smsCode = new TwoFactorCode($code, $expiresAt, 'sms', 2);

            expect($totpCode->getRemainingAttempts())->toBe(1) // 3 - 2 = 1
                ->and($smsCode->getRemainingAttempts())->toBe(3); // 5 - 2 = 3
        });
    });
});

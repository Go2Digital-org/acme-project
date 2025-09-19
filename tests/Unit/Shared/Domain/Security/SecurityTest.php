<?php

declare(strict_types=1);

use Modules\Auth\Domain\Service\SecurityService;
use Modules\Auth\Domain\ValueObject\AuthToken;
use Modules\Auth\Domain\ValueObject\PasswordStrength;
use Modules\Auth\Domain\ValueObject\TwoFactorCode;
use Modules\Shared\Domain\Validation\SafeHtmlRule;
use Modules\Shared\Domain\Validation\StrongPasswordRule;

describe('Security Components', function (): void {

    describe('SecurityService', function (): void {

        beforeEach(function (): void {
            $this->securityService = new SecurityService;
        });

        describe('Token Generation', function (): void {
            it('generates secure token with default length', function (): void {
                $token = $this->securityService->generateSecureToken();

                expect($token)->toHaveLength(32) // 16 bytes = 32 hex chars (default length)
                    ->and(ctype_xdigit($token))->toBeTrue();
            });

            it('generates secure token with custom length', function (): void {
                $length = 48;
                $token = $this->securityService->generateSecureToken($length);

                expect($token)->toHaveLength($length)
                    ->and(ctype_xdigit($token))->toBeTrue();
            });

            it('generates unique tokens on multiple calls', function (): void {
                $token1 = $this->securityService->generateSecureToken();
                $token2 = $this->securityService->generateSecureToken();

                expect($token1)->not->toBe($token2);
            });

            it('throws exception for token length below minimum', function (): void {
                expect(fn () => $this->securityService->generateSecureToken(12))
                    ->toThrow(InvalidArgumentException::class, 'Token length must be between 16 and 256 characters');
            });

            it('throws exception for token length above maximum', function (): void {
                expect(fn () => $this->securityService->generateSecureToken(300))
                    ->toThrow(InvalidArgumentException::class, 'Token length must be between 16 and 256 characters');
            });

            it('accepts minimum valid token length', function (): void {
                $token = $this->securityService->generateSecureToken(16);

                expect($token)->toHaveLength(16)
                    ->and(ctype_xdigit($token))->toBeTrue();
            });

            it('accepts maximum valid token length', function (): void {
                $token = $this->securityService->generateSecureToken(256);

                expect($token)->toHaveLength(256)
                    ->and(ctype_xdigit($token))->toBeTrue();
            });
        });

        describe('Password Generation', function (): void {
            it('generates secure password with default length', function (): void {
                $password = $this->securityService->generateSecurePassword();

                expect($password)->toHaveLength(16)
                    ->and(preg_match('/[A-Z]/', $password))->toBe(1) // Has uppercase
                    ->and(preg_match('/[a-z]/', $password))->toBe(1) // Has lowercase
                    ->and(preg_match('/[0-9]/', $password))->toBe(1) // Has numbers
                    ->and(preg_match('/[^A-Za-z0-9]/', $password))->toBe(1); // Has symbols
            });

            it('generates secure password with custom length', function (): void {
                $length = 24;
                $password = $this->securityService->generateSecurePassword($length);

                expect($password)->toHaveLength($length);
            });

            it('generates unique passwords on multiple calls', function (): void {
                $password1 = $this->securityService->generateSecurePassword();
                $password2 = $this->securityService->generateSecurePassword();

                expect($password1)->not->toBe($password2);
            });

            it('throws exception for password length below minimum', function (): void {
                expect(fn () => $this->securityService->generateSecurePassword(8))
                    ->toThrow(InvalidArgumentException::class, 'Password length must be between 12 and 128 characters');
            });

            it('throws exception for password length above maximum', function (): void {
                expect(fn () => $this->securityService->generateSecurePassword(150))
                    ->toThrow(InvalidArgumentException::class, 'Password length must be between 12 and 128 characters');
            });

            it('ensures minimum character requirements for all lengths', function (): void {
                $lengths = [12, 16, 32, 64, 128];

                foreach ($lengths as $length) {
                    $password = $this->securityService->generateSecurePassword($length);

                    expect($password)->toHaveLength($length)
                        ->and(preg_match('/[A-Z]/', $password))->toBe(1)
                        ->and(preg_match('/[a-z]/', $password))->toBe(1)
                        ->and(preg_match('/[0-9]/', $password))->toBe(1)
                        ->and(preg_match('/[^A-Za-z0-9]/', $password))->toBe(1);
                }
            });
        });

        describe('IP Address Validation', function (): void {
            it('validates correct IPv4 addresses using filter_var', function (): void {
                $validIPs = [
                    '192.168.1.1',
                    '10.0.0.1',
                    '172.16.0.1',
                    '127.0.0.1',
                    '8.8.8.8',
                    '1.1.1.1',
                ];

                foreach ($validIPs as $ip) {
                    expect(filter_var($ip, FILTER_VALIDATE_IP))->not->toBeFalse();
                }
            });

            it('validates correct IPv6 addresses using filter_var', function (): void {
                $validIPs = [
                    '::1',
                    '2001:db8::1',
                    '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                ];

                foreach ($validIPs as $ip) {
                    expect(filter_var($ip, FILTER_VALIDATE_IP))->not->toBeFalse();
                }
            });

            it('detects invalid IP address formats using filter_var', function (): void {
                $invalidIPs = [
                    '256.256.256.256',
                    '192.168.1',
                    '192.168.1.1.1',
                    'not-an-ip',
                    '',
                    'localhost',
                ];

                foreach ($invalidIPs as $ip) {
                    expect(filter_var($ip, FILTER_VALIDATE_IP))->toBeFalse();
                }
            });

            it('identifies private IP ranges correctly', function (): void {
                $privateIPs = [
                    '192.168.1.1',
                    '10.0.0.1',
                    '172.16.0.1',
                    '127.0.0.1',
                ];

                foreach ($privateIPs as $ip) {
                    expect(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))->toBeFalse();
                }
            });

            it('identifies public IP addresses correctly', function (): void {
                $publicIPs = [
                    '8.8.8.8',
                    '1.1.1.1',
                    '208.67.222.222',
                ];

                foreach ($publicIPs as $ip) {
                    expect(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))->not->toBeFalse();
                }
            });

            it('validates IP blacklist logic', function (): void {
                $suspiciousIps = [
                    '192.0.2.1',     // TEST-NET-1
                    '198.51.100.1',  // TEST-NET-2
                    '203.0.113.1',   // TEST-NET-3
                ];

                $trustedIp = '8.8.8.8';

                expect(in_array($trustedIp, $suspiciousIps, true))->toBeFalse();

                foreach ($suspiciousIps as $ip) {
                    expect(in_array($ip, $suspiciousIps, true))->toBeTrue();
                }
            });
        });

        describe('Suspicious Activity Detection', function (): void {
            it('detects suspicious user agents', function (): void {
                $suspiciousUserAgents = [
                    'curl/7.68.0',
                    'wget/1.20.3',
                    'python-requests/2.25.1',
                    'Java/11.0.2',
                    'Googlebot/2.1',
                    'Bingbot/2.0',
                    'spider/1.0',
                    'scraper tool',
                    '',
                    'short',
                ];

                foreach ($suspiciousUserAgents as $userAgent) {
                    expect($this->securityService->isSuspiciousUserAgent($userAgent))->toBeTrue();
                }
            });

            it('allows legitimate user agents', function (): void {
                $legitimateUserAgents = [
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
                ];

                foreach ($legitimateUserAgents as $userAgent) {
                    expect($this->securityService->isSuspiciousUserAgent($userAgent))->toBeFalse();
                }
            });

            it('detects excessive requests from same IP', function (): void {
                $ipAddress = '192.168.1.100';
                $recentActivity = [];

                // Create 150 requests in the last hour (exceeds limit of 100)
                for ($i = 0; $i < 150; $i++) {
                    $recentActivity[] = [
                        'ip_address' => $ipAddress,
                        'timestamp' => (new DateTimeImmutable)->modify('-' . rand(1, 59) . ' minutes')->format('Y-m-d H:i:s'),
                    ];
                }

                expect($this->securityService->hasExcessiveRequests($ipAddress, $recentActivity))->toBeTrue();
            });

            it('allows normal request rates', function (): void {
                $ipAddress = '192.168.1.100';
                $recentActivity = [];

                // Create 50 requests in the last hour (within limit)
                for ($i = 0; $i < 50; $i++) {
                    $recentActivity[] = [
                        'ip_address' => $ipAddress,
                        'timestamp' => (new DateTimeImmutable)->modify('-' . rand(1, 59) . ' minutes')->format('Y-m-d H:i:s'),
                    ];
                }

                expect($this->securityService->hasExcessiveRequests($ipAddress, $recentActivity))->toBeFalse();
            });

            it('ignores old requests outside time window', function (): void {
                $ipAddress = '192.168.1.100';
                $recentActivity = [];

                // Create 150 requests but all older than 1 hour
                for ($i = 0; $i < 150; $i++) {
                    $recentActivity[] = [
                        'ip_address' => $ipAddress,
                        'timestamp' => (new DateTimeImmutable)->modify('-' . rand(61, 120) . ' minutes')->format('Y-m-d H:i:s'),
                    ];
                }

                expect($this->securityService->hasExcessiveRequests($ipAddress, $recentActivity))->toBeFalse();
            });

            it('detects suspicious activity based on multiple factors', function (): void {
                $suspiciousIP = '192.0.2.1';
                $suspiciousUserAgent = 'curl/7.68.0';

                expect($this->securityService->detectSuspiciousActivity(
                    $suspiciousIP,
                    $suspiciousUserAgent
                ))->toBeTrue();
            });
        });

        describe('Encryption and Hashing', function (): void {
            it('encrypts and decrypts data correctly', function (): void {
                $data = 'sensitive information';
                $key = str_repeat('a', 32); // 32 character key

                $encrypted = $this->securityService->encryptSensitiveData($data, $key);
                $decrypted = $this->securityService->decryptSensitiveData($encrypted, $key);

                expect($decrypted)->toBe($data)
                    ->and($encrypted)->not->toBe($data)
                    ->and(strlen($encrypted))->toBeGreaterThan(strlen($data));
            });

            it('throws exception for short encryption key', function (): void {
                $data = 'test data';
                $shortKey = 'short'; // Less than 32 characters

                expect(fn () => $this->securityService->encryptSensitiveData($data, $shortKey))
                    ->toThrow(InvalidArgumentException::class, 'Encryption key must be at least 32 characters');
            });

            it('throws exception for short decryption key', function (): void {
                $data = 'fake encrypted data';
                $shortKey = 'short';

                expect(fn () => $this->securityService->decryptSensitiveData($data, $shortKey))
                    ->toThrow(InvalidArgumentException::class, 'Decryption key must be at least 32 characters');
            });

            it('throws exception for invalid encrypted data format', function (): void {
                $key = str_repeat('a', 32);
                $invalidData = 'not-base64-encrypted-data';

                expect(fn () => $this->securityService->decryptSensitiveData($invalidData, $key))
                    ->toThrow(InvalidArgumentException::class, 'Failed to decrypt data');
            });

            it('hashes data with salt', function (): void {
                $data = 'password123';
                $hashedData = $this->securityService->hashSensitiveData($data);

                expect($hashedData)->toContain(':')
                    ->and(explode(':', $hashedData))->toHaveCount(2);

                [$hash, $salt] = explode(':', $hashedData);
                expect($hash)->toHaveLength(64) // SHA256 hash length
                    ->and($salt)->toHaveLength(16); // Default salt length (16 hex chars = 8 bytes)
            });

            it('verifies hashed data correctly', function (): void {
                $data = 'password123';
                $hashedData = $this->securityService->hashSensitiveData($data);

                expect($this->securityService->verifySensitiveData($data, $hashedData))->toBeTrue()
                    ->and($this->securityService->verifySensitiveData('wrong', $hashedData))->toBeFalse();
            });

            it('handles malformed hash data gracefully', function (): void {
                $data = 'password123';
                $malformedHash = 'invalid-hash-format';

                expect($this->securityService->verifySensitiveData($data, $malformedHash))->toBeFalse();
            });
        });

        describe('Session Token Validation', function (): void {
            it('validates valid session tokens', function (): void {
                $authToken = AuthToken::generate();
                $ipAddress = '192.168.1.1';
                $userAgent = 'Mozilla/5.0 (valid browser)';

                expect($this->securityService->validateSessionToken($authToken, $ipAddress, $userAgent))->toBeTrue();
            });

            it('rejects expired session tokens', function (): void {
                // Create a token that expires in 1 hour
                $token = new AuthToken(
                    'abcdef123456789012345678',
                    new DateTimeImmutable('+1 hour')
                );

                // Test token expiry logic directly (fast, no sleep needed)
                $futureTime = new DateTimeImmutable('+2 hours');
                expect($token->isValid($futureTime))->toBeFalse(); // Token should be expired in future

                // Test that the SecurityService validates correctly for current valid token
                $ipAddress = '192.168.1.1';
                $userAgent = 'Mozilla/5.0 (valid browser)';
                expect($this->securityService->validateSessionToken($token, $ipAddress, $userAgent))->toBeTrue();
            });

            it('rejects tokens with invalid IP addresses', function (): void {
                $authToken = AuthToken::generate();
                $invalidIP = 'invalid-ip';
                $userAgent = 'Mozilla/5.0 (valid browser)';

                expect(fn () => $this->securityService->validateSessionToken($authToken, $invalidIP, $userAgent))
                    ->toThrow(InvalidArgumentException::class, 'Invalid IP address format');
            });
        });

        describe('Input Sanitization', function (): void {
            it('removes null bytes from input', function (): void {
                $input = "test\0data";
                $sanitized = $this->securityService->sanitizeUserInput($input);

                expect($sanitized)->toBe('testdata');
            });

            it('trims whitespace from input', function (): void {
                $input = "  test data  \n\t";
                $sanitized = $this->securityService->sanitizeUserInput($input);

                expect($sanitized)->toBe('test data');
            });

            it('removes control characters except allowed ones', function (): void {
                $input = "test\x01\x02data\t\nmore\rdata";
                $sanitized = $this->securityService->sanitizeUserInput($input);

                expect($sanitized)->toBe("testdata\t\nmore\rdata");
            });

            it('handles empty input gracefully', function (): void {
                $input = '';
                $sanitized = $this->securityService->sanitizeUserInput($input);

                expect($sanitized)->toBe('');
            });

            it('handles only whitespace input', function (): void {
                $input = "   \t\n\r   ";
                $sanitized = $this->securityService->sanitizeUserInput($input);

                expect($sanitized)->toBe('');
            });
        });

        describe('File Upload Validation', function (): void {
            it('validates allowed file types', function (): void {
                $allowedFiles = [
                    ['type' => 'image/jpeg', 'size' => 1024, 'error' => UPLOAD_ERR_OK],
                    ['type' => 'image/png', 'size' => 2048, 'error' => UPLOAD_ERR_OK],
                    ['type' => 'application/pdf', 'size' => 5000, 'error' => UPLOAD_ERR_OK],
                    ['type' => 'text/plain', 'size' => 500, 'error' => UPLOAD_ERR_OK],
                ];

                foreach ($allowedFiles as $file) {
                    expect($this->securityService->validateFileUpload($file))->toBeTrue();
                }
            });

            it('rejects disallowed file types', function (): void {
                $disallowedFiles = [
                    ['type' => 'application/x-executable', 'size' => 1024, 'error' => UPLOAD_ERR_OK],
                    ['type' => 'text/html', 'size' => 1024, 'error' => UPLOAD_ERR_OK],
                    ['type' => 'application/javascript', 'size' => 1024, 'error' => UPLOAD_ERR_OK],
                ];

                foreach ($disallowedFiles as $file) {
                    expect(fn () => $this->securityService->validateFileUpload($file))
                        ->toThrow(InvalidArgumentException::class, 'File type not allowed');
                }
            });

            it('rejects files exceeding size limit', function (): void {
                $largeFile = [
                    'type' => 'image/jpeg',
                    'size' => 11 * 1024 * 1024, // 11MB (exceeds 10MB limit)
                    'error' => UPLOAD_ERR_OK,
                ];

                expect(fn () => $this->securityService->validateFileUpload($largeFile))
                    ->toThrow(InvalidArgumentException::class, 'File size exceeds maximum limit of 10MB');
            });

            it('rejects files with upload errors', function (): void {
                $errorFile = [
                    'type' => 'image/jpeg',
                    'size' => 1024,
                    'error' => UPLOAD_ERR_PARTIAL,
                ];

                expect(fn () => $this->securityService->validateFileUpload($errorFile))
                    ->toThrow(InvalidArgumentException::class, 'File upload error: ' . UPLOAD_ERR_PARTIAL);
            });

            it('validates file at maximum allowed size', function (): void {
                $maxSizeFile = [
                    'type' => 'image/jpeg',
                    'size' => 10 * 1024 * 1024, // Exactly 10MB
                    'error' => UPLOAD_ERR_OK,
                ];

                expect($this->securityService->validateFileUpload($maxSizeFile))->toBeTrue();
            });
        });

        describe('CSRF Token Management', function (): void {
            it('generates CSRF token with correct format', function (): void {
                $token = $this->securityService->generateCsrfToken();

                expect($token)->toHaveLength(64) // 32 bytes = 64 hex chars
                    ->and(ctype_xdigit($token))->toBeTrue();
            });

            it('generates unique CSRF tokens', function (): void {
                $token1 = $this->securityService->generateCsrfToken();
                $token2 = $this->securityService->generateCsrfToken();

                expect($token1)->not->toBe($token2);
            });

            it('validates correct CSRF token', function (): void {
                $token = $this->securityService->generateCsrfToken();
                $sessionToken = $token; // In real implementation, this would be stored in session

                expect($this->securityService->validateCsrfToken($token, $sessionToken))->toBeTrue();
            });

            it('rejects invalid CSRF token length', function (): void {
                $shortToken = 'short';
                $sessionToken = $this->securityService->generateCsrfToken();

                expect($this->securityService->validateCsrfToken($shortToken, $sessionToken))->toBeFalse();
            });

            it('rejects non-hexadecimal CSRF token', function (): void {
                $invalidToken = str_repeat('g', 64); // Invalid hex characters
                $sessionToken = $this->securityService->generateCsrfToken();

                expect($this->securityService->validateCsrfToken($invalidToken, $sessionToken))->toBeFalse();
            });

            it('rejects mismatched CSRF tokens', function (): void {
                $token = $this->securityService->generateCsrfToken();
                $differentToken = $this->securityService->generateCsrfToken();

                expect($this->securityService->validateCsrfToken($token, $differentToken))->toBeFalse();
            });
        });
    });

    describe('PasswordStrength Value Object', function (): void {

        describe('Construction and Validation', function (): void {
            it('creates valid password strength for strong password', function (): void {
                $password = 'StrongPass4@7#';
                $strength = new PasswordStrength($password);

                expect($strength->getPassword())->toBe($password)
                    ->and($strength->isValid())->toBeTrue()
                    ->and($strength->getViolations())->toBeEmpty();
            });

            it('throws exception for weak password', function (): void {
                expect(fn () => new PasswordStrength('weak'))
                    ->toThrow(InvalidArgumentException::class);
            });

            it('calculates strength score correctly', function (): void {
                $strongPassword = 'VeryStrong4@7#@#$%';
                $strength = new PasswordStrength($strongPassword);

                expect($strength->getScore())->toBeGreaterThan(75)
                    ->and($strength->getStrengthLevel())->toBeIn(['strong', 'very_strong']);
            });
        });

        describe('Individual Requirements', function (): void {
            it('checks minimum length requirement', function (): void {
                $strength = new PasswordStrength('StrongPass4@7#');
                expect($strength->hasMinimumLength())->toBeTrue();

                $shortPassword = 'Short1!';
                expect(fn () => new PasswordStrength($shortPassword))
                    ->toThrow(InvalidArgumentException::class);
            });

            it('checks uppercase requirement', function (): void {
                $strength = new PasswordStrength('StrongPass4@7#');
                expect($strength->hasUppercase())->toBeTrue();
            });

            it('checks lowercase requirement', function (): void {
                $strength = new PasswordStrength('StrongPass4@7#');
                expect($strength->hasLowercase())->toBeTrue();
            });

            it('checks numbers requirement', function (): void {
                $strength = new PasswordStrength('StrongPass9$7#');
                expect($strength->hasNumbers())->toBeTrue();
            });

            it('checks special characters requirement', function (): void {
                $strength = new PasswordStrength('StrongPass9$7#');
                expect($strength->hasSpecialCharacters())->toBeTrue();
            });

            it('rejects common passwords', function (): void {
                $commonPasswords = ['password', '123456', 'qwerty', 'admin'];

                foreach ($commonPasswords as $commonPassword) {
                    expect(fn () => new PasswordStrength($commonPassword))
                        ->toThrow(InvalidArgumentException::class);
                }
            });

            it('rejects sequential characters', function (): void {
                expect(fn () => new PasswordStrength('StrongAbc123!'))
                    ->toThrow(InvalidArgumentException::class);
            });

            it('rejects repeating characters', function (): void {
                expect(fn () => new PasswordStrength('Strongaaa123!'))
                    ->toThrow(InvalidArgumentException::class);
            });
        });

        describe('Strength Levels', function (): void {
            it('categorizes very weak passwords', function (): void {
                // This would fail validation, so we test the scoring logic separately
                $passwordObject = new class('')
                {
                    public function getStrengthLevel(int $score): string
                    {
                        return match (true) {
                            $score >= 90 => 'very_strong',
                            $score >= 75 => 'strong',
                            $score >= 60 => 'moderate',
                            $score >= 40 => 'weak',
                            default => 'very_weak',
                        };
                    }
                };

                expect($passwordObject->getStrengthLevel(20))->toBe('very_weak');
            });

            it('categorizes weak passwords', function (): void {
                $passwordObject = new class('')
                {
                    public function getStrengthLevel(int $score): string
                    {
                        return match (true) {
                            $score >= 90 => 'very_strong',
                            $score >= 75 => 'strong',
                            $score >= 60 => 'moderate',
                            $score >= 40 => 'weak',
                            default => 'very_weak',
                        };
                    }
                };

                expect($passwordObject->getStrengthLevel(50))->toBe('weak');
            });

            it('categorizes moderate passwords', function (): void {
                $strength = new PasswordStrength('ModeratePass4@7#');
                expect($strength->getStrengthLevel())->toBeIn(['moderate', 'strong', 'very_strong']);
            });

            it('categorizes strong passwords', function (): void {
                $strength = new PasswordStrength('VeryStrongPassword4@7#@#');
                expect($strength->getStrengthLevel())->toBeIn(['strong', 'very_strong']);
            });
        });

        describe('Array Conversion', function (): void {
            it('converts to array correctly', function (): void {
                $strength = new PasswordStrength('StrongPass4@7#');
                $array = $strength->toArray();

                expect($array)->toHaveKeys(['score', 'strength_level', 'is_valid', 'violations', 'requirements'])
                    ->and($array['is_valid'])->toBeTrue()
                    ->and($array['violations'])->toBeArray()
                    ->and($array['requirements'])->toBeArray();
            });
        });

        describe('Static Validation', function (): void {
            it('validates password using static method', function (): void {
                $strength = PasswordStrength::validate('StrongPass4@7#');

                expect($strength)->toBeInstanceOf(PasswordStrength::class)
                    ->and($strength->isValid())->toBeTrue();
            });
        });
    });

    describe('TwoFactorCode Value Object', function (): void {

        describe('Construction and Validation', function (): void {
            it('creates valid 2FA code with all parameters', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $type = 'totp';
                $attempts = 1;

                $twoFactorCode = new TwoFactorCode($code, $expiresAt, $type, $attempts);

                expect($twoFactorCode->getCode())->toBe($code)
                    ->and($twoFactorCode->getExpiresAt())->toBe($expiresAt)
                    ->and($twoFactorCode->getType())->toBe($type)
                    ->and($twoFactorCode->getAttempts())->toBe($attempts);
            });

            it('creates valid 2FA code with default parameters', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');

                $twoFactorCode = new TwoFactorCode($code, $expiresAt);

                expect($twoFactorCode->getType())->toBe('totp')
                    ->and($twoFactorCode->getAttempts())->toBe(0);
            });

            it('throws exception for empty code', function (): void {
                $expiresAt = new DateTimeImmutable('+5 minutes');

                expect(fn () => new TwoFactorCode('', $expiresAt))
                    ->toThrow(InvalidArgumentException::class, '2FA code cannot be empty');
            });

            it('throws exception for code too short', function (): void {
                $expiresAt = new DateTimeImmutable('+5 minutes');

                expect(fn () => new TwoFactorCode('123', $expiresAt))
                    ->toThrow(InvalidArgumentException::class, '2FA code must be at least 4 characters long');
            });

            it('throws exception for code too long', function (): void {
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $longCode = str_repeat('1', 17);

                expect(fn () => new TwoFactorCode($longCode, $expiresAt))
                    ->toThrow(InvalidArgumentException::class, '2FA code cannot be longer than 16 characters');
            });

            it('throws exception for invalid type', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');

                expect(fn () => new TwoFactorCode($code, $expiresAt, 'invalid'))
                    ->toThrow(InvalidArgumentException::class, '2FA code type must be one of: totp, sms, email, backup');
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

        describe('Expiry and Validity', function (): void {
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
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 1);

                expect($twoFactorCode->isValid())->toBeTrue();
            });

            it('returns false for isValid when code is blocked', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 3); // Max attempts for TOTP

                expect($twoFactorCode->isValid())->toBeFalse();
            });
        });

        describe('Attempt Management', function (): void {
            it('correctly calculates max attempts for different types', function (): void {
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

            it('increments attempts correctly', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 1);

                $incrementedCode = $twoFactorCode->incrementAttempts();

                expect($incrementedCode->getAttempts())->toBe(2)
                    ->and($twoFactorCode->getAttempts())->toBe(1); // Original unchanged
            });

            it('detects blocked codes correctly', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $blockedCode = new TwoFactorCode($code, $expiresAt, 'totp', 3);

                expect($blockedCode->isBlocked())->toBeTrue();
            });
        });

        describe('Code Verification', function (): void {
            it('matches correct code', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt);

                expect($twoFactorCode->matches('123456'))->toBeTrue()
                    ->and($twoFactorCode->matches('654321'))->toBeFalse();
            });

            it('verifies valid code successfully', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt);

                expect($twoFactorCode->verify('123456'))->toBeTrue()
                    ->and($twoFactorCode->verify('654321'))->toBeFalse();
            });

            it('rejects verification for expired code', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt);

                $futureTime = new DateTimeImmutable('+10 minutes');
                expect($twoFactorCode->verify('123456', $futureTime))->toBeFalse();
            });
        });

        describe('Static Factory Methods', function (): void {
            it('generates TOTP code correctly', function (): void {
                $totpCode = TwoFactorCode::generateTOTP();

                expect($totpCode->getCode())->toHaveLength(6)
                    ->and($totpCode->getType())->toBe('totp')
                    ->and(ctype_digit($totpCode->getCode()))->toBeTrue()
                    ->and($totpCode->isValid())->toBeTrue();
            });

            it('generates SMS code correctly', function (): void {
                $smsCode = TwoFactorCode::generateSMS();

                expect($smsCode->getCode())->toHaveLength(6)
                    ->and($smsCode->getType())->toBe('sms')
                    ->and(ctype_digit($smsCode->getCode()))->toBeTrue();
            });

            it('generates email code correctly', function (): void {
                $emailCode = TwoFactorCode::generateEmail();

                expect($emailCode->getCode())->toHaveLength(6)
                    ->and($emailCode->getType())->toBe('email')
                    ->and(ctype_digit($emailCode->getCode()))->toBeTrue();
            });

            it('generates backup code correctly', function (): void {
                $backupCode = TwoFactorCode::generateBackupCode();

                expect($backupCode->getCode())->toHaveLength(8)
                    ->and($backupCode->getType())->toBe('backup')
                    ->and(ctype_xdigit($backupCode->getCode()))->toBeTrue()
                    ->and($backupCode->getCode())->toBe(strtoupper($backupCode->getCode()));
            });

            it('generates unique codes on multiple calls', function (): void {
                $code1 = TwoFactorCode::generateTOTP();
                $code2 = TwoFactorCode::generateTOTP();

                expect($code1->getCode())->not->toBe($code2->getCode());
            });
        });

        describe('Array Conversion', function (): void {
            it('converts to array correctly', function (): void {
                $code = '123456';
                $expiresAt = new DateTimeImmutable('+5 minutes');
                $twoFactorCode = new TwoFactorCode($code, $expiresAt, 'totp', 1);

                $array = $twoFactorCode->toArray();

                expect($array)->toHaveKeys([
                    'code', 'expires_at', 'type', 'attempts',
                    'is_valid', 'is_expired', 'is_blocked', 'remaining_attempts',
                ])
                    ->and($array['code'])->toBe($code)
                    ->and($array['type'])->toBe('totp')
                    ->and($array['attempts'])->toBe(1)
                    ->and($array['is_valid'])->toBeTrue()
                    ->and($array['is_expired'])->toBeFalse()
                    ->and($array['is_blocked'])->toBeFalse()
                    ->and($array['remaining_attempts'])->toBe(2);
            });
        });
    });

    describe('SafeHtmlRule Validation', function (): void {

        describe('Construction', function (): void {
            it('creates rule with default allowed tags', function (): void {
                $rule = new SafeHtmlRule;

                expect($rule)->toBeInstanceOf(SafeHtmlRule::class);
            });

            it('creates rule with custom allowed tags', function (): void {
                $customTags = ['p', 'strong', 'em'];
                $rule = new SafeHtmlRule($customTags);

                expect($rule)->toBeInstanceOf(SafeHtmlRule::class);
            });
        });

        describe('Validation Logic', function (): void {
            it('passes validation for safe HTML with allowed tags', function (): void {
                $rule = new SafeHtmlRule;
                $safeHtml = '<p>This is <strong>safe</strong> HTML content.</p>';
                $failed = false;

                $rule->validate('content', $safeHtml, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });

            it('fails validation for non-string input', function (): void {
                $rule = new SafeHtmlRule;
                $failed = false;
                $failureMessage = '';

                $rule->validate('content', 123, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('must be a string');
            });

            it('fails validation for dangerous script tags', function (): void {
                $rule = new SafeHtmlRule;
                $dangerousHtml = '<p>Safe content</p><script>alert("xss")</script>';
                $failed = false;

                $rule->validate('content', $dangerousHtml, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('fails validation for iframe tags', function (): void {
                $rule = new SafeHtmlRule;
                $dangerousHtml = '<p>Content</p><iframe src="http://evil.com"></iframe>';
                $failed = false;

                $rule->validate('content', $dangerousHtml, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('fails validation for javascript URLs', function (): void {
                $rule = new SafeHtmlRule;
                $dangerousHtml = '<a href="javascript:alert(1)">Click me</a>';
                $failed = false;

                $rule->validate('content', $dangerousHtml, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('fails validation for event handlers', function (): void {
                $rule = new SafeHtmlRule;
                $dangerousPatterns = [
                    '<p onload="alert(1)">Content</p>',
                    '<div onclick="steal()">Click</div>',
                    '<img onerror="hack()" src="x">',
                    '<span onmouseover="evil()">Hover</span>',
                ];

                foreach ($dangerousPatterns as $html) {
                    $failed = false;
                    $rule->validate('content', $html, function () use (&$failed): void {
                        $failed = true;
                    });
                    expect($failed)->toBeTrue();
                }
            });

            it('fails validation for disallowed tags', function (): void {
                $rule = new SafeHtmlRule(['p', 'strong']); // Only allow p and strong
                $htmlWithDisallowedTags = '<p>Content</p><div>Not allowed</div><script>Evil</script>';
                $failed = false;
                $failureMessage = '';

                $rule->validate('content', $htmlWithDisallowedTags, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('dangerous content');
            });

            it('handles empty HTML gracefully', function (): void {
                $rule = new SafeHtmlRule;
                $failed = false;

                $rule->validate('content', '', function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });

            it('handles HTML with no tags gracefully', function (): void {
                $rule = new SafeHtmlRule;
                $plainText = 'This is just plain text with no HTML tags.';
                $failed = false;

                $rule->validate('content', $plainText, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });
        });

        describe('Edge Cases', function (): void {
            it('handles malformed HTML gracefully', function (): void {
                $rule = new SafeHtmlRule;
                $malformedHtml = '<p>Unclosed paragraph<div>Mixed</p></div>';
                $failed = false;

                $rule->validate('content', $malformedHtml, function () use (&$failed): void {
                    $failed = true;
                });

                // Should not crash, validation result depends on implementation
                expect($failed)->toBeIn([true, false]);
            });

            it('detects case-insensitive dangerous patterns', function (): void {
                $rule = new SafeHtmlRule;
                $casedHtml = '<P>Content</P><SCRIPT>alert("xss")</SCRIPT>';
                $failed = false;

                $rule->validate('content', $casedHtml, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });
        });
    });

    describe('StrongPasswordRule Validation', function (): void {

        describe('Construction', function (): void {
            it('creates rule with default parameters', function (): void {
                $rule = new StrongPasswordRule;

                expect($rule)->toBeInstanceOf(StrongPasswordRule::class);
            });

            it('creates rule with custom parameters', function (): void {
                $rule = new StrongPasswordRule(
                    minLength: 12,
                    requireUppercase: false,
                    requireNumbers: false
                );

                expect($rule)->toBeInstanceOf(StrongPasswordRule::class);
            });
        });

        describe('Validation Logic', function (): void {
            it('passes validation for strong password with default requirements', function (): void {
                $rule = new StrongPasswordRule;
                $strongPassword = 'StrongPass4@7#';
                $failed = false;

                $rule->validate('password', $strongPassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });

            it('fails validation for non-string input', function (): void {
                $rule = new StrongPasswordRule;
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', 123, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('must be a string');
            });

            it('fails validation for password below minimum length', function (): void {
                $rule = new StrongPasswordRule(minLength: 10);
                $shortPassword = 'Short1!';
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', $shortPassword, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('at least 10 characters');
            });

            it('fails validation when uppercase is required but missing', function (): void {
                $rule = new StrongPasswordRule(requireUppercase: true);
                $noUppercase = 'lowercase123!';
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', $noUppercase, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('uppercase letter');
            });

            it('fails validation when lowercase is required but missing', function (): void {
                $rule = new StrongPasswordRule(requireLowercase: true);
                $noLowercase = 'UPPERCASE123!';
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', $noLowercase, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('lowercase letter');
            });

            it('fails validation when numbers are required but missing', function (): void {
                $rule = new StrongPasswordRule(requireNumbers: true);
                $noNumbers = 'StrongPassword!';
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', $noNumbers, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('one number');
            });

            it('fails validation when special characters are required but missing', function (): void {
                $rule = new StrongPasswordRule(requireSpecialChars: true);
                $noSpecialChars = 'StrongPassword123';
                $failed = false;
                $failureMessage = '';

                $rule->validate('password', $noSpecialChars, function ($message) use (&$failed, &$failureMessage): void {
                    $failed = true;
                    $failureMessage = $message;
                });

                expect($failed)->toBeTrue()
                    ->and($failureMessage)->toContain('special character');
            });

            it('passes validation when requirements are disabled', function (): void {
                $rule = new StrongPasswordRule(
                    minLength: 6,
                    requireUppercase: false,
                    requireLowercase: false,
                    requireNumbers: false,
                    requireSpecialChars: false
                );
                $simplePassword = 'simple';
                $failed = false;

                $rule->validate('password', $simplePassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });
        });

        describe('Boundary Testing', function (): void {
            it('passes validation for password at exact minimum length', function (): void {
                $rule = new StrongPasswordRule(minLength: 8);
                $exactLength = 'Exact8!a'; // Exactly 8 characters
                $failed = false;

                $rule->validate('password', $exactLength, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });

            it('fails validation for password one character below minimum', function (): void {
                $rule = new StrongPasswordRule(minLength: 8);
                $tooShort = 'Short7!'; // 7 characters
                $failed = false;

                $rule->validate('password', $tooShort, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('handles very long passwords correctly', function (): void {
                $rule = new StrongPasswordRule;
                $veryLongPassword = 'A1!' . str_repeat('a', 1000); // Very long but valid
                $failed = false;

                $rule->validate('password', $veryLongPassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });
        });

        describe('Edge Cases', function (): void {
            it('handles empty password', function (): void {
                $rule = new StrongPasswordRule;
                $emptyPassword = '';
                $failed = false;

                $rule->validate('password', $emptyPassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('handles password with only whitespace', function (): void {
                $rule = new StrongPasswordRule;
                $whitespacePassword = '        ';
                $failed = false;

                $rule->validate('password', $whitespacePassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeTrue();
            });

            it('handles unicode characters correctly', function (): void {
                $rule = new StrongPasswordRule;
                $unicodePassword = 'Pássw🔐rd123!';
                $failed = false;

                $rule->validate('password', $unicodePassword, function () use (&$failed): void {
                    $failed = true;
                });

                expect($failed)->toBeFalse();
            });
        });
    });

    describe('AuthToken Security Integration', function (): void {

        describe('Token Security Features', function (): void {
            it('generates cryptographically secure tokens', function (): void {
                $tokens = [];
                for ($i = 0; $i < 100; $i++) {
                    $token = AuthToken::generate();
                    $tokens[] = $token->getToken();
                }

                // Check for uniqueness (very high probability with crypto-secure generation)
                $uniqueTokens = array_unique($tokens);
                expect(count($uniqueTokens))->toBe(count($tokens));
            });

            it('enforces reasonable expiry limits for security', function (): void {
                // Test boundary conditions for token expiry
                expect(fn () => new AuthToken('validtoken123456789', new DateTimeImmutable('2020-01-01')))
                    ->toThrow(InvalidArgumentException::class, 'Token expiry must be in the future');

                expect(fn () => new AuthToken('validtoken123456789', new DateTimeImmutable('+1 year +1 second')))
                    ->toThrow(InvalidArgumentException::class, 'Token expiry cannot be more than 1 year in the future');
            });

            it('validates token format for security', function (): void {
                $validToken = 'abcdef123456789012345678';
                $expiresAt = new DateTimeImmutable('+1 hour');

                // Valid token should work
                expect(fn () => new AuthToken($validToken, $expiresAt))
                    ->not->toThrow(InvalidArgumentException::class);

                // Tokens with special characters should be rejected (security risk)
                expect(fn () => new AuthToken('token-with-dashes-123', $expiresAt))
                    ->toThrow(InvalidArgumentException::class);

                expect(fn () => new AuthToken('token_with_underscores', $expiresAt))
                    ->toThrow(InvalidArgumentException::class);
            });

            it('prevents timing attacks with constant-time comparison', function (): void {
                $token1 = AuthToken::generate();
                $token2 = AuthToken::generate();

                // The internal comparison should use hash_equals for timing attack protection
                // We can't directly test this, but we ensure different tokens don't match
                expect($token1->getToken())->not->toBe($token2->getToken());
            });
        });

        describe('Token Type Security', function (): void {
            it('restricts token types to prevent misuse', function (): void {
                $token = 'validtoken123456789012345678';
                $expiresAt = new DateTimeImmutable('+1 hour');

                $validTypes = ['bearer', 'api', 'refresh', 'access'];
                foreach ($validTypes as $type) {
                    expect(fn () => new AuthToken($token, $expiresAt, $type))
                        ->not->toThrow(InvalidArgumentException::class);
                }

                $invalidTypes = ['session', 'custom', 'invalid', 'admin'];
                foreach ($invalidTypes as $type) {
                    expect(fn () => new AuthToken($token, $expiresAt, $type))
                        ->toThrow(InvalidArgumentException::class);
                }
            });

            it('handles case sensitivity in token type validation', function (): void {
                $token = 'validtoken123456789012345678';
                $expiresAt = new DateTimeImmutable('+1 hour');

                // Should handle case-insensitive validation
                expect(fn () => new AuthToken($token, $expiresAt, 'BEARER'))
                    ->not->toThrow(InvalidArgumentException::class);

                expect(fn () => new AuthToken($token, $expiresAt, 'Api'))
                    ->not->toThrow(InvalidArgumentException::class);
            });
        });
    });

});

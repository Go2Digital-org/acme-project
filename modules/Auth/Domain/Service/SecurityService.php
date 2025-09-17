<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Auth\Domain\ValueObject\AuthToken;

/**
 * Security Domain Service.
 *
 * Handles security-related operations and validations.
 */
class SecurityService
{
    private const RATE_LIMIT_REQUESTS = 100;

    private const RATE_LIMIT_WINDOW_MINUTES = 60;

    public function __construct()
    {
        // In a real implementation, this might inject repositories or external services
    }

    public function validateIpAddress(string $ipAddress): bool
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Invalid IP address format');
        }

        // Check for private/local IPs that might indicate development environment
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $this->isIpAddressTrusted($ipAddress);
        }

        return true; // Allow private IPs for development
    }

    public function isIpAddressTrusted(string $ipAddress): bool
    {
        // Validate IP format without recursion
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Invalid IP address format');
        }

        // Check against known bad IP lists (simulated)
        $suspiciousIps = [
            '192.0.2.1', // TEST-NET-1
            '198.51.100.1', // TEST-NET-2
            '203.0.113.1', // TEST-NET-3
        ];

        return ! in_array($ipAddress, $suspiciousIps, true);
    }

    /**
     * @param  array<string, mixed>  $recentActivity
     */
    public function detectSuspiciousActivity(
        string $ipAddress,
        string $userAgent,
        array $recentActivity = []
    ): bool {
        // Check for suspicious IP
        if (! $this->isIpAddressTrusted($ipAddress)) {
            return true;
        }

        // Check for suspicious user agent patterns
        if ($this->isSuspiciousUserAgent($userAgent)) {
            return true;
        }

        // Check for rapid requests from same IP
        return $this->hasExcessiveRequests($ipAddress, $recentActivity);
    }

    public function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/java/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        // Check for empty or too short user agents
        return strlen(trim($userAgent)) < 10;
    }

    /**
     * @param  array<string, mixed>  $recentActivity
     */
    public function hasExcessiveRequests(string $ipAddress, array $recentActivity): bool
    {
        $requestCount = 0;
        $windowStart = (new DateTimeImmutable)->modify('-' . self::RATE_LIMIT_WINDOW_MINUTES . ' minutes');

        foreach ($recentActivity as $activity) {
            if (
                $activity['ip_address'] === $ipAddress &&
                new DateTimeImmutable($activity['timestamp']) > $windowStart
            ) {
                $requestCount++;
            }
        }

        return $requestCount > self::RATE_LIMIT_REQUESTS;
    }

    public function generateSecureToken(int $length = 32): string
    {
        if ($length < 16 || $length > 256) {
            throw new InvalidArgumentException('Token length must be between 16 and 256 characters');
        }

        $bytesLength = max(1, (int) ($length / 2));

        return bin2hex(random_bytes($bytesLength));
    }

    public function generateSecurePassword(int $length = 16): string
    {
        if ($length < 12 || $length > 128) {
            throw new InvalidArgumentException('Password length must be between 12 and 128 characters');
        }

        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $password = '';

        // Ensure at least one character from each set
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    public function validateSessionToken(AuthToken $token, string $ipAddress, string $userAgent): bool
    {
        if (! $token->isValid()) {
            return false;
        }

        // In a real implementation, we might check if the token was issued for this IP/User Agent
        return $this->validateIpAddress($ipAddress);
    }

    public function encryptSensitiveData(string $data, string $key): string
    {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Encryption key must be at least 32 characters');
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

        if ($encrypted === false) {
            throw new InvalidArgumentException('Failed to encrypt data');
        }

        return base64_encode($iv . $encrypted);
    }

    public function decryptSensitiveData(string $encryptedData, string $key): string
    {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Decryption key must be at least 32 characters');
        }

        $data = base64_decode($encryptedData);
        if ($data === false) {
            throw new InvalidArgumentException('Invalid encrypted data format');
        }

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        if ($decrypted === false) {
            throw new InvalidArgumentException('Failed to decrypt data');
        }

        return $decrypted;
    }

    public function hashSensitiveData(string $data, string $salt = ''): string
    {
        if ($salt === '') {
            $salt = $this->generateSecureToken(16);
        }

        return hash('sha256', $data . $salt) . ':' . $salt;
    }

    public function verifySensitiveData(string $data, string $hashedData): bool
    {
        $parts = explode(':', $hashedData);
        if (count($parts) !== 2) {
            return false;
        }

        [$hash, $salt] = $parts;
        $expectedHash = hash('sha256', $data . $salt);

        return hash_equals($hash, $expectedHash);
    }

    public function sanitizeUserInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Trim whitespace
        $input = trim($input);

        // Remove control characters except tab, newline, and carriage return
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        return $cleaned ?? $input;
    }

    /**
     * @param  array<string, mixed>  $fileInfo
     */
    public function validateFileUpload(array $fileInfo): bool
    {
        // Check file size (max 10MB)
        if ($fileInfo['size'] > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('File size exceeds maximum limit of 10MB');
        }

        // Check allowed file types
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'application/pdf',
        ];

        if (! in_array($fileInfo['type'], $allowedTypes, true)) {
            throw new InvalidArgumentException('File type not allowed');
        }

        // Check for upload errors
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload error: ' . $fileInfo['error']);
        }

        return true;
    }

    public function generateCsrfToken(): string
    {
        return $this->generateSecureToken(64);
    }

    public function validateCsrfToken(string $token, string $sessionToken): bool
    {
        if (strlen($token) !== 64) { // 32 bytes = 64 hex chars
            return false;
        }

        if (! ctype_xdigit($token)) {
            return false;
        }

        // In a real implementation, this would check against stored session token
        return hash_equals($sessionToken, $token);
    }
}

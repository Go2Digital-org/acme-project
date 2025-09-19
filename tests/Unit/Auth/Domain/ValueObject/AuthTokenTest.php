<?php

declare(strict_types=1);

use Modules\Auth\Domain\ValueObject\AuthToken;

describe('AuthToken Value Object', function (): void {

    describe('Construction', function (): void {
        it('creates a valid auth token with all parameters', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $type = 'bearer';

            $authToken = new AuthToken($token, $expiresAt, $type);

            expect($authToken->getToken())->toBe($token)
                ->and($authToken->getExpiresAt())->toBe($expiresAt)
                ->and($authToken->getType())->toBe($type);
        });

        it('creates a valid auth token with default type', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->getToken())->toBe($token)
                ->and($authToken->getExpiresAt())->toBe($expiresAt)
                ->and($authToken->getType())->toBe('bearer');
        });

        it('throws exception for empty token', function (): void {
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken('', $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token cannot be empty');
        });

        it('throws exception for whitespace-only token', function (): void {
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken('   ', $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token cannot be empty');
        });

        it('throws exception for token shorter than 16 characters', function (): void {
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken('short', $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token must be at least 16 characters long');
        });

        it('throws exception for token with non-alphanumeric characters', function (): void {
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken('token-with-special!@#', $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token must contain only alphanumeric characters');
        });

        it('throws exception for invalid token type', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken($token, $expiresAt, 'invalid'))
                ->toThrow(InvalidArgumentException::class, 'Token type must be one of: bearer, api, refresh, access');
        });

        it('accepts all valid token types', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $validTypes = ['bearer', 'api', 'refresh', 'access'];

            foreach ($validTypes as $type) {
                $authToken = new AuthToken($token, $expiresAt, $type);
                expect($authToken->getType())->toBe($type);
            }
        });

        it('throws exception for past expiry date', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('-1 hour');

            expect(fn () => new AuthToken($token, $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token expiry must be in the future');
        });

        it('throws exception for expiry more than 1 year in future', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+2 years');

            expect(fn () => new AuthToken($token, $expiresAt))
                ->toThrow(InvalidArgumentException::class, 'Token expiry cannot be more than 1 year in the future');
        });
    });

    describe('Expiry Validation', function (): void {
        it('returns false for isExpired when token is valid', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->isExpired())->toBeFalse();
        });

        it('returns true for isExpired when token is expired', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $authToken = new AuthToken($token, $expiresAt);

            $futureTime = new \DateTimeImmutable('+2 hours');
            expect($authToken->isExpired($futureTime))->toBeTrue();
        });

        it('returns true for isValid when token is not expired', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->isValid())->toBeTrue();
        });

        it('returns false for isValid when token is expired', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $authToken = new AuthToken($token, $expiresAt);

            $futureTime = new \DateTimeImmutable('+2 hours');
            expect($authToken->isValid($futureTime))->toBeFalse();
        });

        it('calculates correct time to expiry', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+3600 seconds'); // 1 hour
            $authToken = new AuthToken($token, $expiresAt);

            $now = new \DateTimeImmutable;
            $timeToExpiry = $authToken->getTimeToExpiry($now);

            expect($timeToExpiry)->toBeGreaterThan(3500)
                ->and($timeToExpiry)->toBeLessThanOrEqual(3600);
        });

        it('returns zero time to expiry for expired tokens', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $authToken = new AuthToken($token, $expiresAt);

            $futureTime = new \DateTimeImmutable('+2 hours');
            expect($authToken->getTimeToExpiry($futureTime))->toBe(0);
        });
    });

    describe('Array Conversion', function (): void {
        it('converts to array correctly', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $type = 'api';
            $authToken = new AuthToken($token, $expiresAt, $type);

            $array = $authToken->toArray();

            expect($array)->toBe([
                'token' => $token,
                'expires_at' => $expiresAt->format(DateTimeImmutable::ATOM),
                'type' => $type,
            ]);
        });

        it('creates from array correctly', function (): void {
            $futureTime = (new \DateTimeImmutable('+1 hour'))->format(\DateTimeImmutable::ATOM);
            $data = [
                'token' => 'abcdef123456789012345678',
                'expires_at' => $futureTime,
                'type' => 'refresh',
            ];

            $authToken = AuthToken::fromArray($data);

            expect($authToken->getToken())->toBe($data['token'])
                ->and($authToken->getExpiresAt()->format(DateTimeImmutable::ATOM))->toBe($data['expires_at'])
                ->and($authToken->getType())->toBe($data['type']);
        });

        it('creates from array with default type when not provided', function (): void {
            $futureTime = (new \DateTimeImmutable('+1 hour'))->format(\DateTimeImmutable::ATOM);
            $data = [
                'token' => 'abcdef123456789012345678',
                'expires_at' => $futureTime,
            ];

            $authToken = AuthToken::fromArray($data);

            expect($authToken->getToken())->toBe($data['token'])
                ->and($authToken->getType())->toBe('bearer');
        });
    });

    describe('Static Factory Methods', function (): void {
        it('generates valid token with default parameters', function (): void {
            $authToken = AuthToken::generate();

            expect($authToken->getToken())->toHaveLength(64) // 32 bytes = 64 hex chars
                ->and($authToken->getType())->toBe('bearer')
                ->and($authToken->isValid())->toBeTrue();

            $timeToExpiry = $authToken->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(3500) // Should be close to 1 hour
                ->and($timeToExpiry)->toBeLessThanOrEqual(3600);
        });

        it('generates valid token with custom expiry minutes', function (): void {
            $expiryMinutes = 30;
            $authToken = AuthToken::generate($expiryMinutes);

            $timeToExpiry = $authToken->getTimeToExpiry();
            expect($timeToExpiry)->toBeGreaterThan(1700) // Should be close to 30 minutes
                ->and($timeToExpiry)->toBeLessThanOrEqual(1800);
        });

        it('generates valid token with custom type', function (): void {
            $type = 'refresh';
            $authToken = AuthToken::generate(60, $type);

            expect($authToken->getType())->toBe($type);
        });

        it('generates unique tokens on multiple calls', function (): void {
            $token1 = AuthToken::generate();
            $token2 = AuthToken::generate();

            expect($token1->getToken())->not->toBe($token2->getToken());
        });
    });

    describe('Edge Cases', function (): void {
        it('handles minimum valid token length', function (): void {
            $token = '1234567890123456'; // Exactly 16 characters
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->getToken())->toBe($token);
        });

        it('handles very long valid token', function (): void {
            $token = str_repeat('a', 256); // Very long but valid
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->getToken())->toBe($token);
        });

        it('handles expiry at maximum allowed time', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = (new \DateTimeImmutable)->modify('+1 year');

            $authToken = new AuthToken($token, $expiresAt);

            expect($authToken->isValid())->toBeTrue();
        });

        it('handles case-insensitive token type validation', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $authToken1 = new AuthToken($token, $expiresAt, 'BEARER');
            expect($authToken1->getType())->toBe('BEARER');

            $authToken2 = new AuthToken($token, $expiresAt, 'API');
            expect($authToken2->getType())->toBe('API');
        });

        it('preserves original case of token type', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = new \DateTimeImmutable('+1 hour');
            $type = 'BEARER';

            $authToken = new AuthToken($token, $expiresAt, $type);

            // The constructor validates lowercase but stores original
            expect($authToken->getType())->toBe($type);
        });
    });

    describe('Boundary Value Testing', function (): void {
        it('validates token with exactly 15 characters fails', function (): void {
            $token = '123456789012345'; // 15 characters
            $expiresAt = new \DateTimeImmutable('+1 hour');

            expect(fn () => new AuthToken($token, $expiresAt))
                ->toThrow(InvalidArgumentException::class);
        });

        it('validates token with exactly 16 characters passes', function (): void {
            $token = '1234567890123456'; // 16 characters
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $authToken = new AuthToken($token, $expiresAt);
            expect($authToken->getToken())->toBe($token);
        });

        it('validates expiry exactly 1 year in future passes', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = (new \DateTimeImmutable)->modify('+1 year');

            $authToken = new AuthToken($token, $expiresAt);
            expect($authToken->getToken())->toBe($token);
        });

        it('validates expiry exactly 1 second past expiry deadline fails', function (): void {
            $token = 'abcdef123456789012345678';
            $expiresAt = (new \DateTimeImmutable)->modify('+1 year +1 second');

            expect(fn () => new AuthToken($token, $expiresAt))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});

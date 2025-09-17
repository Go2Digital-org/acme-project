<?php

declare(strict_types=1);

use Modules\Auth\Domain\Event\PasswordChangedEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;

describe('PasswordChangedEvent', function () {

    describe('Construction', function () {
        it('creates event with all required parameters', function () {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00');

            $event = new PasswordChangedEvent($userId, $ipAddress, $userAgent, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('creates event with default occurredAt when not provided', function () {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

            $beforeCreation = new DateTimeImmutable;
            $event = new PasswordChangedEvent($userId, $ipAddress, $userAgent);
            $afterCreation = new DateTimeImmutable;

            expect($event->getOccurredAt())->toBeGreaterThanOrEqual($beforeCreation)
                ->and($event->getOccurredAt())->toBeLessThanOrEqual($afterCreation);
        });

        it('implements DomainEventInterface', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            expect($event)->toBeInstanceOf(DomainEventInterface::class);
        });
    });

    describe('Getters', function () {
        it('returns correct user ID', function () {
            $userId = 456;
            $event = new PasswordChangedEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe($userId);
        });

        it('returns correct IP address', function () {
            $ipAddress = '10.0.0.1';
            $event = new PasswordChangedEvent(123, $ipAddress, 'User Agent');

            expect($event->getIpAddress())->toBe($ipAddress);
        });

        it('returns correct user agent', function () {
            $userAgent = 'Custom User Agent String';
            $event = new PasswordChangedEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('returns correct occurred at timestamp', function () {
            $occurredAt = new DateTimeImmutable('2025-06-15 14:30:00');
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent', $occurredAt);

            expect($event->getOccurredAt())->toBe($occurredAt);
        });
    });

    describe('Domain Event Interface Methods', function () {
        it('returns correct event name', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventName())->toBe('auth.password.changed');
        });

        it('returns correct event data', function () {
            $userId = 789;
            $ipAddress = '172.16.0.1';
            $userAgent = 'Test User Agent';
            $occurredAt = new DateTimeImmutable('2025-03-01 09:15:30');

            $event = new PasswordChangedEvent($userId, $ipAddress, $userAgent, $occurredAt);

            $expectedData = [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'occurred_at' => $occurredAt->format(DateTimeImmutable::ATOM),
            ];

            expect($event->getEventData())->toBe($expectedData);
        });

        it('returns correct aggregate ID', function () {
            $userId = 555;
            $event = new PasswordChangedEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getAggregateId())->toBe($userId);
        });

        it('returns correct event version', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventVersion())->toBe(1);
        });

        it('returns correct context', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getContext())->toBe('Auth');
        });

        it('indicates event is not async', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            expect($event->isAsync())->toBeFalse();
        });
    });

    describe('Edge Cases', function () {
        it('handles zero user ID', function () {
            $event = new PasswordChangedEvent(0, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(0)
                ->and($event->getAggregateId())->toBe(0);
        });

        it('handles negative user ID', function () {
            $event = new PasswordChangedEvent(-1, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(-1)
                ->and($event->getAggregateId())->toBe(-1);
        });

        it('handles empty IP address', function () {
            $event = new PasswordChangedEvent(123, '', 'User Agent');

            expect($event->getIpAddress())->toBe('');
        });

        it('handles empty user agent', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', '');

            expect($event->getUserAgent())->toBe('');
        });

        it('handles IPv6 addresses', function () {
            $ipv6Address = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
            $event = new PasswordChangedEvent(123, $ipv6Address, 'User Agent');

            expect($event->getIpAddress())->toBe($ipv6Address);
        });

        it('handles very long user agent string', function () {
            $longUserAgent = str_repeat('A', 1000);
            $event = new PasswordChangedEvent(123, '192.168.1.1', $longUserAgent);

            expect($event->getUserAgent())->toBe($longUserAgent);
        });

        it('handles special characters in user agent', function () {
            $specialUserAgent = 'Mozilla/5.0 (特殊文字) "Test" & <script>';
            $event = new PasswordChangedEvent(123, '192.168.1.1', $specialUserAgent);

            expect($event->getUserAgent())->toBe($specialUserAgent);
        });
    });

    describe('Immutability', function () {
        it('creates immutable event object', function () {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';
            $occurredAt = new DateTimeImmutable;

            $event = new PasswordChangedEvent($userId, $ipAddress, $userAgent, $occurredAt);

            // Properties should be readonly, so we can't modify them
            // We can only verify they return the same values
            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('maintains consistent event data across multiple calls', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');

            $firstCall = $event->getEventData();
            $secondCall = $event->getEventData();

            expect($firstCall)->toBe($secondCall);
        });
    });

    describe('Date Formatting', function () {
        it('formats occurred at timestamp correctly in event data', function () {
            $occurredAt = new DateTimeImmutable('2025-12-25 15:45:30');
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent', $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles different timezone formats', function () {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00', new DateTimeZone('America/New_York'));
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent', $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles UTC timezone correctly', function () {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00 UTC');
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent', $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM))
                ->and($eventData['occurred_at'])->toEndWith('+00:00');
        });
    });

    describe('Real-world Scenarios', function () {
        it('handles typical web browser user agent', function () {
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
            $event = new PasswordChangedEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('handles mobile user agent', function () {
            $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1';
            $event = new PasswordChangedEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('handles private IP addresses', function () {
            $privateIps = ['192.168.1.1', '10.0.0.1', '172.16.0.1'];

            foreach ($privateIps as $ip) {
                $event = new PasswordChangedEvent(123, $ip, 'User Agent');
                expect($event->getIpAddress())->toBe($ip);
            }
        });

        it('handles public IP addresses', function () {
            $publicIps = ['8.8.8.8', '1.1.1.1', '208.67.222.222'];

            foreach ($publicIps as $ip) {
                $event = new PasswordChangedEvent(123, $ip, 'User Agent');
                expect($event->getIpAddress())->toBe($ip);
            }
        });

        it('handles localhost addresses', function () {
            $localhostAddresses = ['127.0.0.1', '::1', 'localhost'];

            foreach ($localhostAddresses as $address) {
                $event = new PasswordChangedEvent(123, $address, 'User Agent');
                expect($event->getIpAddress())->toBe($address);
            }
        });
    });

    describe('Event Data Structure', function () {
        it('includes all required fields in event data', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $event->getEventData();

            expect($eventData)->toHaveKeys(['user_id', 'ip_address', 'user_agent', 'occurred_at']);
        });

        it('has consistent data types in event data', function () {
            $event = new PasswordChangedEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBeInt()
                ->and($eventData['ip_address'])->toBeString()
                ->and($eventData['user_agent'])->toBeString()
                ->and($eventData['occurred_at'])->toBeString();
        });

        it('maintains data integrity across serialization', function () {
            $userId = 789;
            $ipAddress = '203.0.113.1';
            $userAgent = 'Test Agent';
            $occurredAt = new DateTimeImmutable('2025-05-15 10:30:45');

            $event = new PasswordChangedEvent($userId, $ipAddress, $userAgent, $occurredAt);
            $eventData = $event->getEventData();

            // Verify all data matches original input
            expect($eventData['user_id'])->toBe($userId)
                ->and($eventData['ip_address'])->toBe($ipAddress)
                ->and($eventData['user_agent'])->toBe($userAgent)
                ->and($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });
});

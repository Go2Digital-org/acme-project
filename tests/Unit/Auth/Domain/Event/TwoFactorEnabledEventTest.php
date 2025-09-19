<?php

declare(strict_types=1);

use Modules\Auth\Domain\Event\TwoFactorEnabledEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;

describe('TwoFactorEnabledEvent', function (): void {

    describe('Construction', function (): void {
        it('creates event with all required parameters', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $backupCodesGenerated = true;
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00');

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent, $backupCodesGenerated, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->isBackupCodesGenerated())->toBe($backupCodesGenerated)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('creates event with default backup codes generated when not provided', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent);

            expect($event->isBackupCodesGenerated())->toBeTrue();
        });

        it('creates event with default occurredAt when not provided', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';

            $beforeCreation = new DateTimeImmutable;
            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent);
            $afterCreation = new DateTimeImmutable;

            expect($event->getOccurredAt())->toBeGreaterThanOrEqual($beforeCreation)
                ->and($event->getOccurredAt())->toBeLessThanOrEqual($afterCreation);
        });

        it('creates event with backup codes disabled', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', false);

            expect($event->isBackupCodesGenerated())->toBeFalse();
        });

        it('implements DomainEventInterface', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event)->toBeInstanceOf(DomainEventInterface::class);
        });
    });

    describe('Getters', function (): void {
        it('returns correct user ID', function (): void {
            $userId = 456;
            $event = new TwoFactorEnabledEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe($userId);
        });

        it('returns correct IP address', function (): void {
            $ipAddress = '10.0.0.1';
            $event = new TwoFactorEnabledEvent(123, $ipAddress, 'User Agent');

            expect($event->getIpAddress())->toBe($ipAddress);
        });

        it('returns correct user agent', function (): void {
            $userAgent = 'Custom User Agent String';
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('returns correct backup codes generated status', function (): void {
            $eventWithBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true);
            $eventWithoutBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', false);

            expect($eventWithBackup->isBackupCodesGenerated())->toBeTrue()
                ->and($eventWithoutBackup->isBackupCodesGenerated())->toBeFalse();
        });

        it('returns correct occurred at timestamp', function (): void {
            $occurredAt = new DateTimeImmutable('2025-06-15 14:30:00');
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true, $occurredAt);

            expect($event->getOccurredAt())->toBe($occurredAt);
        });
    });

    describe('Domain Event Interface Methods', function (): void {
        it('returns correct event name', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventName())->toBe('auth.two_factor.enabled');
        });

        it('returns correct event data with backup codes enabled', function (): void {
            $userId = 789;
            $ipAddress = '172.16.0.1';
            $userAgent = 'Test User Agent';
            $backupCodesGenerated = true;
            $occurredAt = new DateTimeImmutable('2025-03-01 09:15:30');

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent, $backupCodesGenerated, $occurredAt);

            $expectedData = [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'backup_codes_generated' => $backupCodesGenerated,
                'occurred_at' => $occurredAt->format(DateTimeImmutable::ATOM),
            ];

            expect($event->getEventData())->toBe($expectedData);
        });

        it('returns correct event data with backup codes disabled', function (): void {
            $userId = 789;
            $ipAddress = '172.16.0.1';
            $userAgent = 'Test User Agent';
            $backupCodesGenerated = false;
            $occurredAt = new DateTimeImmutable('2025-03-01 09:15:30');

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent, $backupCodesGenerated, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['backup_codes_generated'])->toBeFalse();
        });

        it('returns correct aggregate ID', function (): void {
            $userId = 555;
            $event = new TwoFactorEnabledEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getAggregateId())->toBe($userId);
        });

        it('returns correct event version', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventVersion())->toBe(1);
        });

        it('returns correct context', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getContext())->toBe('Auth');
        });

        it('indicates event is not async', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->isAsync())->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero user ID', function (): void {
            $event = new TwoFactorEnabledEvent(0, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(0)
                ->and($event->getAggregateId())->toBe(0);
        });

        it('handles negative user ID', function (): void {
            $event = new TwoFactorEnabledEvent(-1, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(-1)
                ->and($event->getAggregateId())->toBe(-1);
        });

        it('handles empty IP address', function (): void {
            $event = new TwoFactorEnabledEvent(123, '', 'User Agent');

            expect($event->getIpAddress())->toBe('');
        });

        it('handles empty user agent', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', '');

            expect($event->getUserAgent())->toBe('');
        });

        it('handles IPv6 addresses', function (): void {
            $ipv6Address = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
            $event = new TwoFactorEnabledEvent(123, $ipv6Address, 'User Agent');

            expect($event->getIpAddress())->toBe($ipv6Address);
        });

        it('handles very long user agent string', function (): void {
            $longUserAgent = str_repeat('A', 1000);
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', $longUserAgent);

            expect($event->getUserAgent())->toBe($longUserAgent);
        });

        it('handles special characters in user agent', function (): void {
            $specialUserAgent = 'Mozilla/5.0 (特殊文字) "Test" & <script>';
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', $specialUserAgent);

            expect($event->getUserAgent())->toBe($specialUserAgent);
        });
    });

    describe('Backup Codes Feature', function (): void {
        it('correctly identifies when backup codes are generated', function (): void {
            $eventWithBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true);
            $eventWithoutBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', false);

            expect($eventWithBackup->isBackupCodesGenerated())->toBeTrue()
                ->and($eventWithoutBackup->isBackupCodesGenerated())->toBeFalse();
        });

        it('includes backup codes status in event data', function (): void {
            $eventWithBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true);
            $eventWithoutBackup = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', false);

            $dataWithBackup = $eventWithBackup->getEventData();
            $dataWithoutBackup = $eventWithoutBackup->getEventData();

            expect($dataWithBackup['backup_codes_generated'])->toBeTrue()
                ->and($dataWithoutBackup['backup_codes_generated'])->toBeFalse();
        });

        it('defaults to backup codes generated when not specified', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->isBackupCodesGenerated())->toBeTrue();
        });
    });

    describe('Immutability', function (): void {
        it('creates immutable event object', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';
            $backupCodesGenerated = true;
            $occurredAt = new DateTimeImmutable;

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent, $backupCodesGenerated, $occurredAt);

            // Properties should be readonly, so we can't modify them
            // We can only verify they return the same values
            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->isBackupCodesGenerated())->toBe($backupCodesGenerated)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('maintains consistent event data across multiple calls', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            $firstCall = $event->getEventData();
            $secondCall = $event->getEventData();

            expect($firstCall)->toBe($secondCall);
        });
    });

    describe('Date Formatting', function (): void {
        it('formats occurred at timestamp correctly in event data', function (): void {
            $occurredAt = new DateTimeImmutable('2025-12-25 15:45:30');
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles different timezone formats', function (): void {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00', new DateTimeZone('America/New_York'));
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles UTC timezone correctly', function (): void {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00 UTC');
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true, $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM))
                ->and($eventData['occurred_at'])->toEndWith('+00:00');
        });
    });

    describe('Real-world Scenarios', function (): void {
        it('handles typical web browser user agent', function (): void {
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('handles mobile user agent', function (): void {
            $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1';
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('handles private IP addresses', function (): void {
            $privateIps = ['192.168.1.1', '10.0.0.1', '172.16.0.1'];

            foreach ($privateIps as $ip) {
                $event = new TwoFactorEnabledEvent(123, $ip, 'User Agent');
                expect($event->getIpAddress())->toBe($ip);
            }
        });

        it('handles public IP addresses', function (): void {
            $publicIps = ['8.8.8.8', '1.1.1.1', '208.67.222.222'];

            foreach ($publicIps as $ip) {
                $event = new TwoFactorEnabledEvent(123, $ip, 'User Agent');
                expect($event->getIpAddress())->toBe($ip);
            }
        });

        it('handles localhost addresses', function (): void {
            $localhostAddresses = ['127.0.0.1', '::1', 'localhost'];

            foreach ($localhostAddresses as $address) {
                $event = new TwoFactorEnabledEvent(123, $address, 'User Agent');
                expect($event->getIpAddress())->toBe($address);
            }
        });

        it('represents scenario where user enables 2FA with backup codes', function (): void {
            $event = new TwoFactorEnabledEvent(
                userId: 123,
                ipAddress: '192.168.1.100',
                userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                backupCodesGenerated: true
            );

            expect($event->getEventName())->toBe('auth.two_factor.enabled')
                ->and($event->isBackupCodesGenerated())->toBeTrue()
                ->and($event->getEventData()['backup_codes_generated'])->toBeTrue();
        });

        it('represents scenario where user enables 2FA without backup codes', function (): void {
            $event = new TwoFactorEnabledEvent(
                userId: 456,
                ipAddress: '10.0.0.50',
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                backupCodesGenerated: false
            );

            expect($event->getEventName())->toBe('auth.two_factor.enabled')
                ->and($event->isBackupCodesGenerated())->toBeFalse()
                ->and($event->getEventData()['backup_codes_generated'])->toBeFalse();
        });
    });

    describe('Event Data Structure', function (): void {
        it('includes all required fields in event data', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $event->getEventData();

            expect($eventData)->toHaveKeys([
                'user_id',
                'ip_address',
                'user_agent',
                'backup_codes_generated',
                'occurred_at',
            ]);
        });

        it('has consistent data types in event data', function (): void {
            $event = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent', true);
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBeInt()
                ->and($eventData['ip_address'])->toBeString()
                ->and($eventData['user_agent'])->toBeString()
                ->and($eventData['backup_codes_generated'])->toBeBool()
                ->and($eventData['occurred_at'])->toBeString();
        });

        it('maintains data integrity across serialization', function (): void {
            $userId = 789;
            $ipAddress = '203.0.113.1';
            $userAgent = 'Test Agent';
            $backupCodesGenerated = false;
            $occurredAt = new DateTimeImmutable('2025-05-15 10:30:45');

            $event = new TwoFactorEnabledEvent($userId, $ipAddress, $userAgent, $backupCodesGenerated, $occurredAt);
            $eventData = $event->getEventData();

            // Verify all data matches original input
            expect($eventData['user_id'])->toBe($userId)
                ->and($eventData['ip_address'])->toBe($ipAddress)
                ->and($eventData['user_agent'])->toBe($userAgent)
                ->and($eventData['backup_codes_generated'])->toBe($backupCodesGenerated)
                ->and($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });

    describe('Comparison with PasswordChangedEvent', function (): void {
        it('has different event name than password changed event', function (): void {
            $twoFactorEvent = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');

            expect($twoFactorEvent->getEventName())->toBe('auth.two_factor.enabled')
                ->and($twoFactorEvent->getEventName())->not->toBe('auth.password.changed');
        });

        it('has additional backup codes field compared to password changed event', function (): void {
            $twoFactorEvent = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $twoFactorEvent->getEventData();

            expect($eventData)->toHaveKey('backup_codes_generated');
        });

        it('shares common fields with other auth events', function (): void {
            $twoFactorEvent = new TwoFactorEnabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $twoFactorEvent->getEventData();

            $commonFields = ['user_id', 'ip_address', 'user_agent', 'occurred_at'];

            foreach ($commonFields as $field) {
                expect($eventData)->toHaveKey($field);
            }
        });
    });
});

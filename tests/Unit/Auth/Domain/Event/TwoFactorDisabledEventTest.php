<?php

declare(strict_types=1);

use Modules\Auth\Domain\Event\TwoFactorDisabledEvent;
use Modules\Shared\Domain\Event\DomainEventInterface;

describe('TwoFactorDisabledEvent', function (): void {

    describe('Construction', function (): void {
        it('creates event with all required parameters', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $disabledReason = 'security_breach';
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00');

            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent, $disabledReason, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->getDisabledReason())->toBe($disabledReason)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('creates event with default disabled reason when not provided', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';

            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent);

            expect($event->getDisabledReason())->toBe('user_request');
        });

        it('creates event with default occurredAt when not provided', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';

            $beforeCreation = new DateTimeImmutable;
            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent);
            $afterCreation = new DateTimeImmutable;

            expect($event->getOccurredAt())->toBeGreaterThanOrEqual($beforeCreation)
                ->and($event->getOccurredAt())->toBeLessThanOrEqual($afterCreation);
        });

        it('implements DomainEventInterface', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event)->toBeInstanceOf(DomainEventInterface::class);
        });
    });

    describe('Getters', function (): void {
        it('returns correct user ID', function (): void {
            $userId = 456;
            $event = new TwoFactorDisabledEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe($userId);
        });

        it('returns correct IP address', function (): void {
            $ipAddress = '10.0.0.1';
            $event = new TwoFactorDisabledEvent(123, $ipAddress, 'User Agent');

            expect($event->getIpAddress())->toBe($ipAddress);
        });

        it('returns correct user agent', function (): void {
            $userAgent = 'Custom User Agent String';
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', $userAgent);

            expect($event->getUserAgent())->toBe($userAgent);
        });

        it('returns correct disabled reason', function (): void {
            $reasons = ['user_request', 'security_breach', 'admin_action', 'device_lost'];

            foreach ($reasons as $reason) {
                $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', $reason);
                expect($event->getDisabledReason())->toBe($reason);
            }
        });

        it('returns correct occurred at timestamp', function (): void {
            $occurredAt = new DateTimeImmutable('2025-06-15 14:30:00');
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'user_request', $occurredAt);

            expect($event->getOccurredAt())->toBe($occurredAt);
        });
    });

    describe('Domain Event Interface Methods', function (): void {
        it('returns correct event name', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventName())->toBe('auth.two_factor.disabled');
        });

        it('returns correct event data with all fields', function (): void {
            $userId = 789;
            $ipAddress = '172.16.0.1';
            $userAgent = 'Test User Agent';
            $disabledReason = 'security_breach';
            $occurredAt = new DateTimeImmutable('2025-03-01 09:15:30');

            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent, $disabledReason, $occurredAt);

            $expectedData = [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'disabled_reason' => $disabledReason,
                'occurred_at' => $occurredAt->format(DateTimeImmutable::ATOM),
            ];

            expect($event->getEventData())->toBe($expectedData);
        });

        it('returns correct aggregate ID', function (): void {
            $userId = 555;
            $event = new TwoFactorDisabledEvent($userId, '192.168.1.1', 'User Agent');

            expect($event->getAggregateId())->toBe($userId);
        });

        it('returns correct event version', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getEventVersion())->toBe(1);
        });

        it('returns correct context', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->getContext())->toBe('Auth');
        });

        it('indicates event is not async', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($event->isAsync())->toBeFalse();
        });
    });

    describe('Disabled Reason Scenarios', function (): void {
        it('handles user request reason', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'user_request');

            expect($event->getDisabledReason())->toBe('user_request');
        });

        it('handles security breach reason', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'security_breach');

            expect($event->getDisabledReason())->toBe('security_breach');
        });

        it('handles admin action reason', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'admin_action');

            expect($event->getDisabledReason())->toBe('admin_action');
        });

        it('handles device lost reason', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'device_lost');

            expect($event->getDisabledReason())->toBe('device_lost');
        });

        it('handles custom disabled reason', function (): void {
            $customReason = 'company_policy_change';
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', $customReason);

            expect($event->getDisabledReason())->toBe($customReason);
        });

        it('includes disabled reason in event data', function (): void {
            $reason = 'emergency_access';
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', $reason);

            $eventData = $event->getEventData();

            expect($eventData['disabled_reason'])->toBe($reason);
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero user ID', function (): void {
            $event = new TwoFactorDisabledEvent(0, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(0)
                ->and($event->getAggregateId())->toBe(0);
        });

        it('handles negative user ID', function (): void {
            $event = new TwoFactorDisabledEvent(-1, '192.168.1.1', 'User Agent');

            expect($event->getUserId())->toBe(-1)
                ->and($event->getAggregateId())->toBe(-1);
        });

        it('handles empty IP address', function (): void {
            $event = new TwoFactorDisabledEvent(123, '', 'User Agent');

            expect($event->getIpAddress())->toBe('');
        });

        it('handles empty user agent', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', '');

            expect($event->getUserAgent())->toBe('');
        });

        it('handles empty disabled reason', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', '');

            expect($event->getDisabledReason())->toBe('');
        });

        it('handles IPv6 addresses', function (): void {
            $ipv6Address = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
            $event = new TwoFactorDisabledEvent(123, $ipv6Address, 'User Agent');

            expect($event->getIpAddress())->toBe($ipv6Address);
        });

        it('handles very long disabled reason', function (): void {
            $longReason = str_repeat('reason_', 100);
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', $longReason);

            expect($event->getDisabledReason())->toBe($longReason);
        });

        it('handles special characters in disabled reason', function (): void {
            $specialReason = 'user-requested (急須) & "urgent"';
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', $specialReason);

            expect($event->getDisabledReason())->toBe($specialReason);
        });
    });

    describe('Real-world Scenarios', function (): void {
        it('represents user voluntarily disabling 2FA', function (): void {
            $event = new TwoFactorDisabledEvent(
                userId: 123,
                ipAddress: '192.168.1.100',
                userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                disabledReason: 'user_request'
            );

            expect($event->getEventName())->toBe('auth.two_factor.disabled')
                ->and($event->getDisabledReason())->toBe('user_request')
                ->and($event->getEventData()['disabled_reason'])->toBe('user_request');
        });

        it('represents admin disabling 2FA for security reasons', function (): void {
            $event = new TwoFactorDisabledEvent(
                userId: 456,
                ipAddress: '10.0.0.50',
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                disabledReason: 'security_breach'
            );

            expect($event->getDisabledReason())->toBe('security_breach');
        });

        it('represents 2FA disabled due to lost device', function (): void {
            $event = new TwoFactorDisabledEvent(
                userId: 789,
                ipAddress: '203.0.113.1',
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)',
                disabledReason: 'device_lost'
            );

            expect($event->getDisabledReason())->toBe('device_lost');
        });

        it('represents emergency access scenario', function (): void {
            $event = new TwoFactorDisabledEvent(
                userId: 999,
                ipAddress: '127.0.0.1',
                userAgent: 'Emergency Access Tool v1.0',
                disabledReason: 'emergency_access'
            );

            expect($event->getDisabledReason())->toBe('emergency_access');
        });
    });

    describe('Immutability', function (): void {
        it('creates immutable event object', function (): void {
            $userId = 123;
            $ipAddress = '192.168.1.1';
            $userAgent = 'User Agent';
            $disabledReason = 'user_request';
            $occurredAt = new DateTimeImmutable;

            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent, $disabledReason, $occurredAt);

            expect($event->getUserId())->toBe($userId)
                ->and($event->getIpAddress())->toBe($ipAddress)
                ->and($event->getUserAgent())->toBe($userAgent)
                ->and($event->getDisabledReason())->toBe($disabledReason)
                ->and($event->getOccurredAt())->toBe($occurredAt);
        });

        it('maintains consistent event data across multiple calls', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            $firstCall = $event->getEventData();
            $secondCall = $event->getEventData();

            expect($firstCall)->toBe($secondCall);
        });
    });

    describe('Event Data Structure', function (): void {
        it('includes all required fields in event data', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $event->getEventData();

            expect($eventData)->toHaveKeys([
                'user_id',
                'ip_address',
                'user_agent',
                'disabled_reason',
                'occurred_at',
            ]);
        });

        it('has consistent data types in event data', function (): void {
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'user_request');
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBeInt()
                ->and($eventData['ip_address'])->toBeString()
                ->and($eventData['user_agent'])->toBeString()
                ->and($eventData['disabled_reason'])->toBeString()
                ->and($eventData['occurred_at'])->toBeString();
        });

        it('maintains data integrity across serialization', function (): void {
            $userId = 789;
            $ipAddress = '203.0.113.1';
            $userAgent = 'Test Agent';
            $disabledReason = 'admin_action';
            $occurredAt = new DateTimeImmutable('2025-05-15 10:30:45');

            $event = new TwoFactorDisabledEvent($userId, $ipAddress, $userAgent, $disabledReason, $occurredAt);
            $eventData = $event->getEventData();

            expect($eventData['user_id'])->toBe($userId)
                ->and($eventData['ip_address'])->toBe($ipAddress)
                ->and($eventData['user_agent'])->toBe($userAgent)
                ->and($eventData['disabled_reason'])->toBe($disabledReason)
                ->and($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });

    describe('Comparison with Other Auth Events', function (): void {
        it('has different event name than other auth events', function (): void {
            $disabledEvent = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');

            expect($disabledEvent->getEventName())->toBe('auth.two_factor.disabled')
                ->and($disabledEvent->getEventName())->not->toBe('auth.two_factor.enabled')
                ->and($disabledEvent->getEventName())->not->toBe('auth.password.changed');
        });

        it('has unique disabled_reason field', function (): void {
            $disabledEvent = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $disabledEvent->getEventData();

            expect($eventData)->toHaveKey('disabled_reason');
        });

        it('shares common fields with other auth events', function (): void {
            $disabledEvent = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent');
            $eventData = $disabledEvent->getEventData();

            $commonFields = ['user_id', 'ip_address', 'user_agent', 'occurred_at'];

            foreach ($commonFields as $field) {
                expect($eventData)->toHaveKey($field);
            }
        });
    });

    describe('Date Formatting', function (): void {
        it('formats occurred at timestamp correctly in event data', function (): void {
            $occurredAt = new DateTimeImmutable('2025-12-25 15:45:30');
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'user_request', $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });

        it('handles different timezone formats', function (): void {
            $occurredAt = new DateTimeImmutable('2025-01-01 12:00:00', new DateTimeZone('Europe/London'));
            $event = new TwoFactorDisabledEvent(123, '192.168.1.1', 'User Agent', 'user_request', $occurredAt);

            $eventData = $event->getEventData();

            expect($eventData['occurred_at'])->toBe($occurredAt->format(DateTimeImmutable::ATOM));
        });
    });
});

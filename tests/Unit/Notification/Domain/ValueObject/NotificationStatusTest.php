<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\NotificationStatus;

describe('NotificationStatus Value Object', function (): void {
    describe('Constants', function (): void {
        it('defines all required status constants', function (): void {
            expect(NotificationStatus::PENDING)->toBe('pending')
                ->and(NotificationStatus::SENT)->toBe('sent')
                ->and(NotificationStatus::FAILED)->toBe('failed')
                ->and(NotificationStatus::CANCELLED)->toBe('cancelled')
                ->and(NotificationStatus::DELIVERED)->toBe('delivered');
        });
    });

    describe('all() method', function (): void {
        it('returns all valid notification statuses', function (): void {
            $expected = [
                'pending',
                'sent',
                'failed',
                'cancelled',
                'delivered',
            ];

            expect(NotificationStatus::all())->toBe($expected);
        });

        it('returns array with correct count', function (): void {
            expect(NotificationStatus::all())->toHaveCount(5);
        });
    });

    describe('isValid() method', function (): void {
        it('validates valid statuses', function (): void {
            expect(NotificationStatus::isValid('pending'))->toBeTrue()
                ->and(NotificationStatus::isValid('sent'))->toBeTrue()
                ->and(NotificationStatus::isValid('failed'))->toBeTrue()
                ->and(NotificationStatus::isValid('cancelled'))->toBeTrue()
                ->and(NotificationStatus::isValid('delivered'))->toBeTrue();
        });

        it('rejects invalid statuses', function (): void {
            expect(NotificationStatus::isValid('invalid'))->toBeFalse()
                ->and(NotificationStatus::isValid('processing'))->toBeFalse()
                ->and(NotificationStatus::isValid(''))->toBeFalse()
                ->and(NotificationStatus::isValid('PENDING'))->toBeFalse(); // Case sensitive
        });
    });

    describe('successStates() method', function (): void {
        it('returns statuses representing successful delivery', function (): void {
            $expected = ['sent', 'delivered'];

            expect(NotificationStatus::successStates())->toBe($expected);
        });

        it('includes sent status', function (): void {
            expect(NotificationStatus::successStates())->toContain('sent');
        });

        it('includes delivered status', function (): void {
            expect(NotificationStatus::successStates())->toContain('delivered');
        });

        it('does not include failure states', function (): void {
            $successStates = NotificationStatus::successStates();

            expect($successStates)->not->toContain('failed')
                ->and($successStates)->not->toContain('cancelled')
                ->and($successStates)->not->toContain('pending');
        });
    });

    describe('failureStates() method', function (): void {
        it('returns statuses representing failure or problems', function (): void {
            $expected = ['failed', 'cancelled'];

            expect(NotificationStatus::failureStates())->toBe($expected);
        });

        it('includes failed status', function (): void {
            expect(NotificationStatus::failureStates())->toContain('failed');
        });

        it('includes cancelled status', function (): void {
            expect(NotificationStatus::failureStates())->toContain('cancelled');
        });

        it('does not include success states', function (): void {
            $failureStates = NotificationStatus::failureStates();

            expect($failureStates)->not->toContain('sent')
                ->and($failureStates)->not->toContain('delivered')
                ->and($failureStates)->not->toContain('pending');
        });
    });

    describe('label() method', function (): void {
        it('returns human-readable labels for valid statuses', function (): void {
            expect(NotificationStatus::label('pending'))->toBe('Pending')
                ->and(NotificationStatus::label('sent'))->toBe('Sent')
                ->and(NotificationStatus::label('failed'))->toBe('Failed')
                ->and(NotificationStatus::label('cancelled'))->toBe('Cancelled')
                ->and(NotificationStatus::label('delivered'))->toBe('Delivered');
        });

        it('returns Unknown for invalid status', function (): void {
            expect(NotificationStatus::label('invalid'))->toBe('Unknown')
                ->and(NotificationStatus::label(''))->toBe('Unknown')
                ->and(NotificationStatus::label('PENDING'))->toBe('Unknown');
        });

        it('uses proper capitalization', function (): void {
            expect(NotificationStatus::label('pending'))->toStartWith('P')
                ->and(NotificationStatus::label('sent'))->toStartWith('S')
                ->and(NotificationStatus::label('failed'))->toStartWith('F')
                ->and(NotificationStatus::label('cancelled'))->toStartWith('C')
                ->and(NotificationStatus::label('delivered'))->toStartWith('D');
        });
    });

    describe('colorClass() method', function (): void {
        it('returns appropriate color classes for UI display', function (): void {
            expect(NotificationStatus::colorClass('pending'))->toBe('warning')
                ->and(NotificationStatus::colorClass('sent'))->toBe('success')
                ->and(NotificationStatus::colorClass('delivered'))->toBe('success')
                ->and(NotificationStatus::colorClass('failed'))->toBe('danger')
                ->and(NotificationStatus::colorClass('cancelled'))->toBe('gray');
        });

        it('returns gray for unknown statuses', function (): void {
            expect(NotificationStatus::colorClass('invalid'))->toBe('gray')
                ->and(NotificationStatus::colorClass(''))->toBe('gray')
                ->and(NotificationStatus::colorClass('unknown'))->toBe('gray');
        });

        it('groups success states with same color', function (): void {
            expect(NotificationStatus::colorClass('sent'))->toBe(NotificationStatus::colorClass('delivered'));
        });
    });

    describe('isFinalState() method', function (): void {
        it('identifies final states correctly', function (): void {
            expect(NotificationStatus::isFinalState('sent'))->toBeTrue()
                ->and(NotificationStatus::isFinalState('delivered'))->toBeTrue()
                ->and(NotificationStatus::isFinalState('failed'))->toBeTrue()
                ->and(NotificationStatus::isFinalState('cancelled'))->toBeTrue();
        });

        it('identifies non-final states correctly', function (): void {
            expect(NotificationStatus::isFinalState('pending'))->toBeFalse();
        });

        it('returns false for invalid statuses', function (): void {
            expect(NotificationStatus::isFinalState('invalid'))->toBeFalse()
                ->and(NotificationStatus::isFinalState(''))->toBeFalse()
                ->and(NotificationStatus::isFinalState('processing'))->toBeFalse();
        });

        it('considers all success and failure states as final', function (): void {
            $successStates = NotificationStatus::successStates();
            $failureStates = NotificationStatus::failureStates();
            $finalStates = array_merge($successStates, $failureStates);

            foreach ($finalStates as $status) {
                expect(NotificationStatus::isFinalState($status))->toBeTrue();
            }
        });
    });

    describe('State Classification', function (): void {
        it('has no overlap between success and failure states', function (): void {
            $successStates = NotificationStatus::successStates();
            $failureStates = NotificationStatus::failureStates();
            $overlap = array_intersect($successStates, $failureStates);

            expect($overlap)->toBeEmpty();
        });

        it('accounts for all states except pending in success or failure', function (): void {
            $allStates = NotificationStatus::all();
            $successStates = NotificationStatus::successStates();
            $failureStates = NotificationStatus::failureStates();
            $classified = array_merge($successStates, $failureStates, ['pending']);

            sort($allStates);
            sort($classified);

            expect($classified)->toBe($allStates);
        });
    });
});

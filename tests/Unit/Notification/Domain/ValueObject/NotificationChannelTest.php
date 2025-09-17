<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\NotificationChannel;

describe('NotificationChannel Value Object', function (): void {
    describe('Constants', function (): void {
        it('defines all required channel constants', function (): void {
            expect(NotificationChannel::DATABASE)->toBe('database')
                ->and(NotificationChannel::EMAIL)->toBe('email')
                ->and(NotificationChannel::SMS)->toBe('sms')
                ->and(NotificationChannel::PUSH)->toBe('push')
                ->and(NotificationChannel::SLACK)->toBe('slack')
                ->and(NotificationChannel::WEBHOOK)->toBe('webhook')
                ->and(NotificationChannel::REALTIME)->toBe('realtime')
                ->and(NotificationChannel::BROADCAST)->toBe('broadcast')
                ->and(NotificationChannel::IN_APP)->toBe('in_app');
        });
    });

    describe('all() method', function (): void {
        it('returns all valid notification channels', function (): void {
            $expected = [
                'database',
                'email',
                'sms',
                'push',
                'slack',
                'webhook',
                'realtime',
                'broadcast',
                'in_app',
            ];

            expect(NotificationChannel::all())->toBe($expected);
        });

        it('returns array with correct count', function (): void {
            expect(NotificationChannel::all())->toHaveCount(9);
        });
    });

    describe('isValid() method', function (): void {
        it('validates valid channels', function (): void {
            expect(NotificationChannel::isValid('database'))->toBeTrue()
                ->and(NotificationChannel::isValid('email'))->toBeTrue()
                ->and(NotificationChannel::isValid('sms'))->toBeTrue()
                ->and(NotificationChannel::isValid('push'))->toBeTrue()
                ->and(NotificationChannel::isValid('slack'))->toBeTrue()
                ->and(NotificationChannel::isValid('webhook'))->toBeTrue()
                ->and(NotificationChannel::isValid('realtime'))->toBeTrue()
                ->and(NotificationChannel::isValid('broadcast'))->toBeTrue()
                ->and(NotificationChannel::isValid('in_app'))->toBeTrue();
        });

        it('rejects invalid channels', function (): void {
            expect(NotificationChannel::isValid('invalid'))->toBeFalse()
                ->and(NotificationChannel::isValid('twitter'))->toBeFalse()
                ->and(NotificationChannel::isValid(''))->toBeFalse()
                ->and(NotificationChannel::isValid('DATABASE'))->toBeFalse(); // Case sensitive
        });
    });

    describe('realTimeChannels() method', function (): void {
        it('returns channels that support real-time delivery', function (): void {
            $expected = ['realtime', 'broadcast', 'in_app', 'push'];

            expect(NotificationChannel::realTimeChannels())->toBe($expected);
        });

        it('includes realtime, broadcast, in_app, and push', function (): void {
            $realTimeChannels = NotificationChannel::realTimeChannels();

            expect($realTimeChannels)->toContain('realtime')
                ->and($realTimeChannels)->toContain('broadcast')
                ->and($realTimeChannels)->toContain('in_app')
                ->and($realTimeChannels)->toContain('push');
        });

        it('does not include non-realtime channels', function (): void {
            $realTimeChannels = NotificationChannel::realTimeChannels();

            expect($realTimeChannels)->not->toContain('database')
                ->and($realTimeChannels)->not->toContain('email')
                ->and($realTimeChannels)->not->toContain('sms')
                ->and($realTimeChannels)->not->toContain('slack')
                ->and($realTimeChannels)->not->toContain('webhook');
        });
    });

    describe('persistentChannels() method', function (): void {
        it('returns channels that support persistent storage', function (): void {
            $expected = ['database', 'email', 'sms'];

            expect(NotificationChannel::persistentChannels())->toBe($expected);
        });

        it('includes database, email, and sms', function (): void {
            $persistentChannels = NotificationChannel::persistentChannels();

            expect($persistentChannels)->toContain('database')
                ->and($persistentChannels)->toContain('email')
                ->and($persistentChannels)->toContain('sms');
        });

        it('does not include non-persistent channels', function (): void {
            $persistentChannels = NotificationChannel::persistentChannels();

            expect($persistentChannels)->not->toContain('push')
                ->and($persistentChannels)->not->toContain('realtime')
                ->and($persistentChannels)->not->toContain('broadcast')
                ->and($persistentChannels)->not->toContain('in_app');
        });
    });

    describe('externalChannels() method', function (): void {
        it('returns channels that require external services', function (): void {
            $expected = ['email', 'sms', 'slack', 'webhook', 'push'];

            expect(NotificationChannel::externalChannels())->toBe($expected);
        });

        it('includes all external service channels', function (): void {
            $externalChannels = NotificationChannel::externalChannels();

            expect($externalChannels)->toContain('email')
                ->and($externalChannels)->toContain('sms')
                ->and($externalChannels)->toContain('slack')
                ->and($externalChannels)->toContain('webhook')
                ->and($externalChannels)->toContain('push');
        });

        it('does not include internal channels', function (): void {
            $externalChannels = NotificationChannel::externalChannels();

            expect($externalChannels)->not->toContain('database')
                ->and($externalChannels)->not->toContain('realtime')
                ->and($externalChannels)->not->toContain('broadcast')
                ->and($externalChannels)->not->toContain('in_app');
        });
    });

    describe('label() method', function (): void {
        it('returns human-readable labels for valid channels', function (): void {
            expect(NotificationChannel::label('database'))->toBe('Database')
                ->and(NotificationChannel::label('email'))->toBe('Email')
                ->and(NotificationChannel::label('sms'))->toBe('SMS')
                ->and(NotificationChannel::label('push'))->toBe('Push Notification')
                ->and(NotificationChannel::label('slack'))->toBe('Slack')
                ->and(NotificationChannel::label('webhook'))->toBe('Webhook')
                ->and(NotificationChannel::label('realtime'))->toBe('Real-time')
                ->and(NotificationChannel::label('broadcast'))->toBe('Broadcast')
                ->and(NotificationChannel::label('in_app'))->toBe('In-App');
        });

        it('returns Unknown for invalid channel', function (): void {
            expect(NotificationChannel::label('invalid'))->toBe('Unknown')
                ->and(NotificationChannel::label(''))->toBe('Unknown')
                ->and(NotificationChannel::label('DATABASE'))->toBe('Unknown');
        });

        it('uses proper capitalization', function (): void {
            foreach (NotificationChannel::all() as $channel) {
                $label = NotificationChannel::label($channel);
                expect($label)->toMatch('/^[A-Z]/');
            }
        });
    });

    describe('icon() method', function (): void {
        it('returns appropriate icons for each channel', function (): void {
            expect(NotificationChannel::icon('database'))->toBe('heroicon-o-circle-stack')
                ->and(NotificationChannel::icon('email'))->toBe('heroicon-o-envelope')
                ->and(NotificationChannel::icon('sms'))->toBe('heroicon-o-device-phone-mobile')
                ->and(NotificationChannel::icon('push'))->toBe('heroicon-o-bell')
                ->and(NotificationChannel::icon('slack'))->toBe('heroicon-o-chat-bubble-left-right')
                ->and(NotificationChannel::icon('webhook'))->toBe('heroicon-o-link')
                ->and(NotificationChannel::icon('realtime'))->toBe('heroicon-o-bolt')
                ->and(NotificationChannel::icon('broadcast'))->toBe('heroicon-o-radio')
                ->and(NotificationChannel::icon('in_app'))->toBe('heroicon-o-computer-desktop');
        });

        it('returns question mark icon for unknown channel', function (): void {
            expect(NotificationChannel::icon('invalid'))->toBe('heroicon-o-question-mark-circle')
                ->and(NotificationChannel::icon(''))->toBe('heroicon-o-question-mark-circle');
        });

        it('uses heroicon prefix for all icons', function (): void {
            foreach (NotificationChannel::all() as $channel) {
                $icon = NotificationChannel::icon($channel);
                expect($icon)->toStartWith('heroicon-o-');
            }
        });
    });

    describe('supportsImmediateDelivery() method', function (): void {
        it('identifies channels that support immediate delivery', function (): void {
            expect(NotificationChannel::supportsImmediateDelivery('realtime'))->toBeTrue()
                ->and(NotificationChannel::supportsImmediateDelivery('broadcast'))->toBeTrue()
                ->and(NotificationChannel::supportsImmediateDelivery('in_app'))->toBeTrue()
                ->and(NotificationChannel::supportsImmediateDelivery('database'))->toBeTrue();
        });

        it('identifies channels that do not support immediate delivery', function (): void {
            expect(NotificationChannel::supportsImmediateDelivery('email'))->toBeFalse()
                ->and(NotificationChannel::supportsImmediateDelivery('sms'))->toBeFalse()
                ->and(NotificationChannel::supportsImmediateDelivery('slack'))->toBeFalse()
                ->and(NotificationChannel::supportsImmediateDelivery('webhook'))->toBeFalse()
                ->and(NotificationChannel::supportsImmediateDelivery('push'))->toBeFalse();
        });

        it('returns false for invalid channels', function (): void {
            expect(NotificationChannel::supportsImmediateDelivery('invalid'))->toBeFalse()
                ->and(NotificationChannel::supportsImmediateDelivery(''))->toBeFalse();
        });
    });

    describe('supportsScheduledDelivery() method', function (): void {
        it('identifies channels that support scheduled delivery', function (): void {
            expect(NotificationChannel::supportsScheduledDelivery('email'))->toBeTrue()
                ->and(NotificationChannel::supportsScheduledDelivery('sms'))->toBeTrue()
                ->and(NotificationChannel::supportsScheduledDelivery('push'))->toBeTrue()
                ->and(NotificationChannel::supportsScheduledDelivery('slack'))->toBeTrue()
                ->and(NotificationChannel::supportsScheduledDelivery('webhook'))->toBeTrue();
        });

        it('identifies channels that do not support scheduled delivery', function (): void {
            expect(NotificationChannel::supportsScheduledDelivery('database'))->toBeFalse()
                ->and(NotificationChannel::supportsScheduledDelivery('realtime'))->toBeFalse()
                ->and(NotificationChannel::supportsScheduledDelivery('broadcast'))->toBeFalse()
                ->and(NotificationChannel::supportsScheduledDelivery('in_app'))->toBeFalse();
        });

        it('returns false for invalid channels', function (): void {
            expect(NotificationChannel::supportsScheduledDelivery('invalid'))->toBeFalse()
                ->and(NotificationChannel::supportsScheduledDelivery(''))->toBeFalse();
        });
    });

    describe('defaultForRole() method', function (): void {
        it('returns appropriate default channels for admin roles', function (): void {
            expect(NotificationChannel::defaultForRole('super_admin'))->toBe('realtime')
                ->and(NotificationChannel::defaultForRole('csr_admin'))->toBe('realtime')
                ->and(NotificationChannel::defaultForRole('finance_admin'))->toBe('realtime');
        });

        it('returns email for hr_manager role', function (): void {
            expect(NotificationChannel::defaultForRole('hr_manager'))->toBe('email');
        });

        it('returns database for employee role', function (): void {
            expect(NotificationChannel::defaultForRole('employee'))->toBe('database');
        });

        it('returns database for unknown roles', function (): void {
            expect(NotificationChannel::defaultForRole('unknown'))->toBe('database')
                ->and(NotificationChannel::defaultForRole(''))->toBe('database')
                ->and(NotificationChannel::defaultForRole('custom_role'))->toBe('database');
        });
    });

    describe('Channel Categorization', function (): void {
        it('has no overlap between realtime and persistent channels', function (): void {
            $realTimeChannels = NotificationChannel::realTimeChannels();
            $persistentChannels = NotificationChannel::persistentChannels();
            $overlap = array_intersect($realTimeChannels, $persistentChannels);

            expect($overlap)->toBeEmpty();
        });

        it('categorizes all channels appropriately', function (): void {
            $allChannels = NotificationChannel::all();
            $realTimeChannels = NotificationChannel::realTimeChannels();
            $persistentChannels = NotificationChannel::persistentChannels();
            $externalChannels = NotificationChannel::externalChannels();

            foreach ($allChannels as $channel) {
                // Every channel should belong to at least one category
                $belongsToCategory = in_array($channel, $realTimeChannels, true) ||
                                  in_array($channel, $persistentChannels, true) ||
                                  in_array($channel, $externalChannels, true);

                expect($belongsToCategory)->toBeTrue("Channel {$channel} should belong to at least one category");
            }
        });

        it('identifies channels that belong to multiple categories correctly', function (): void {
            // Push is both realtime and external
            expect(NotificationChannel::realTimeChannels())->toContain('push')
                ->and(NotificationChannel::externalChannels())->toContain('push');

            // Email is both persistent and external
            expect(NotificationChannel::persistentChannels())->toContain('email')
                ->and(NotificationChannel::externalChannels())->toContain('email');

            // SMS is both persistent and external
            expect(NotificationChannel::persistentChannels())->toContain('sms')
                ->and(NotificationChannel::externalChannels())->toContain('sms');
        });
    });

    describe('Delivery Support Logic', function (): void {
        it('ensures immediate and scheduled delivery categories make sense', function (): void {
            $immediateChannels = ['realtime', 'broadcast', 'in_app', 'database'];
            $scheduledChannels = ['email', 'sms', 'push', 'slack', 'webhook'];

            foreach ($immediateChannels as $channel) {
                expect(NotificationChannel::supportsImmediateDelivery($channel))->toBeTrue();
            }

            foreach ($scheduledChannels as $channel) {
                expect(NotificationChannel::supportsScheduledDelivery($channel))->toBeTrue();
            }
        });

        it('has push channel supporting both scheduled delivery and being realtime', function (): void {
            // Push is unique - it can be both immediate (realtime) and scheduled
            expect(NotificationChannel::realTimeChannels())->toContain('push')
                ->and(NotificationChannel::supportsScheduledDelivery('push'))->toBeTrue();
        });
    });
});

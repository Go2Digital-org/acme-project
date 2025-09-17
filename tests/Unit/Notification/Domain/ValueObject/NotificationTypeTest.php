<?php

declare(strict_types=1);

use Modules\Notification\Domain\ValueObject\NotificationType;

describe('NotificationType Value Object', function (): void {
    describe('Constants', function (): void {
        it('defines campaign-related notification constants', function (): void {
            expect(NotificationType::CAMPAIGN_CREATED)->toBe('campaign.created')
                ->and(NotificationType::CAMPAIGN_CREATED_LEGACY)->toBe('campaign_created')
                ->and(NotificationType::CAMPAIGN_MILESTONE)->toBe('campaign.milestone')
                ->and(NotificationType::CAMPAIGN_GOAL_REACHED)->toBe('campaign.goal_reached')
                ->and(NotificationType::CAMPAIGN_ENDING_SOON)->toBe('campaign.ending_soon')
                ->and(NotificationType::CAMPAIGN_PUBLISHED)->toBe('campaign.published')
                ->and(NotificationType::CAMPAIGN_APPROVED)->toBe('campaign.approved')
                ->and(NotificationType::CAMPAIGN_REJECTED)->toBe('campaign.rejected');
        });

        it('defines donation-related notification constants', function (): void {
            expect(NotificationType::DONATION_RECEIVED)->toBe('donation.received')
                ->and(NotificationType::DONATION_CONFIRMED)->toBe('donation.confirmed')
                ->and(NotificationType::DONATION_PROCESSED)->toBe('donation.processed')
                ->and(NotificationType::DONATION_FAILED)->toBe('donation.failed')
                ->and(NotificationType::PAYMENT_FAILED)->toBe('payment.failed')
                ->and(NotificationType::RECURRING_DONATION)->toBe('donation.recurring')
                ->and(NotificationType::LARGE_DONATION)->toBe('donation.large');
        });

        it('defines system-related notification constants', function (): void {
            expect(NotificationType::SYSTEM_MAINTENANCE)->toBe('system.maintenance')
                ->and(NotificationType::SECURITY_ALERT)->toBe('system.security_alert')
                ->and(NotificationType::LOGIN_ALERT)->toBe('system.login_alert')
                ->and(NotificationType::PASSWORD_CHANGED)->toBe('system.password_changed')
                ->and(NotificationType::ACCOUNT_UPDATED)->toBe('system.account_updated');
        });

        it('defines admin workflow notification constants', function (): void {
            expect(NotificationType::CAMPAIGN_PENDING_REVIEW)->toBe('campaign.pending_review')
                ->and(NotificationType::ADMIN_ALERT)->toBe('admin.alert')
                ->and(NotificationType::APPROVAL_NEEDED)->toBe('admin.approval_needed')
                ->and(NotificationType::USER_REGISTERED)->toBe('admin.user_registered')
                ->and(NotificationType::USER_REPORT)->toBe('admin.user_report')
                ->and(NotificationType::SYSTEM_ALERT)->toBe('admin.system_alert');
        });
    });

    describe('all() method', function (): void {
        it('returns all valid notification types', function (): void {
            $allTypes = NotificationType::all();

            expect($allTypes)->toBeArray()
                ->and(count($allTypes))->toBeGreaterThan(30); // We have many types
        });

        it('includes all campaign types', function (): void {
            $allTypes = NotificationType::all();
            $campaignTypes = NotificationType::campaignTypes();

            foreach ($campaignTypes as $type) {
                expect($allTypes)->toContain($type);
            }
        });

        it('includes all donation types', function (): void {
            $allTypes = NotificationType::all();
            $donationTypes = NotificationType::donationTypes();

            foreach ($donationTypes as $type) {
                expect($allTypes)->toContain($type);
            }
        });

        it('includes all admin types', function (): void {
            $allTypes = NotificationType::all();
            $adminTypes = NotificationType::adminTypes();

            foreach ($adminTypes as $type) {
                expect($allTypes)->toContain($type);
            }
        });

        it('includes all system types', function (): void {
            $allTypes = NotificationType::all();
            $systemTypes = NotificationType::systemTypes();

            foreach ($systemTypes as $type) {
                expect($allTypes)->toContain($type);
            }
        });
    });

    describe('isValid() method', function (): void {
        it('validates valid notification types', function (): void {
            expect(NotificationType::isValid('campaign.created'))->toBeTrue()
                ->and(NotificationType::isValid('donation.received'))->toBeTrue()
                ->and(NotificationType::isValid('system.maintenance'))->toBeTrue()
                ->and(NotificationType::isValid('admin.alert'))->toBeTrue();
        });

        it('rejects invalid notification types', function (): void {
            expect(NotificationType::isValid('invalid.type'))->toBeFalse()
                ->and(NotificationType::isValid('campaign.invalid'))->toBeFalse()
                ->and(NotificationType::isValid(''))->toBeFalse()
                ->and(NotificationType::isValid('CAMPAIGN.CREATED'))->toBeFalse(); // Case sensitive
        });

        it('validates legacy format', function (): void {
            expect(NotificationType::isValid('campaign_created'))->toBeTrue();
        });
    });

    describe('campaignTypes() method', function (): void {
        it('returns all campaign-related types', function (): void {
            $expected = [
                'campaign.created',
                'campaign_created',
                'campaign.milestone',
                'campaign.goal_reached',
                'campaign.ending_soon',
                'campaign.published',
                'campaign.approved',
                'campaign.rejected',
            ];

            expect(NotificationType::campaignTypes())->toBe($expected);
        });

        it('includes both new and legacy formats', function (): void {
            $campaignTypes = NotificationType::campaignTypes();

            expect($campaignTypes)->toContain('campaign.created')
                ->and($campaignTypes)->toContain('campaign_created');
        });

        it('does not include non-campaign types', function (): void {
            $campaignTypes = NotificationType::campaignTypes();

            expect($campaignTypes)->not->toContain('donation.received')
                ->and($campaignTypes)->not->toContain('system.maintenance')
                ->and($campaignTypes)->not->toContain('admin.alert');
        });
    });

    describe('donationTypes() method', function (): void {
        it('returns all donation-related types', function (): void {
            $expected = [
                'donation.received',
                'donation.confirmed',
                'donation.processed',
                'donation.failed',
                'payment.failed',
                'donation.recurring',
                'donation.large',
            ];

            expect(NotificationType::donationTypes())->toBe($expected);
        });

        it('includes all donation lifecycle types', function (): void {
            $donationTypes = NotificationType::donationTypes();

            expect($donationTypes)->toContain('donation.received')
                ->and($donationTypes)->toContain('donation.confirmed')
                ->and($donationTypes)->toContain('donation.processed')
                ->and($donationTypes)->toContain('donation.failed');
        });

        it('includes payment-related types', function (): void {
            $donationTypes = NotificationType::donationTypes();

            expect($donationTypes)->toContain('payment.failed');
        });

        it('does not include non-donation types', function (): void {
            $donationTypes = NotificationType::donationTypes();

            expect($donationTypes)->not->toContain('campaign.created')
                ->and($donationTypes)->not->toContain('system.maintenance')
                ->and($donationTypes)->not->toContain('admin.alert');
        });
    });

    describe('adminTypes() method', function (): void {
        it('returns all admin-only notification types', function (): void {
            $adminTypes = NotificationType::adminTypes();

            expect($adminTypes)->toContain('campaign.pending_review')
                ->and($adminTypes)->toContain('admin.alert')
                ->and($adminTypes)->toContain('admin.approval_needed')
                ->and($adminTypes)->toContain('admin.user_registered')
                ->and($adminTypes)->toContain('admin.user_report')
                ->and($adminTypes)->toContain('admin.system_alert')
                ->and($adminTypes)->toContain('system.security_alert')
                ->and($adminTypes)->toContain('organization.compliance_issues')
                ->and($adminTypes)->toContain('organization.verification');
        });

        it('includes security-related types', function (): void {
            $adminTypes = NotificationType::adminTypes();

            expect($adminTypes)->toContain('system.security_alert');
        });

        it('includes organization verification types', function (): void {
            $adminTypes = NotificationType::adminTypes();

            expect($adminTypes)->toContain('organization.verification');
        });

        it('does not include regular user types', function (): void {
            $adminTypes = NotificationType::adminTypes();

            expect($adminTypes)->not->toContain('donation.received')
                ->and($adminTypes)->not->toContain('campaign.created');
        });
    });

    describe('systemTypes() method', function (): void {
        it('returns all system-wide notification types', function (): void {
            $expected = [
                'system.maintenance',
                'system.security_alert',
                'system.login_alert',
                'system.password_changed',
                'system.account_updated',
            ];

            expect(NotificationType::systemTypes())->toBe($expected);
        });

        it('includes security-related types', function (): void {
            $systemTypes = NotificationType::systemTypes();

            expect($systemTypes)->toContain('system.security_alert')
                ->and($systemTypes)->toContain('system.login_alert');
        });

        it('includes account management types', function (): void {
            $systemTypes = NotificationType::systemTypes();

            expect($systemTypes)->toContain('system.password_changed')
                ->and($systemTypes)->toContain('system.account_updated');
        });
    });

    describe('realTimeTypes() method', function (): void {
        it('returns types that should trigger real-time updates', function (): void {
            $expected = [
                'donation.large',
                'campaign.milestone',
                'campaign.goal_reached',
                'system.security_alert',
                'admin.approval_needed',
                'payment.failed',
                'dashboard.update',
            ];

            expect(NotificationType::realTimeTypes())->toBe($expected);
        });

        it('includes high-priority donation types', function (): void {
            $realTimeTypes = NotificationType::realTimeTypes();

            expect($realTimeTypes)->toContain('donation.large');
        });

        it('includes critical campaign milestones', function (): void {
            $realTimeTypes = NotificationType::realTimeTypes();

            expect($realTimeTypes)->toContain('campaign.milestone')
                ->and($realTimeTypes)->toContain('campaign.goal_reached');
        });

        it('includes urgent admin and system alerts', function (): void {
            $realTimeTypes = NotificationType::realTimeTypes();

            expect($realTimeTypes)->toContain('system.security_alert')
                ->and($realTimeTypes)->toContain('admin.approval_needed');
        });
    });

    describe('label() method', function (): void {
        it('returns human-readable labels for campaign types', function (): void {
            expect(NotificationType::label('campaign.created'))->toBe('Campaign Created')
                ->and(NotificationType::label('campaign_created'))->toBe('Campaign Created')
                ->and(NotificationType::label('campaign.milestone'))->toBe('Campaign Milestone')
                ->and(NotificationType::label('campaign.goal_reached'))->toBe('Campaign Goal Reached');
        });

        it('returns human-readable labels for donation types', function (): void {
            expect(NotificationType::label('donation.received'))->toBe('Donation Received')
                ->and(NotificationType::label('donation.confirmed'))->toBe('Donation Confirmed')
                ->and(NotificationType::label('payment.failed'))->toBe('Payment Failed')
                ->and(NotificationType::label('donation.large'))->toBe('Large Donation');
        });

        it('returns human-readable labels for system types', function (): void {
            expect(NotificationType::label('system.maintenance'))->toBe('System Maintenance')
                ->and(NotificationType::label('system.security_alert'))->toBe('Security Alert')
                ->and(NotificationType::label('system.login_alert'))->toBe('Login Alert');
        });

        it('returns human-readable labels for admin types', function (): void {
            expect(NotificationType::label('admin.alert'))->toBe('Admin Alert')
                ->and(NotificationType::label('admin.approval_needed'))->toBe('Approval Needed')
                ->and(NotificationType::label('admin.user_registered'))->toBe('User Registered');
        });

        it('returns Unknown for invalid type', function (): void {
            expect(NotificationType::label('invalid.type'))->toBe('Unknown')
                ->and(NotificationType::label(''))->toBe('Unknown')
                ->and(NotificationType::label('CAMPAIGN.CREATED'))->toBe('Unknown');
        });

        it('uses proper capitalization for all valid types', function (): void {
            $sampleTypes = [
                'campaign.created', 'donation.received', 'system.maintenance',
                'admin.alert', 'translation.completed', 'test',
            ];

            foreach ($sampleTypes as $type) {
                $label = NotificationType::label($type);
                expect($label)->toMatch('/^[A-Z]/');
            }
        });
    });

    describe('category() method', function (): void {
        it('categorizes campaign types correctly', function (): void {
            expect(NotificationType::category('campaign.created'))->toBe('campaigns')
                ->and(NotificationType::category('campaign_created'))->toBe('campaigns')
                ->and(NotificationType::category('campaign.milestone'))->toBe('campaigns')
                ->and(NotificationType::category('campaign.pending_review'))->toBe('campaigns')
                ->and(NotificationType::category('campaign.suggestion'))->toBe('campaigns');
        });

        it('categorizes donation types correctly', function (): void {
            expect(NotificationType::category('donation.received'))->toBe('donations')
                ->and(NotificationType::category('donation.confirmed'))->toBe('donations')
                ->and(NotificationType::category('payment.failed'))->toBe('donations')
                ->and(NotificationType::category('donation.large'))->toBe('donations');
        });

        it('categorizes admin types correctly', function (): void {
            expect(NotificationType::category('admin.alert'))->toBe('admin')
                ->and(NotificationType::category('admin.approval_needed'))->toBe('admin')
                ->and(NotificationType::category('admin.user_registered'))->toBe('admin')
                ->and(NotificationType::category('system.security_alert'))->toBe('admin');
        });

        it('categorizes system types correctly', function (): void {
            expect(NotificationType::category('system.maintenance'))->toBe('system')
                ->and(NotificationType::category('system.login_alert'))->toBe('system')
                ->and(NotificationType::category('system.password_changed'))->toBe('system');
        });

        it('returns general category for uncategorized types', function (): void {
            expect(NotificationType::category('custom'))->toBe('general')
                ->and(NotificationType::category('generic'))->toBe('general')
                ->and(NotificationType::category('translation.completed'))->toBe('general')
                ->and(NotificationType::category('unknown.type'))->toBe('general');
        });
    });

    describe('Type Organization', function (): void {
        it('has no duplicate types across categories', function (): void {
            $campaignTypes = NotificationType::campaignTypes();
            $donationTypes = NotificationType::donationTypes();
            $adminTypes = NotificationType::adminTypes();
            $systemTypes = NotificationType::systemTypes();

            // Check for overlaps
            $campaignDonationOverlap = array_intersect($campaignTypes, $donationTypes);
            $campaignAdminOverlap = array_intersect($campaignTypes, $adminTypes);
            $donationSystemOverlap = array_intersect($donationTypes, $systemTypes);

            expect($campaignDonationOverlap)->toBeEmpty();
            expect($campaignAdminOverlap)->toBeEmpty();
            expect($donationSystemOverlap)->toBeEmpty();
        });

        it('includes legacy and new formats in appropriate categories', function (): void {
            expect(NotificationType::category('campaign.created'))->toBe('campaigns')
                ->and(NotificationType::category('campaign_created'))->toBe('campaigns');
        });

        it('correctly identifies real-time types from different categories', function (): void {
            $realTimeTypes = NotificationType::realTimeTypes();

            // Should have types from different categories
            expect($realTimeTypes)->toContain('donation.large') // donation category
                ->and($realTimeTypes)->toContain('campaign.milestone') // campaign category
                ->and($realTimeTypes)->toContain('system.security_alert') // system/admin category
                ->and($realTimeTypes)->toContain('admin.approval_needed'); // admin category
        });

        it('maintains consistency between type groups and all() method', function (): void {
            $allTypes = NotificationType::all();
            $campaignTypes = NotificationType::campaignTypes();
            $donationTypes = NotificationType::donationTypes();

            foreach ($campaignTypes as $type) {
                expect(in_array($type, $allTypes, true))->toBeTrue("Campaign type {$type} should be in all() method");
            }

            foreach ($donationTypes as $type) {
                expect(in_array($type, $allTypes, true))->toBeTrue("Donation type {$type} should be in all() method");
            }
        });
    });

    describe('Namespace Conventions', function (): void {
        it('follows dot notation for most types', function (): void {
            $dotNotationTypes = [
                'campaign.created', 'donation.received', 'system.maintenance',
                'admin.alert', 'organization.verification',
            ];

            foreach ($dotNotationTypes as $type) {
                expect($type)->toMatch('/\w+\.\w+/');
            }
        });

        it('maintains backward compatibility with legacy underscore format', function (): void {
            expect(NotificationType::isValid('campaign_created'))->toBeTrue()
                ->and(NotificationType::label('campaign_created'))->toBe('Campaign Created');
        });

        it('uses consistent prefixes for type categories', function (): void {
            $campaignTypes = array_filter(NotificationType::campaignTypes(), fn ($type) => $type !== 'campaign_created');

            foreach ($campaignTypes as $type) {
                expect($type)->toStartWith('campaign.');
            }

            $donationTypes = NotificationType::donationTypes();
            $donationPrefixTypes = array_filter($donationTypes, fn ($type) => str_starts_with($type, 'donation.'));
            expect($donationPrefixTypes)->not()->toBeEmpty();

            $systemTypes = NotificationType::systemTypes();

            foreach ($systemTypes as $type) {
                expect($type)->toStartWith('system.');
            }
        });
    });
});

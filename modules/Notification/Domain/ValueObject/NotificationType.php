<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

/**
 * Value object representing notification types in the ACME Corp CSR platform.
 *
 * Defines all possible notification types that can be sent throughout the system.
 */
class NotificationType
{
    // Campaign-related notifications
    public const CAMPAIGN_CREATED = 'campaign.created';

    public const CAMPAIGN_CREATED_LEGACY = 'campaign_created'; // Legacy format for backward compatibility

    public const CAMPAIGN_MILESTONE = 'campaign.milestone';

    public const CAMPAIGN_GOAL_REACHED = 'campaign.goal_reached';

    public const CAMPAIGN_ENDING_SOON = 'campaign.ending_soon';

    public const CAMPAIGN_PUBLISHED = 'campaign.published';

    public const CAMPAIGN_APPROVED = 'campaign.approved';

    public const CAMPAIGN_REJECTED = 'campaign.rejected';

    // Donation-related notifications
    public const DONATION_RECEIVED = 'donation.received';

    public const DONATION_CONFIRMED = 'donation.confirmed';

    public const DONATION_PROCESSED = 'donation.processed';

    public const DONATION_FAILED = 'donation.failed';

    public const PAYMENT_FAILED = 'payment.failed';

    public const RECURRING_DONATION = 'donation.recurring';

    public const LARGE_DONATION = 'donation.large';

    // Organization-related notifications
    public const ORGANIZATION_VERIFICATION = 'organization.verification';

    public const ORGANIZATION_APPROVED = 'organization.approved';

    public const ORGANIZATION_REJECTED = 'organization.rejected';

    public const COMPLIANCE_ISSUES = 'organization.compliance_issues';

    // System notifications
    public const SYSTEM_MAINTENANCE = 'system.maintenance';

    public const SECURITY_ALERT = 'system.security_alert';

    public const LOGIN_ALERT = 'system.login_alert';

    public const PASSWORD_CHANGED = 'system.password_changed';

    public const ACCOUNT_UPDATED = 'system.account_updated';

    // Admin workflow notifications
    public const CAMPAIGN_PENDING_REVIEW = 'campaign.pending_review';

    public const CAMPAIGN_SUGGESTION = 'campaign.suggestion';

    public const ADMIN_ALERT = 'admin.alert';

    public const APPROVAL_NEEDED = 'admin.approval_needed';

    public const USER_REGISTERED = 'admin.user_registered';

    public const USER_REPORT = 'admin.user_report';

    public const SYSTEM_ALERT = 'admin.system_alert';

    // Translation and localization
    public const TRANSLATION_COMPLETED = 'translation.completed';

    public const TRANSLATION_REQUESTED = 'translation.requested';

    // Dashboard and analytics
    public const DASHBOARD_UPDATE = 'dashboard.update';

    public const METRIC_THRESHOLD = 'analytics.metric_threshold';

    // Custom notifications
    public const CUSTOM = 'custom';

    public const GENERIC = 'generic';

    // Test and development notifications
    public const TEST = 'test';

    public const MINIMAL = 'minimal';

    public const TEST_TYPE = 'test_type';

    public const MASS_NOTIFICATION = 'mass_notification';

    /**
     * Get all notification types.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            // Campaign types
            self::CAMPAIGN_CREATED,
            self::CAMPAIGN_CREATED_LEGACY,
            self::CAMPAIGN_MILESTONE,
            self::CAMPAIGN_GOAL_REACHED,
            self::CAMPAIGN_ENDING_SOON,
            self::CAMPAIGN_PUBLISHED,
            self::CAMPAIGN_APPROVED,
            self::CAMPAIGN_REJECTED,

            // Donation types
            self::DONATION_RECEIVED,
            self::DONATION_CONFIRMED,
            self::DONATION_PROCESSED,
            self::DONATION_FAILED,
            self::PAYMENT_FAILED,
            self::RECURRING_DONATION,
            self::LARGE_DONATION,

            // Organization types
            self::ORGANIZATION_VERIFICATION,
            self::ORGANIZATION_APPROVED,
            self::ORGANIZATION_REJECTED,
            self::COMPLIANCE_ISSUES,

            // System types
            self::SYSTEM_MAINTENANCE,
            self::SECURITY_ALERT,
            self::LOGIN_ALERT,
            self::PASSWORD_CHANGED,
            self::ACCOUNT_UPDATED,

            // Admin types
            self::CAMPAIGN_PENDING_REVIEW,
            self::CAMPAIGN_SUGGESTION,
            self::ADMIN_ALERT,
            self::APPROVAL_NEEDED,
            self::USER_REGISTERED,
            self::USER_REPORT,
            self::SYSTEM_ALERT,

            // Translation types
            self::TRANSLATION_COMPLETED,
            self::TRANSLATION_REQUESTED,

            // Analytics types
            self::DASHBOARD_UPDATE,
            self::METRIC_THRESHOLD,

            // Generic types
            self::CUSTOM,
            self::GENERIC,

            // Test types
            self::TEST,
            self::MINIMAL,
            self::TEST_TYPE,
            self::MASS_NOTIFICATION,
        ];
    }

    /**
     * Check if a notification type is valid.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    /**
     * Get campaign-related notification types.
     *
     * @return array<int, string>
     */
    public static function campaignTypes(): array
    {
        return [
            self::CAMPAIGN_CREATED,
            self::CAMPAIGN_CREATED_LEGACY,
            self::CAMPAIGN_MILESTONE,
            self::CAMPAIGN_GOAL_REACHED,
            self::CAMPAIGN_ENDING_SOON,
            self::CAMPAIGN_PUBLISHED,
            self::CAMPAIGN_APPROVED,
            self::CAMPAIGN_REJECTED,
        ];
    }

    /**
     * Get donation-related notification types.
     *
     * @return array<int, string>
     */
    public static function donationTypes(): array
    {
        return [
            self::DONATION_RECEIVED,
            self::DONATION_CONFIRMED,
            self::DONATION_PROCESSED,
            self::DONATION_FAILED,
            self::PAYMENT_FAILED,
            self::RECURRING_DONATION,
            self::LARGE_DONATION,
        ];
    }

    /**
     * Get admin-only notification types.
     *
     * @return array<int, string>
     */
    public static function adminTypes(): array
    {
        return [
            self::CAMPAIGN_PENDING_REVIEW,
            self::ADMIN_ALERT,
            self::APPROVAL_NEEDED,
            self::USER_REGISTERED,
            self::USER_REPORT,
            self::SYSTEM_ALERT,
            self::SECURITY_ALERT,
            self::COMPLIANCE_ISSUES,
            self::ORGANIZATION_VERIFICATION,
        ];
    }

    /**
     * Get system-wide notification types.
     *
     * @return array<int, string>
     */
    public static function systemTypes(): array
    {
        return [
            self::SYSTEM_MAINTENANCE,
            self::SECURITY_ALERT,
            self::LOGIN_ALERT,
            self::PASSWORD_CHANGED,
            self::ACCOUNT_UPDATED,
        ];
    }

    /**
     * Get notification types that should trigger real-time updates.
     *
     * @return array<int, string>
     */
    public static function realTimeTypes(): array
    {
        return [
            self::LARGE_DONATION,
            self::CAMPAIGN_MILESTONE,
            self::CAMPAIGN_GOAL_REACHED,
            self::SECURITY_ALERT,
            self::APPROVAL_NEEDED,
            self::PAYMENT_FAILED,
            self::DASHBOARD_UPDATE,
        ];
    }

    /**
     * Get human-readable label for notification type.
     */
    public static function label(string $type): string
    {
        return match ($type) {
            self::CAMPAIGN_CREATED => 'Campaign Created',
            self::CAMPAIGN_CREATED_LEGACY => 'Campaign Created',
            self::CAMPAIGN_MILESTONE => 'Campaign Milestone',
            self::CAMPAIGN_GOAL_REACHED => 'Campaign Goal Reached',
            self::CAMPAIGN_ENDING_SOON => 'Campaign Ending Soon',
            self::CAMPAIGN_PUBLISHED => 'Campaign Published',
            self::CAMPAIGN_APPROVED => 'Campaign Approved',
            self::CAMPAIGN_REJECTED => 'Campaign Rejected',
            self::CAMPAIGN_PENDING_REVIEW => 'Campaign Pending Review',
            self::CAMPAIGN_SUGGESTION => 'Campaign Suggestion',
            self::ADMIN_ALERT => 'Admin Alert',
            self::DONATION_RECEIVED => 'Donation Received',
            self::DONATION_CONFIRMED => 'Donation Confirmed',
            self::DONATION_PROCESSED => 'Donation Processed',
            self::DONATION_FAILED => 'Donation Failed',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::RECURRING_DONATION => 'Recurring Donation',
            self::LARGE_DONATION => 'Large Donation',
            self::ORGANIZATION_VERIFICATION => 'Organization Verification',
            self::ORGANIZATION_APPROVED => 'Organization Approved',
            self::ORGANIZATION_REJECTED => 'Organization Rejected',
            self::COMPLIANCE_ISSUES => 'Compliance Issues',
            self::SYSTEM_MAINTENANCE => 'System Maintenance',
            self::SECURITY_ALERT => 'Security Alert',
            self::LOGIN_ALERT => 'Login Alert',
            self::PASSWORD_CHANGED => 'Password Changed',
            self::ACCOUNT_UPDATED => 'Account Updated',
            self::APPROVAL_NEEDED => 'Approval Needed',
            self::USER_REGISTERED => 'User Registered',
            self::USER_REPORT => 'User Report',
            self::SYSTEM_ALERT => 'System Alert',
            self::TRANSLATION_COMPLETED => 'Translation Completed',
            self::TRANSLATION_REQUESTED => 'Translation Requested',
            self::DASHBOARD_UPDATE => 'Dashboard Update',
            self::METRIC_THRESHOLD => 'Metric Threshold',
            self::CUSTOM => 'Custom Notification',
            self::GENERIC => 'Notification',
            self::TEST => 'Test Notification',
            self::MINIMAL => 'Minimal Notification',
            self::TEST_TYPE => 'Test Type',
            self::MASS_NOTIFICATION => 'Mass Notification',
            default => 'Unknown',
        };
    }

    /**
     * Get the category for a notification type.
     */
    public static function category(string $type): string
    {
        if (in_array($type, self::campaignTypes(), true) ||
            in_array($type, [self::CAMPAIGN_PENDING_REVIEW, self::CAMPAIGN_SUGGESTION], true)) {
            return 'campaigns';
        }

        if (in_array($type, self::donationTypes(), true)) {
            return 'donations';
        }

        if (in_array($type, self::adminTypes(), true)) {
            return 'admin';
        }

        if (in_array($type, self::systemTypes(), true)) {
            return 'system';
        }

        return 'general';
    }
}

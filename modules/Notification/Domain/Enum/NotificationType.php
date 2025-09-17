<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

/**
 * Enum representing notification types in the ACME Corp CSR platform.
 *
 * Defines all possible notification types that can be sent throughout the system.
 */
enum NotificationType: string
{
    // Campaign-related notifications
    case CAMPAIGN_CREATED = 'campaign.created';
    case CAMPAIGN_MILESTONE = 'campaign.milestone';
    case CAMPAIGN_GOAL_REACHED = 'campaign.goal_reached';
    case CAMPAIGN_ENDING_SOON = 'campaign.ending_soon';
    case CAMPAIGN_PUBLISHED = 'campaign.published';
    case CAMPAIGN_APPROVED = 'campaign.approved';
    case CAMPAIGN_REJECTED = 'campaign.rejected';
    case CAMPAIGN_ACTIVATED = 'campaign.activated';
    case CAMPAIGN_AVAILABLE = 'campaign.available';
    case CAMPAIGN_SUCCESS = 'campaign.success';
    case CAMPAIGN_COMPLETED = 'campaign.completed';

    // Donation-related notifications
    case DONATION_RECEIVED = 'donation.received';
    case DONATION_CONFIRMED = 'donation.confirmed';
    case DONATION_CONFIRMATION = 'donation.confirmation';
    case DONATION_PROCESSED = 'donation.processed';
    case DONATION_FAILED = 'donation.failed';
    case PAYMENT_FAILED = 'payment.failed';
    case RECURRING_DONATION = 'donation.recurring';
    case LARGE_DONATION = 'donation.large';

    // Organization-related notifications
    case ORGANIZATION_VERIFIED = 'organization.verified';
    case ORGANIZATION_VERIFICATION = 'organization.verification';
    case ORGANIZATION_APPROVED = 'organization.approved';
    case ORGANIZATION_REJECTED = 'organization.rejected';
    case COMPLIANCE_ISSUES = 'organization.compliance_issues';

    // System notifications
    case SYSTEM = 'system';
    case SYSTEM_MAINTENANCE = 'system.maintenance';
    case SECURITY_ALERT = 'system.security_alert';
    case LOGIN_ALERT = 'system.login_alert';
    case PASSWORD_CHANGED = 'system.password_changed';
    case ACCOUNT_UPDATED = 'system.account_updated';
    case SYSTEM_ALERT = 'system.alert';

    // Admin workflow notifications
    case CAMPAIGN_PENDING_REVIEW = 'campaign.pending_review';
    case CAMPAIGN_SUGGESTION = 'campaign.suggestion';
    case ADMIN_ALERT = 'admin.alert';
    case APPROVAL_NEEDED = 'admin.approval_needed';
    case USER_REGISTERED = 'admin.user_registered';
    case USER_REPORT = 'admin.user_report';

    // Translation and localization
    case TRANSLATION_COMPLETED = 'translation.completed';
    case TRANSLATION_REQUESTED = 'translation.requested';

    // Dashboard and analytics
    case DASHBOARD_UPDATE = 'dashboard.update';
    case METRIC_THRESHOLD = 'analytics.metric_threshold';

    // Generic notifications
    case CUSTOM = 'custom';
    case GENERIC = 'generic';
    case TEST = 'test';

    /**
     * Get the string value of the enum case.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get human-readable label for the notification type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CAMPAIGN_CREATED => 'Campaign Created',
            self::CAMPAIGN_MILESTONE => 'Campaign Milestone',
            self::CAMPAIGN_GOAL_REACHED => 'Campaign Goal Reached',
            self::CAMPAIGN_ENDING_SOON => 'Campaign Ending Soon',
            self::CAMPAIGN_PUBLISHED => 'Campaign Published',
            self::CAMPAIGN_APPROVED => 'Campaign Approved',
            self::CAMPAIGN_REJECTED => 'Campaign Rejected',
            self::CAMPAIGN_ACTIVATED => 'Campaign Activated',
            self::CAMPAIGN_AVAILABLE => 'Campaign Available',
            self::CAMPAIGN_SUCCESS => 'Campaign Success',
            self::CAMPAIGN_COMPLETED => 'Campaign Completed',
            self::CAMPAIGN_PENDING_REVIEW => 'Campaign Pending Review',
            self::CAMPAIGN_SUGGESTION => 'Campaign Suggestion',
            self::DONATION_RECEIVED => 'Donation Received',
            self::DONATION_CONFIRMED => 'Donation Confirmed',
            self::DONATION_CONFIRMATION => 'Donation Confirmation',
            self::DONATION_PROCESSED => 'Donation Processed',
            self::DONATION_FAILED => 'Donation Failed',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::RECURRING_DONATION => 'Recurring Donation',
            self::LARGE_DONATION => 'Large Donation',
            self::ORGANIZATION_VERIFIED => 'Organization Verified',
            self::ORGANIZATION_VERIFICATION => 'Organization Verification',
            self::ORGANIZATION_APPROVED => 'Organization Approved',
            self::ORGANIZATION_REJECTED => 'Organization Rejected',
            self::COMPLIANCE_ISSUES => 'Compliance Issues',
            self::SYSTEM => 'System Notification',
            self::SYSTEM_MAINTENANCE => 'System Maintenance',
            self::SECURITY_ALERT => 'Security Alert',
            self::LOGIN_ALERT => 'Login Alert',
            self::PASSWORD_CHANGED => 'Password Changed',
            self::ACCOUNT_UPDATED => 'Account Updated',
            self::SYSTEM_ALERT => 'System Alert',
            self::ADMIN_ALERT => 'Admin Alert',
            self::APPROVAL_NEEDED => 'Approval Needed',
            self::USER_REGISTERED => 'User Registered',
            self::USER_REPORT => 'User Report',
            self::TRANSLATION_COMPLETED => 'Translation Completed',
            self::TRANSLATION_REQUESTED => 'Translation Requested',
            self::DASHBOARD_UPDATE => 'Dashboard Update',
            self::METRIC_THRESHOLD => 'Metric Threshold',
            self::CUSTOM => 'Custom Notification',
            self::GENERIC => 'Notification',
            self::TEST => 'Test Notification',
        };
    }

    /**
     * Get the category for a notification type.
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::CAMPAIGN_CREATED,
            self::CAMPAIGN_MILESTONE,
            self::CAMPAIGN_GOAL_REACHED,
            self::CAMPAIGN_ENDING_SOON,
            self::CAMPAIGN_PUBLISHED,
            self::CAMPAIGN_APPROVED,
            self::CAMPAIGN_REJECTED,
            self::CAMPAIGN_ACTIVATED,
            self::CAMPAIGN_AVAILABLE,
            self::CAMPAIGN_SUCCESS,
            self::CAMPAIGN_COMPLETED,
            self::CAMPAIGN_PENDING_REVIEW,
            self::CAMPAIGN_SUGGESTION => 'campaigns',

            self::DONATION_RECEIVED,
            self::DONATION_CONFIRMED,
            self::DONATION_CONFIRMATION,
            self::DONATION_PROCESSED,
            self::DONATION_FAILED,
            self::PAYMENT_FAILED,
            self::RECURRING_DONATION,
            self::LARGE_DONATION => 'donations',

            self::ORGANIZATION_VERIFIED,
            self::ORGANIZATION_VERIFICATION,
            self::ORGANIZATION_APPROVED,
            self::ORGANIZATION_REJECTED,
            self::COMPLIANCE_ISSUES => 'organizations',

            self::SYSTEM,
            self::SYSTEM_MAINTENANCE,
            self::SECURITY_ALERT,
            self::LOGIN_ALERT,
            self::PASSWORD_CHANGED,
            self::ACCOUNT_UPDATED,
            self::SYSTEM_ALERT => 'system',

            self::ADMIN_ALERT,
            self::APPROVAL_NEEDED,
            self::USER_REGISTERED,
            self::USER_REPORT => 'admin',

            self::TRANSLATION_COMPLETED,
            self::TRANSLATION_REQUESTED => 'translations',

            self::DASHBOARD_UPDATE,
            self::METRIC_THRESHOLD => 'analytics',

            default => 'general',
        };
    }

    /**
     * Get icon for the notification type.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::CAMPAIGN_CREATED => 'heroicon-o-megaphone',
            self::CAMPAIGN_MILESTONE => 'heroicon-o-trophy',
            self::CAMPAIGN_GOAL_REACHED => 'heroicon-o-flag',
            self::CAMPAIGN_ENDING_SOON => 'heroicon-o-clock',
            self::CAMPAIGN_PUBLISHED => 'heroicon-o-eye',
            self::CAMPAIGN_APPROVED => 'heroicon-o-check-circle',
            self::CAMPAIGN_REJECTED => 'heroicon-o-x-circle',
            self::CAMPAIGN_ACTIVATED => 'heroicon-o-rocket-launch',
            self::CAMPAIGN_AVAILABLE => 'heroicon-o-megaphone',
            self::CAMPAIGN_SUCCESS => 'heroicon-o-star',
            self::CAMPAIGN_COMPLETED => 'heroicon-o-check-badge',
            self::CAMPAIGN_PENDING_REVIEW => 'heroicon-o-exclamation-triangle',
            self::CAMPAIGN_SUGGESTION => 'heroicon-o-light-bulb',
            self::DONATION_RECEIVED => 'heroicon-o-banknotes',
            self::DONATION_CONFIRMED => 'heroicon-o-check-circle',
            self::DONATION_CONFIRMATION => 'heroicon-o-envelope-open',
            self::DONATION_PROCESSED => 'heroicon-o-cog-6-tooth',
            self::DONATION_FAILED => 'heroicon-o-x-circle',
            self::PAYMENT_FAILED => 'heroicon-o-credit-card',
            self::RECURRING_DONATION => 'heroicon-o-arrow-path',
            self::LARGE_DONATION => 'heroicon-o-currency-dollar',
            self::ORGANIZATION_VERIFIED => 'heroicon-o-shield-check',
            self::ORGANIZATION_VERIFICATION => 'heroicon-o-building-office-2',
            self::ORGANIZATION_APPROVED => 'heroicon-o-check-badge',
            self::ORGANIZATION_REJECTED => 'heroicon-o-x-mark',
            self::COMPLIANCE_ISSUES => 'heroicon-o-exclamation-triangle',
            self::SYSTEM => 'heroicon-o-cog-6-tooth',
            self::SYSTEM_MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::SECURITY_ALERT => 'heroicon-o-shield-exclamation',
            self::LOGIN_ALERT => 'heroicon-o-user',
            self::PASSWORD_CHANGED => 'heroicon-o-key',
            self::ACCOUNT_UPDATED => 'heroicon-o-user-circle',
            self::SYSTEM_ALERT => 'heroicon-o-bell-alert',
            self::ADMIN_ALERT => 'heroicon-o-exclamation-circle',
            self::APPROVAL_NEEDED => 'heroicon-o-hand-raised',
            self::USER_REGISTERED => 'heroicon-o-user-plus',
            self::USER_REPORT => 'heroicon-o-flag',
            self::TRANSLATION_COMPLETED => 'heroicon-o-language',
            self::TRANSLATION_REQUESTED => 'heroicon-o-chat-bubble-left-right',
            self::DASHBOARD_UPDATE => 'heroicon-o-chart-bar',
            self::METRIC_THRESHOLD => 'heroicon-o-chart-pie',
            self::CUSTOM => 'heroicon-o-cog-6-tooth',
            self::GENERIC => 'heroicon-o-bell',
            self::TEST => 'heroicon-o-beaker',
        };
    }

    /**
     * Check if the notification type is campaign-related.
     */
    public function isCampaignType(): bool
    {
        return $this->getCategory() === 'campaigns';
    }

    /**
     * Check if the notification type is donation-related.
     */
    public function isDonationType(): bool
    {
        return $this->getCategory() === 'donations';
    }

    /**
     * Check if the notification type is system-related.
     */
    public function isSystemType(): bool
    {
        return $this->getCategory() === 'system';
    }

    /**
     * Check if the notification type requires real-time delivery.
     */
    public function isRealTime(): bool
    {
        return match ($this) {
            self::LARGE_DONATION,
            self::CAMPAIGN_MILESTONE,
            self::CAMPAIGN_GOAL_REACHED,
            self::SECURITY_ALERT,
            self::APPROVAL_NEEDED,
            self::PAYMENT_FAILED,
            self::DASHBOARD_UPDATE,
            self::SYSTEM_ALERT => true,
            default => false,
        };
    }

    /**
     * Get all values as strings.
     *
     * @return array<string>
     */
    public static function getAllValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get all campaign-related notification types.
     *
     * @return array<NotificationType>
     */
    public static function campaignTypes(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->isCampaignType()
        );
    }

    /**
     * Get all donation-related notification types.
     *
     * @return array<NotificationType>
     */
    public static function donationTypes(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->isDonationType()
        );
    }

    /**
     * Get all system-related notification types.
     *
     * @return array<NotificationType>
     */
    public static function systemTypes(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->isSystemType()
        );
    }

    /**
     * Get all notification types that require real-time delivery.
     *
     * @return array<NotificationType>
     */
    public static function realTimeTypes(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->isRealTime()
        );
    }
}

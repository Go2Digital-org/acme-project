<?php

declare(strict_types=1);

return [
    // General notification types
    'notifications' => 'Notifications',
    'new_notification' => 'New Notification',
    'mark_as_read' => 'Mark as Read',
    'mark_all_read' => 'Mark All as Read',
    'mark_all_as_read' => 'Mark All as Read',
    'view_all_notifications' => 'View All Notifications',
    'no_notifications' => 'No notifications',
    'unread_notifications' => 'Unread Notifications',
    'unread' => 'Unread',

    // Campaign notifications
    'new_campaign_available' => 'New campaign available',
    'campaign_goal_reached' => 'Campaign goal reached!',
    'campaign_ending_soon' => 'Campaign ending soon',
    'campaign_published' => 'Your campaign has been published',
    'campaign_approved' => 'Your campaign has been approved',
    'campaign_rejected' => 'Your campaign requires changes',
    'new_donation_received' => 'New donation received',
    'sample_campaign_description' => 'Help provide clean water to communities in need',

    // Donation notifications
    'donation_confirmed' => 'Donation confirmed',
    'donation_processed' => 'Your :amount donation to :campaign has been processed',
    'donation_receipt' => 'Donation receipt available',
    'recurring_donation_processed' => 'Recurring donation processed',
    'payment_failed' => 'Payment failed - please update payment method',

    // System notifications
    'system_maintenance' => 'System maintenance scheduled',
    'account_updated' => 'Account information updated',
    'password_changed' => 'Password changed successfully',
    'login_alert' => 'New login detected',
    'security_alert' => 'Security alert - unusual activity detected',

    // Admin notifications
    'new_user_registered' => 'New user registered',
    'new_campaign_submitted' => 'New campaign awaiting approval',
    'user_report_submitted' => 'User report submitted',
    'system_alert' => 'System alert requires attention',

    // Time stamps
    'just_now' => 'Just now',
    'minutes_ago' => ':count minute ago|:count minutes ago',
    'hours_ago' => ':count hour ago|:count hours ago',
    'days_ago' => ':count day ago|:count days ago',
    'weeks_ago' => ':count week ago|:count weeks ago',

    // Email notifications
    'campaign_email_footer' => 'Thank you for supporting corporate social responsibility initiatives.',
    'goal_reached_message' => 'The campaign has successfully reached its goal of :amount!',
    'thank_you_support' => 'Thank you for your continued support.',

    // Empty state
    'empty_state_message' => 'You have no notifications yet. Check back later or browse our active campaigns.',
    'no_notifications_description' => 'You have no notifications at this time. We\'ll notify you when there\'s something new.',

    // Notification preferences
    'notification_preferences' => 'Notification Preferences',
    'email_notifications' => 'Email Notifications',
    'push_notifications' => 'Push Notifications',
    'sms_notifications' => 'SMS Notifications',
    'notification_frequency' => 'Notification Frequency',
    'immediate' => 'Immediate',
    'daily_digest' => 'Daily Digest',
    'weekly_digest' => 'Weekly Digest',
    'never' => 'Never',
];

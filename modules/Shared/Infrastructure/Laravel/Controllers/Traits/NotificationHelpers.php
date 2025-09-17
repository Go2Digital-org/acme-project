<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Traits;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

trait NotificationHelpers
{
    /**
     * Get notification type label.
     */
    private function getNotificationTypeLabel(string $type): string
    {
        $typeMap = [
            'App\\Notifications\\CampaignCreated' => 'campaign',
            'App\\Notifications\\DonationReceived' => 'donation',
            'App\\Notifications\\CampaignGoalReached' => 'campaign',
            'App\\Notifications\\CampaignEndingSoon' => 'campaign',
            'App\\Notifications\\DonationConfirmed' => 'donation',
            'App\\Notifications\\PaymentFailed' => 'payment',
        ];

        return $typeMap[$type] ?? 'system';
    }

    /**
     * Get notification title based on type and data.
     */
    private function getNotificationTitle(DatabaseNotification $notification): string
    {
        $data = $notification->getAttribute('data');

        return match ($this->getNotificationTypeLabel($notification->getAttribute('type'))) {
            'campaign' => $data['title'] ?? __('notifications.new_campaign_available'),
            'donation' => __('notifications.donation_confirmed'),
            'payment' => __('notifications.payment_failed'),
            default => __('notifications.new_notification'),
        };
    }

    /**
     * Get notification message based on type and data.
     */
    private function getNotificationMessage(DatabaseNotification $notification): string
    {
        $data = $notification->getAttribute('data');

        return match ($this->getNotificationTypeLabel($notification->getAttribute('type'))) {
            'campaign' => $data['message'] ?? $data['description'] ?? __('notifications.sample_campaign_description'),
            'donation' => __('notifications.donation_processed', [
                'amount' => $data['amount'] ?? '$50',
                'campaign' => $data['campaign_title'] ?? 'Campaign',
            ]),
            'payment' => __('notifications.payment_failed'),
            default => $data['message'] ?? __('notifications.new_notification'),
        };
    }

    /**
     * Get notification icon color based on type.
     */
    private function getNotificationIconColor(string $type): string
    {
        return match ($this->getNotificationTypeLabel($type)) {
            'campaign' => 'bg-primary',
            'donation' => 'bg-secondary',
            'payment' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get notification URL for click handling.
     */
    private function getNotificationUrl(DatabaseNotification $notification): ?string
    {
        $data = $notification->getAttribute('data');

        return match ($this->getNotificationTypeLabel($notification->getAttribute('type'))) {
            'campaign' => isset($data['campaign_id']) ? route('campaigns.show', $data['campaign_id']) : route('campaigns.index'),
            'donation' => isset($data['donation_id']) ? route('donations.show', $data['donation_id']) : route('donations.index'),
            default => null,
        };
    }

    /**
     * Get human readable time ago.
     */
    private function getTimeAgo(Carbon $createdAt): string
    {
        $diffInMinutes = (int) $createdAt->diffInMinutes();
        $diffInHours = (int) $createdAt->diffInHours();
        $diffInDays = (int) $createdAt->diffInDays();

        if ($diffInMinutes < 1) {
            return __('notifications.just_now');
        }

        if ($diffInMinutes < 60) {
            return trans_choice('notifications.minutes_ago', $diffInMinutes);
        }

        if ($diffInHours < 24) {
            return trans_choice('notifications.hours_ago', $diffInHours);
        }

        if ($diffInDays < 7) {
            return trans_choice('notifications.days_ago', $diffInDays);
        }

        return trans_choice('notifications.weeks_ago', (int) ($diffInDays / 7));
    }
}

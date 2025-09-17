<?php

declare(strict_types=1);

namespace Modules\Notification\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Notification read model optimized for notification details, status, and delivery information.
 */
final class NotificationReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        string $notificationId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($notificationId, $data, $version);
        $this->setCacheTtl(600); // 10 minutes for notifications
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'notification',
            'notification:' . $this->id,
            'user:' . $this->getUserId(),
            'type:' . $this->getType(),
        ]);
    }

    // Basic Notification Information
    public function getNotificationId(): int
    {
        return (int) $this->id;
    }

    public function getType(): string
    {
        return $this->get('type', 'general');
    }

    public function getTitle(): string
    {
        return $this->get('title', '');
    }

    public function getMessage(): string
    {
        return $this->get('message', '');
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->get('data', []);
    }

    public function getChannel(): string
    {
        return $this->get('channel', 'database');
    }

    public function getPriority(): string
    {
        return $this->get('priority', 'normal');
    }

    // Priority Helpers
    public function isLowPriority(): bool
    {
        return $this->getPriority() === 'low';
    }

    public function isNormalPriority(): bool
    {
        return $this->getPriority() === 'normal';
    }

    public function isHighPriority(): bool
    {
        return $this->getPriority() === 'high';
    }

    public function isCritical(): bool
    {
        return $this->getPriority() === 'critical';
    }

    // Status Information
    public function isRead(): bool
    {
        return $this->get('read_at') !== null;
    }

    public function isUnread(): bool
    {
        return ! $this->isRead();
    }

    public function getReadAt(): ?string
    {
        return $this->get('read_at');
    }

    public function isDismissed(): bool
    {
        return $this->get('dismissed_at') !== null;
    }

    public function getDismissedAt(): ?string
    {
        return $this->get('dismissed_at');
    }

    // User Information
    public function getUserId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function getUserName(): ?string
    {
        return $this->get('user_name');
    }

    public function getUserEmail(): ?string
    {
        return $this->get('user_email');
    }

    // Type-specific Helpers
    public function isCampaignNotification(): bool
    {
        return str_starts_with($this->getType(), 'campaign.');
    }

    public function isDonationNotification(): bool
    {
        return str_starts_with($this->getType(), 'donation.');
    }

    public function isSystemNotification(): bool
    {
        return str_starts_with($this->getType(), 'system.');
    }

    public function isUserNotification(): bool
    {
        return str_starts_with($this->getType(), 'user.');
    }

    public function isOrganizationNotification(): bool
    {
        return str_starts_with($this->getType(), 'organization.');
    }

    // Type Categorization
    public function getCategory(): string
    {
        return match (true) {
            $this->isCampaignNotification() => 'campaign',
            $this->isDonationNotification() => 'donation',
            $this->isSystemNotification() => 'system',
            $this->isUserNotification() => 'user',
            $this->isOrganizationNotification() => 'organization',
            default => 'general',
        };
    }

    public function getCategoryLabel(): string
    {
        return match ($this->getCategory()) {
            'campaign' => 'Campaign',
            'donation' => 'Donation',
            'system' => 'System',
            'user' => 'User',
            'organization' => 'Organization',
            'general' => 'General',
            default => 'Other',
        };
    }

    // Data Extraction Helpers
    public function getCampaignId(): ?int
    {
        $data = $this->getData();
        $campaignId = $data['campaign_id'] ?? null;

        return $campaignId ? (int) $campaignId : null;
    }

    public function getCampaignTitle(): ?string
    {
        $data = $this->getData();

        return $data['campaign_title'] ?? null;
    }

    public function getDonationId(): ?int
    {
        $data = $this->getData();
        $donationId = $data['donation_id'] ?? null;

        return $donationId ? (int) $donationId : null;
    }

    public function getDonationAmount(): ?float
    {
        $data = $this->getData();
        $amount = $data['donation_amount'] ?? null;

        return $amount ? (float) $amount : null;
    }

    public function getOrganizationId(): ?int
    {
        $data = $this->getData();
        $orgId = $data['organization_id'] ?? null;

        return $orgId ? (int) $orgId : null;
    }

    public function getOrganizationName(): ?string
    {
        $data = $this->getData();

        return $data['organization_name'] ?? null;
    }

    // Action Information
    public function hasAction(): bool
    {
        $data = $this->getData();

        return isset($data['action']) || isset($data['action_url']) || isset($data['action_text']);
    }

    public function getActionText(): ?string
    {
        $data = $this->getData();

        return $data['action_text'] ?? null;
    }

    public function getActionUrl(): ?string
    {
        $data = $this->getData();

        return $data['action_url'] ?? null;
    }

    public function getAction(): ?string
    {
        $data = $this->getData();

        return $data['action'] ?? null;
    }

    // Delivery Information
    public function isDelivered(): bool
    {
        return $this->get('delivered_at') !== null;
    }

    public function getDeliveredAt(): ?string
    {
        return $this->get('delivered_at');
    }

    public function getDeliveryStatus(): string
    {
        if ($this->isDelivered()) {
            return 'delivered';
        }

        return $this->get('delivery_status', 'pending');
    }

    public function getDeliveryError(): ?string
    {
        return $this->get('delivery_error');
    }

    public function hasDeliveryError(): bool
    {
        return $this->getDeliveryError() !== null;
    }

    // Multi-channel Support
    /**
     * @return array<string>
     */
    public function getChannels(): array
    {
        return $this->get('channels', [$this->getChannel()]);
    }

    public function isMultiChannel(): bool
    {
        return count($this->getChannels()) > 1;
    }

    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->getChannels());
    }

    // Timing
    public function getScheduledFor(): ?string
    {
        return $this->get('scheduled_for');
    }

    public function isScheduled(): bool
    {
        return $this->getScheduledFor() !== null;
    }

    public function isPastDue(): bool
    {
        $scheduledFor = $this->getScheduledFor();
        if (! $scheduledFor) {
            return false;
        }

        return strtotime($scheduledFor) < time();
    }

    // Timestamps
    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->get('updated_at');
    }

    public function getSentAt(): ?string
    {
        return $this->get('sent_at');
    }

    // Time Calculations
    public function getAgeInMinutes(): int
    {
        $created = $this->getCreatedAt();
        if (! $created) {
            return 0;
        }

        return (int) ((time() - strtotime($created)) / 60);
    }

    public function getAgeInHours(): int
    {
        return (int) ($this->getAgeInMinutes() / 60);
    }

    public function getAgeInDays(): int
    {
        return (int) ($this->getAgeInHours() / 24);
    }

    public function getTimeToRead(): ?int
    {
        $created = $this->getCreatedAt();
        $readAt = $this->getReadAt();

        if (! $created || ! $readAt) {
            return null;
        }

        return (int) ((strtotime($readAt) - strtotime($created)) / 60); // Minutes
    }

    // Display Helpers
    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        $data = $this->getData();

        // Simple variable replacement
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $message = str_replace("{{$key}}", (string) $value, $message);
            }
        }

        return $message;
    }

    public function getShortMessage(int $length = 100): string
    {
        $message = $this->getFormattedMessage();

        return strlen($message) > $length ? substr($message, 0, $length) . '...' : $message;
    }

    public function getIcon(): string
    {
        return match ($this->getCategory()) {
            'campaign' => 'campaign',
            'donation' => 'heart',
            'system' => 'cog',
            'user' => 'user',
            'organization' => 'building',
            default => 'bell',
        };
    }

    public function getColor(): string
    {
        return match ($this->getPriority()) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'critical' => 'red',
            default => 'blue',
        };
    }

    // State Helpers
    public function canBeMarkedAsRead(): bool
    {
        return $this->isUnread();
    }

    public function canBeDismissed(): bool
    {
        return ! $this->isDismissed();
    }

    public function canBeDeleted(): bool
    {
        // Allow deletion of read notifications older than 30 days
        return $this->isRead() && $this->getAgeInDays() > 30;
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getNotificationId(),
            'type' => $this->getType(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'formatted_message' => $this->getFormattedMessage(),
            'short_message' => $this->getShortMessage(),
            'data' => $this->getData(),
            'channel' => $this->getChannel(),
            'channels' => $this->getChannels(),
            'priority' => $this->getPriority(),
            'category' => $this->getCategory(),
            'category_label' => $this->getCategoryLabel(),
            'status' => [
                'is_read' => $this->isRead(),
                'is_unread' => $this->isUnread(),
                'read_at' => $this->getReadAt(),
                'is_dismissed' => $this->isDismissed(),
                'dismissed_at' => $this->getDismissedAt(),
            ],
            'user' => [
                'id' => $this->getUserId(),
                'name' => $this->getUserName(),
                'email' => $this->getUserEmail(),
            ],
            'delivery' => [
                'is_delivered' => $this->isDelivered(),
                'delivered_at' => $this->getDeliveredAt(),
                'delivery_status' => $this->getDeliveryStatus(),
                'delivery_error' => $this->getDeliveryError(),
                'has_delivery_error' => $this->hasDeliveryError(),
            ],
            'action' => [
                'has_action' => $this->hasAction(),
                'action_text' => $this->getActionText(),
                'action_url' => $this->getActionUrl(),
                'action' => $this->getAction(),
            ],
            'timing' => [
                'scheduled_for' => $this->getScheduledFor(),
                'is_scheduled' => $this->isScheduled(),
                'is_past_due' => $this->isPastDue(),
                'age_minutes' => $this->getAgeInMinutes(),
                'age_hours' => $this->getAgeInHours(),
                'age_days' => $this->getAgeInDays(),
                'time_to_read' => $this->getTimeToRead(),
            ],
            'display' => [
                'icon' => $this->getIcon(),
                'color' => $this->getColor(),
                'can_be_marked_as_read' => $this->canBeMarkedAsRead(),
                'can_be_dismissed' => $this->canBeDismissed(),
                'can_be_deleted' => $this->canBeDeleted(),
            ],
            'related' => [
                'campaign_id' => $this->getCampaignId(),
                'campaign_title' => $this->getCampaignTitle(),
                'donation_id' => $this->getDonationId(),
                'donation_amount' => $this->getDonationAmount(),
                'organization_id' => $this->getOrganizationId(),
                'organization_name' => $this->getOrganizationName(),
            ],
            'timestamps' => [
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
                'sent_at' => $this->getSentAt(),
            ],
        ];
    }

    /**
     * Get summary data optimized for lists and cards
     *
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getNotificationId(),
            'type' => $this->getType(),
            'title' => $this->getTitle(),
            'short_message' => $this->getShortMessage(),
            'priority' => $this->getPriority(),
            'category' => $this->getCategory(),
            'is_read' => $this->isRead(),
            'is_unread' => $this->isUnread(),
            'has_action' => $this->hasAction(),
            'action_text' => $this->getActionText(),
            'action_url' => $this->getActionUrl(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'age_minutes' => $this->getAgeInMinutes(),
            'created_at' => $this->getCreatedAt(),
        ];
    }

    /**
     * Get data optimized for mobile/push notifications
     *
     * @return array<string, mixed>
     */
    public function toPushNotification(): array
    {
        return [
            'id' => $this->getNotificationId(),
            'title' => $this->getTitle(),
            'body' => $this->getShortMessage(200),
            'data' => array_merge($this->getData(), [
                'notification_id' => $this->getNotificationId(),
                'type' => $this->getType(),
                'category' => $this->getCategory(),
                'priority' => $this->getPriority(),
            ]),
            'priority' => $this->getPriority(),
            'click_action' => $this->getActionUrl(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Donation\Domain\Model\Donation;

class DonationNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $additionalData
     */
    public function __construct(
        public readonly Donation $donation,
        public readonly string $eventType,
        /** @var array<string, mixed> */
        public readonly array $additionalData = [],
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Admin dashboard for all donation events
        $channels[] = new Channel('admin-dashboard');

        // Large donations also go to finance admins
        if ($this->isLargeDonation()) {
            $channels[] = new Channel('admin-role-finance_admin');
        }

        // Organization-specific channel
        if ($this->donation->campaign && $this->donation->campaign->organization_id) {
            $channels[] = new PrivateChannel("organization.{$this->donation->campaign->organization_id}");
        }

        // Campaign-specific channel
        if ($this->donation->campaign_id) {
            $channels[] = new PrivateChannel("campaign.{$this->donation->campaign_id}");
        }

        // Payment failure notifications also go to payment notifications channel
        if ($this->eventType === 'payment.failed') {
            $channels[] = new Channel('payment-notifications');
        }

        return $channels;
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return $this->eventType;
    }

    /**
     * Get the data to broadcast.
     */
    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $baseData = [
            'donation_id' => $this->donation->id,
            'amount' => $this->donation->amount,
            'currency' => $this->donation->currency,
            'formatted_amount' => number_format($this->donation->amount, 2) . ' ' . $this->donation->currency,
            'donor_name' => $this->donation->anonymous ? 'Anonymous' : $this->donation->user->name ?? 'Unknown Donor',
            'donor_email' => $this->donation->anonymous ? null : $this->donation->user?->email,
            'campaign_id' => $this->donation->campaign_id,
            'campaign_title' => $this->donation->campaign->title ?? 'Unknown Campaign',
            'status' => $this->donation->status->value,
            'payment_method' => $this->donation->payment_method?->value,
            'created_at' => $this->donation->created_at?->toIso8601String(),
        ];

        // Add event-specific data
        return match ($this->eventType) {
            'donation.large' => array_merge($baseData, [
                'threshold_amount' => $this->additionalData['threshold_amount'] ?? 1000,
                'is_anonymous' => $this->donation->is_anonymous,
                'organization_name' => $this->donation->campaign?->organization?->getName(),
            ]),
            'payment.failed' => array_merge($baseData, [
                'failure_reason' => $this->additionalData['failure_reason'] ?? 'Unknown error',
                'error_code' => $this->additionalData['error_code'] ?? null,
                'retry_count' => $this->additionalData['retry_count'] ?? 0,
                'next_retry_at' => $this->additionalData['next_retry_at'] ?? null,
            ]),
            'donation.processed' => array_merge($baseData, [
                'processing_time' => $this->additionalData['processing_time'] ?? null,
                'transaction_id' => $this->additionalData['transaction_id'] ?? null,
                'fee_amount' => $this->additionalData['fee_amount'] ?? null,
            ]),
            'donation.refunded' => array_merge($baseData, [
                'refund_amount' => $this->additionalData['refund_amount'] ?? $this->donation->amount,
                'refund_reason' => $this->additionalData['refund_reason'] ?? 'User requested',
                'refund_transaction_id' => $this->additionalData['refund_transaction_id'] ?? null,
            ]),
            default => $baseData,
        };
    }

    /**
     * The queue connection to use when broadcasting.
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldBroadcast(): bool
    {
        // Don't broadcast test donations in production
        return ! (app()->environment('production') && str_contains($this->donation->user->email ?? '', 'test@'));
    }

    /**
     * Determine if this is a large donation.
     */
    private function isLargeDonation(): bool
    {
        $threshold = config('donation.large_donation_threshold', 1000);

        return $this->donation->amount >= $threshold;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Donation\Domain\Model\Donation;

/**
 * @mixin Donation
 */
final class DonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method?->value,
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status->value,
            'anonymous' => $this->anonymous,
            'recurring' => $this->recurring,
            'recurring_frequency' => $this->recurring_frequency,
            'donated_at' => $this->donated_at->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'refunded_at' => $this->refunded_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'refund_reason' => $this->refund_reason,
            'notes' => $this->notes,
            'days_since_donation' => $this->days_since_donation,
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_refunded' => $this->canBeRefunded(),
            'is_successful' => $this->isSuccessful(),
            'is_pending' => $this->isPending(),
            'is_failed' => $this->isFailed(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Include relationships when loaded
            'campaign' => $this->whenLoaded('campaign', fn (): ?array => $this->campaign ? [
                'id' => $this->campaign->id,
                'title' => $this->campaign->title,
                'status' => $this->campaign->status->value,
            ] : null),
            'user' => $this->whenLoaded(
                'user',
                fn (): ?array => $this->user !== null ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ] : null,
            ),
        ];
    }
}

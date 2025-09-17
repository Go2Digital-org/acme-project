<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Http\Resources;

use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Http\Resources\BaseApiResource;

class CampaignDetailResource extends BaseApiResource
{
    /**
     * Transform the resource into an array optimized for detail views.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        /** @var Campaign $campaign */
        $campaign = $this->resource;

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'description' => $campaign->description,
            'slug' => $campaign->slug,

            'status' => [
                'value' => $campaign->status->value,
                'label' => $campaign->status->getLabel(),
                'color' => $campaign->status->getColor(),
                'can_accept_donations' => $campaign->status->canAcceptDonations(),
                'is_active' => $campaign->status->isActive(),
            ],

            'financial' => [
                'goal_amount' => $this->transformMoney((float) $campaign->goal_amount),
                'current_amount' => $this->transformMoney((float) $campaign->current_amount),
                'remaining_amount' => $this->transformMoney($campaign->getRemainingAmount()),
                'progress_percentage' => round($campaign->getProgressPercentage(), 1),
                'donations_count' => $campaign->donations_count ?? 0,
                'average_donation' => $this->calculateAverageDonation($campaign),
                'has_corporate_matching' => $campaign->has_corporate_matching ?? false,
            ],

            'timing' => [
                'start_date' => $this->transformDate($campaign->start_date),
                'end_date' => $this->transformDate($campaign->end_date),
                'created_at' => $this->transformDate($campaign->created_at),
                'updated_at' => $this->transformDate($campaign->updated_at),
                'days_remaining' => $campaign->getDaysRemaining(),
                'is_ending_soon' => $campaign->getDaysRemaining() <= 7 && $campaign->getDaysRemaining() >= 0,
            ],

            'creator' => $this->whenLoaded('creator', function ($creator) {
                return [
                    'id' => $creator->getId(),
                    'name' => $creator->getName(),
                    'title' => $creator->title ?? 'ACME Employee',
                    'avatar_url' => $creator->profile_photo_url,
                ];
            }),

            'organization' => $this->whenLoaded('organization', function ($organization) {
                return [
                    'id' => $organization->id,
                    'name' => $organization->getName(),
                    'logo_url' => $organization->logo_url,
                ];
            }),

            'metadata' => [
                'featured_image' => $campaign->featured_image,
                'is_featured' => (bool) ($campaign->is_featured ?? false),
                'category' => $campaign->category,
                'visibility' => $campaign->visibility,
            ],

            'actions' => [
                'can_donate' => $campaign->canAcceptDonation(),
                'can_share' => true,
                'can_bookmark' => auth()->check(),
                'donate_url' => $campaign->canAcceptDonation()
                    ? route('campaigns.donate', ['campaign' => $campaign])
                    : null,
                'share_url' => route('campaigns.show', ['campaign' => $campaign]),
            ],

            // Include donation statistics if requested
            'donation_stats' => $this->when(
                $this->shouldIncludeRelation($request, 'donation_stats'),
                function () use ($campaign) {
                    return [
                        'recent_donations_count' => $this->getRecentDonationsCount($campaign),
                        'top_donation_amount' => $this->getTopDonationAmount($campaign),
                        'donation_velocity' => $this->getDonationVelocity($campaign),
                    ];
                }
            ),
        ];
    }

    /**
     * Calculate average donation amount.
     *
     * @return array<string, mixed>
     */
    private function calculateAverageDonation(Campaign $campaign): array
    {
        $donationsCount = $campaign->donations_count ?? 0;

        if ($donationsCount === 0) {
            return $this->transformMoney(0.0, true);
        }

        $average = (float) $campaign->current_amount / $donationsCount;

        return $this->transformMoney($average, true);
    }

    /**
     * Get recent donations count (last 7 days).
     */
    private function getRecentDonationsCount(Campaign $campaign): int
    {
        // This would typically come from a cached value or be computed elsewhere
        return $campaign->recent_donations_count ?? 0;
    }

    /**
     * Get top donation amount.
     *
     * @return array<string, mixed>
     */
    private function getTopDonationAmount(Campaign $campaign): array
    {
        // This would typically come from a cached value
        $amount = $campaign->top_donation_amount ?? 0.0;

        return $this->transformMoney($amount, true);
    }

    /**
     * Get donation velocity (donations per day).
     */
    private function getDonationVelocity(Campaign $campaign): float
    {
        // This would typically come from a cached value
        return $campaign->donation_velocity ?? 0.0;
    }
}

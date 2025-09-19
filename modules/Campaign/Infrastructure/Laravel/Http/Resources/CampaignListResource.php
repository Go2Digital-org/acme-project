<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Http\Resources;

use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Http\Resources\BaseApiResource;

class CampaignListResource extends BaseApiResource
{
    /**
     * Transform the resource into an array optimized for list views.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Campaign $campaign */
        $campaign = $this->resource;

        $data = [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'slug' => $campaign->slug,
            'status' => [
                'value' => $campaign->status->value,
                'label' => $campaign->status->getLabel(),
                'can_accept_donations' => $campaign->status->canAcceptDonations(),
            ],
        ];

        // Add financial data with compact formatting
        if ($this->shouldIncludeField($request, 'financial')) {
            $data['financial'] = [
                'goal_amount' => $this->transformMoney((float) $campaign->goal_amount, true),
                'current_amount' => $this->transformMoney((float) $campaign->current_amount, true),
                'progress_percentage' => round($campaign->getProgressPercentage(), 1),
            ];
        }

        // Add timing data
        if ($this->shouldIncludeField($request, 'timing')) {
            $daysRemaining = $campaign->getDaysRemaining();
            $data['timing'] = [
                'days_remaining' => max(0, $daysRemaining),
                'is_ending_soon' => $daysRemaining <= 7 && $daysRemaining >= 0,
                'end_date' => $this->transformDate($campaign->end_date, true),
            ];
        }

        // Add creator info (lazy loaded)
        if ($this->shouldIncludeField($request, 'creator')) {
            $data['creator'] = $this->whenLoadedRelation('creator', fn ($creator): array => [
                'id' => $creator->getId(),
                'name' => $creator->getName(),
            ]);
        }

        // Add organization info (lazy loaded)
        if ($this->shouldIncludeField($request, 'organization')) {
            $data['organization'] = $this->whenLoadedRelation('organization', fn ($organization): array => [
                'id' => $organization->id,
                'name' => $organization->getName(),
            ]);
        }

        // Add metadata if requested
        if ($this->shouldIncludeField($request, 'metadata')) {
            $data['metadata'] = [
                'featured_image' => $campaign->featured_image,
                'is_featured' => (bool) ($campaign->is_featured ?? false),
                'donations_count' => $campaign->donations_count ?? 0,
            ];
        }

        // Add actions
        if ($this->shouldIncludeField($request, 'actions')) {
            $data['actions'] = [
                'can_donate' => $campaign->canAcceptDonation(),
                'donate_url' => $campaign->canAcceptDonation()
                    ? route('campaigns.donate', ['campaign' => $campaign])
                    : null,
                'view_url' => route('campaigns.show', ['campaign' => $campaign]),
            ];
        }

        return $data;
    }

    /**
     * Create collection response with optimized pagination.
     */
    public static function collection($resource)
    {
        return parent::collection($resource)->additional([
            'meta' => [
                'optimized_for' => 'list_view',
                'cache_ttl' => 300,
            ],
        ]);
    }
}

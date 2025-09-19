<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Helper\CurrencyHelper;
use Modules\Shared\Domain\ValueObject\Money;

final readonly class CampaignViewDataService
{
    public function __construct(
        private CurrencyHelper $currencyHelper,
    ) {}

    /**
     * Transform a single campaign into view-ready data.
     */
    /**
     * @return array<string, mixed>
     */
    public function getSingleCampaignViewData(Campaign $campaign, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return [
            'id' => $campaign->id,
            'title' => $campaign->getTranslation('title', $locale) ?? $campaign->title,
            'description' => $campaign->getTranslation('description', $locale) ?? $campaign->description,
            'slug' => $campaign->slug,
            'status' => $this->getStatusViewData($campaign->status),
            'progress' => $this->getProgressViewData($campaign),
            'timing' => $this->getTimingViewData($campaign),
            'financial' => $this->getFinancialViewData($campaign),
            'creator' => $this->getCreatorViewData($campaign),
            'organization' => $this->getOrganizationViewData($campaign),
            'metadata' => $this->getMetadataViewData($campaign),
            'actions' => $this->getActionViewData($campaign),
        ];
    }

    /**
     * Transform campaign collection into view-ready data.
     *
     * @param  iterable<Campaign>  $campaigns
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignListViewData(iterable $campaigns, ?string $locale = null): array
    {
        $viewData = [];

        foreach ($campaigns as $campaign) {
            $viewData[] = $this->getCampaignCardViewData($campaign, $locale);
        }

        return $viewData;
    }

    /**
     * Get campaign card data for listing pages.
     *
     * @return array<string, mixed>
     */
    public function getCampaignCardViewData(Campaign $campaign, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return [
            'id' => $campaign->id,
            'title' => $campaign->getTranslation('title', $locale) ?? $campaign->title,
            'description' => $this->getTruncatedDescription($campaign, 150, $locale),
            'slug' => $campaign->slug,
            'featured_image' => $campaign->featured_image,
            'status' => $this->getStatusViewData($campaign->status),
            'progress' => $this->getProgressViewData($campaign),
            'timing' => $this->getTimingViewData($campaign),
            'financial' => $this->getFinancialViewData($campaign, compact: true),
            'creator_name' => $campaign->creator->name ?? 'Unknown',
            'organization_name' => $campaign->organization ? $campaign->organization->getName() : 'Unknown',
            'url' => route('campaigns.show', ['campaign' => $campaign]),
        ];
    }

    /**
     * Get status view data with styling information.
     *
     * @return array<string, mixed>
     */
    private function getStatusViewData(CampaignStatus $status): array
    {
        return [
            'value' => $status->value,
            'label' => $status->getLabel(),
            'color' => $status->getColor(),
            'icon' => $this->getStatusIcon($status),
            'css_class' => 'status-' . $status->value,
            'can_accept_donations' => $status->canAcceptDonations(),
            'is_active' => $status->isActive(),
        ];
    }

    /**
     * Get progress view data with percentage and styling.
     *
     * @return array<string, mixed>
     */
    private function getProgressViewData(Campaign $campaign): array
    {
        $percentage = $campaign->getProgressPercentage();
        $isComplete = $campaign->hasReachedGoal();

        return [
            'percentage' => round($percentage, 1),
            'percentage_int' => (int) round($percentage),
            'is_complete' => $isComplete,
            'is_over_goal' => $percentage > 100,
            'progress_class' => $this->getProgressClass($percentage),
            'remaining_percentage' => max(0, 100 - $percentage),
            'accessibility_label' => "{$percentage}% of goal reached",
        ];
    }

    /**
     * Get timing view data with days remaining and status.
     *
     * @return array<string, mixed>
     */
    private function getTimingViewData(Campaign $campaign): array
    {
        $daysRemaining = $campaign->getDaysRemaining();
        $isExpired = $daysRemaining < 0;
        $isEndingSoon = $daysRemaining <= 7 && $daysRemaining >= 0;

        return [
            'days_remaining' => abs($daysRemaining),
            'days_remaining_signed' => $daysRemaining,
            'is_expired' => $isExpired,
            'is_ending_soon' => $isEndingSoon,
            'timing_class' => $this->getTimingClass($daysRemaining),
            'timing_text' => $this->getTimingText($daysRemaining),
            'start_date' => [
                'raw' => $campaign->start_date,
                'formatted' => $campaign->start_date?->format('M j, Y') ?? '',
                'iso' => $campaign->start_date?->toISOString() ?? '',
            ],
            'end_date' => [
                'raw' => $campaign->end_date,
                'formatted' => $campaign->end_date?->format('M j, Y') ?? '',
                'iso' => $campaign->end_date?->toISOString() ?? '',
            ],
            'created_date' => [
                'raw' => $campaign->created_at,
                'formatted' => $campaign->created_at?->format('M j, Y') ?? '',
                'relative' => $campaign->created_at?->diffForHumans() ?? '',
                'iso' => $campaign->created_at?->toISOString() ?? '',
            ],
        ];
    }

    /**
     * Get financial view data with formatted amounts.
     *
     * @return array<string, mixed>
     */
    private function getFinancialViewData(Campaign $campaign, bool $compact = false): array
    {
        $goalAmount = new Money((float) $campaign->goal_amount);
        new Money((float) $campaign->current_amount);
        $remainingAmount = new Money($campaign->getRemainingAmount());

        $baseData = [
            'goal_amount' => [
                'raw' => $campaign->goal_amount,
                'formatted' => $this->currencyHelper->format((float) $campaign->goal_amount),
                'formatted_short' => $this->currencyHelper->formatShort((float) $campaign->goal_amount),
            ],
            'current_amount' => [
                'raw' => $campaign->current_amount,
                'formatted' => $this->currencyHelper->format((float) $campaign->current_amount),
                'formatted_short' => $this->currencyHelper->formatShort((float) $campaign->current_amount),
            ],
            'remaining_amount' => [
                'raw' => $remainingAmount->amount,
                'formatted' => $this->currencyHelper->format($remainingAmount->amount),
                'formatted_short' => $this->currencyHelper->formatShort($remainingAmount->amount),
            ],
        ];

        if ($compact) {
            return $baseData;
        }

        return array_merge($baseData, [
            'donations_count' => $campaign->donations_count ?? 0,
            'average_donation' => $this->getAverageDonation($campaign),
            'currency' => $goalAmount->currency,
            'has_corporate_matching' => $campaign->has_corporate_matching,
            'corporate_matching_rate' => $campaign->corporate_matching_rate,
            'max_corporate_matching' => $campaign->max_corporate_matching,
        ]);
    }

    /**
     * Get creator view data.
     *
     * @return array<string, mixed>
     */
    private function getCreatorViewData(Campaign $campaign): array
    {
        if (! $campaign->relationLoaded('creator')) {
            return [
                'name' => 'Unknown',
                'initials' => '?',
                'title' => null,
                'avatar_url' => null,
            ];
        }

        $creator = $campaign->creator;

        return [
            'id' => $creator->getId(),
            'name' => $creator->getName(),
            'initials' => strtoupper(substr($creator->getName(), 0, 1)),
            'title' => $creator->title ?? 'ACME Employee',
            'avatar_url' => $creator->profile_photo_url ?? null,
        ];
    }

    /**
     * Get organization view data.
     *
     * @return array<string, mixed>
     */
    private function getOrganizationViewData(Campaign $campaign): array
    {
        if (! $campaign->relationLoaded('organization')) {
            return [
                'name' => 'Unknown Organization',
                'logo_url' => null,
            ];
        }

        $organization = $campaign->organization;

        return [
            'id' => $organization->id,
            'name' => $organization->getName(),
            'logo_url' => $organization->logo_url ?? null,
        ];
    }

    /**
     * Get metadata view data.
     *
     * @return array<string, mixed>
     */
    private function getMetadataViewData(Campaign $campaign): array
    {
        return [
            'category' => $campaign->category,
            'visibility' => $campaign->visibility,
            'featured_image_url' => $campaign->featured_image ?? null,
            'is_featured' => (bool) ($campaign->getAttribute('featured') ?? false),
        ];
    }

    /**
     * Get available actions for the campaign.
     *
     * @return array<string, mixed>
     */
    private function getActionViewData(Campaign $campaign): array
    {
        return [
            'can_donate' => $campaign->canAcceptDonation(),
            'can_share' => true,
            'can_bookmark' => auth()->check(),
            'donate_url' => $campaign->canAcceptDonation()
                ? route('campaigns.donate', ['campaign' => $campaign])
                : null,
            'share_url' => route('campaigns.show', ['campaign' => $campaign]),
        ];
    }

    /**
     * Get truncated description for card views.
     */
    private function getTruncatedDescription(Campaign $campaign, int $limit, ?string $locale = null): string
    {
        $description = $campaign->getTranslation('description', $locale) ?? $campaign->description;

        if (strlen($description) <= $limit) {
            return $description;
        }

        return substr($description, 0, $limit) . '...';
    }

    /**
     * Get status icon for display.
     */
    private function getStatusIcon(CampaignStatus $status): string
    {
        return match ($status) {
            CampaignStatus::DRAFT => 'fas fa-pause',
            CampaignStatus::ACTIVE => 'fas fa-play',
            CampaignStatus::PAUSED => 'fas fa-pause',
            CampaignStatus::COMPLETED => 'fas fa-check',
            CampaignStatus::CANCELLED => 'fas fa-times',
            CampaignStatus::EXPIRED => 'fas fa-clock',
            CampaignStatus::PENDING_APPROVAL => 'fas fa-hourglass-half',
            CampaignStatus::REJECTED => 'fas fa-times-circle',
        };
    }

    /**
     * Get progress CSS class based on percentage.
     */
    private function getProgressClass(float $percentage): string
    {
        return match (true) {
            $percentage >= 100 => 'progress-complete',
            $percentage >= 75 => 'progress-high',
            $percentage >= 50 => 'progress-medium',
            $percentage >= 25 => 'progress-low',
            default => 'progress-minimal',
        };
    }

    /**
     * Get timing CSS class based on days remaining.
     */
    private function getTimingClass(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining < 0 => 'timing-expired',
            $daysRemaining <= 3 => 'timing-critical',
            $daysRemaining <= 7 => 'timing-warning',
            default => 'timing-normal',
        };
    }

    /**
     * Get human-readable timing text.
     */
    private function getTimingText(int $daysRemaining): string
    {
        return match (true) {
            $daysRemaining < 0 => 'Campaign Ended',
            $daysRemaining === 0 => 'Last Day',
            $daysRemaining === 1 => '1 Day Left',
            default => "{$daysRemaining} Days Left",
        };
    }

    /**
     * Calculate average donation amount.
     *
     * @return array<string, mixed>
     */
    private function getAverageDonation(Campaign $campaign): array
    {
        $donationsCount = $campaign->donations_count ?? 0;

        if ($donationsCount === 0) {
            return [
                'raw' => 0,
                'formatted' => $this->currencyHelper->format(0.0),
            ];
        }

        $average = (float) $campaign->current_amount / $donationsCount;

        return [
            'raw' => $average,
            'formatted' => $this->currencyHelper->format($average),
        ];
    }
}

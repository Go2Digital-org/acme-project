<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ViewPresenter;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\DonationProgress;
use Modules\Shared\Application\ViewPresenter\AbstractViewPresenter;
use Modules\Shared\Domain\ValueObject\Money;

/**
 * View presenter for Campaign card display
 * Handles all campaign card logic including progress calculation, days left, etc.
 */
final class CampaignCardPresenter extends AbstractViewPresenter
{
    private readonly Campaign $campaign;

    public function __construct(Campaign $campaign, private readonly ?string $searchTerm = null)
    {
        parent::__construct($campaign);
        $this->campaign = $campaign;
    }

    /**
     * Present campaign data for card display.
     *
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $daysRemaining = $this->campaign->getDaysRemaining();
        $isExpired = $daysRemaining < 0;
        $isAlmostExpired = $daysRemaining <= 7 && $daysRemaining >= 0;

        return [
            'id' => $this->campaign->id,
            'title' => $this->highlightText($this->campaign->getTranslation('title') ?? ''),
            'description' => $this->highlightText($this->campaign->getTranslation('description') ?? ''),
            'slug' => $this->campaign->slug,
            'featured' => $this->campaign->featured_image !== null,
            'image_url' => $this->campaign->featured_image,
            'image_alt' => null,
            'organization_name' => $this->campaign->organization ? $this->campaign->organization->getName() : null,
            'categories' => collect([$this->campaign->category])->filter(),
            'goal_amount' => $this->campaign->goal_amount,
            'current_amount' => $this->campaign->current_amount,
            'remaining_amount' => $this->campaign->getRemainingAmount(),
            'progress_percentage' => $this->campaign->getProgressPercentage(),
            'days_remaining' => $daysRemaining,
            'status' => [
                'value' => $this->campaign->status->value,
                'label' => $this->campaign->status->getLabel(),
                'color' => $this->campaign->status->getColor(),
                'color_class' => $this->getStatusColorClass(),
            ],
            'timing' => [
                'days_remaining' => $daysRemaining,
                'is_expired' => $isExpired,
                'is_almost_expired' => $isAlmostExpired,
                'is_ending_today' => $daysRemaining === 0,
                'display_text' => $this->getTimingDisplayText($daysRemaining, $isExpired),
                'urgency_color' => $this->getTimingUrgencyColor($daysRemaining, $isExpired),
            ],
            'stats' => [
                'donor_count' => $this->campaign->donations_count ?? 0,
                'donations_count' => $this->campaign->donations_count ?? 0, // Legacy field name
            ],
            'dates' => [
                'start_date' => $this->formatDate($this->campaign->start_date),
                'end_date' => $this->formatDate($this->campaign->end_date),
                'start_date_raw' => $this->campaign->start_date?->format('Y-m-d') ?? '',
                'end_date_raw' => $this->campaign->end_date?->format('Y-m-d') ?? '',
            ],
            'formatted' => [
                'goal_amount' => $this->formatMoney($this->campaign->goal_amount),
                'current_amount' => $this->formatMoney($this->campaign->current_amount),
                'remaining_amount' => $this->formatMoney($this->campaign->getRemainingAmount()),
                'progress_percentage' => $this->formatPercentage($this->campaign->getProgressPercentage()),
                'description_short' => $this->truncateText($this->campaign->getTranslation('description') ?? '', 120),
            ],
            'flags' => [
                'is_active' => $this->campaign->isActive(),
                'can_accept_donation' => $this->campaign->canAcceptDonation(),
                'has_reached_goal' => $this->campaign->hasReachedGoal(),
                'is_ending_soon' => $this->isEndingSoon(),
                'has_corporate_matching' => $this->campaign->has_corporate_matching,
                'is_featured' => $this->isFeatured(),
            ],
            'urls' => [
                'view' => route('campaigns.show', $this->campaign->id),
                'donate' => route('campaigns.donate', $this->campaign->id),
                'share' => $this->getShareUrl(),
            ],
            'organization' => $this->getOrganizationData(),
            'creator' => $this->getCreatorData(),
            'corporate_matching' => $this->getCorporateMatchingData(),
            'css_classes' => $this->getCssClasses(),
        ];
    }

    /**
     * Present campaign data specifically for dashboard display.
     *
     * @return array<string, mixed>
     */
    public function presentForDashboard(): array
    {
        $baseData = $this->present();

        return array_merge($baseData, [
            'dashboard_priority' => $this->getDashboardPriority(),
            'action_required' => $this->requiresAction(),
            'recent_donations_count' => $this->getRecentDonationsCount(),
            'momentum_score' => $this->getMomentumScore(),
        ]);
    }

    /**
     * Get progress data for visual components using new DonationProgress value object.
     *
     * @return array<string, mixed>
     */
    public function getProgressData(): array
    {
        $donationProgress = $this->getDonationProgress();
        $progressArray = $donationProgress->toArray();

        return array_merge($progressArray, [
            'percentage' => $this->campaign->getProgressPercentage(),
            'formatted_percentage' => $this->formatPercentage($this->campaign->getProgressPercentage()),
            'current_amount' => $this->campaign->current_amount,
            'goal_amount' => $this->campaign->goal_amount,
            'remaining_amount' => $this->campaign->getRemainingAmount(),
            'formatted_current' => $this->formatMoney($this->campaign->current_amount),
            'formatted_goal' => $this->formatMoney($this->campaign->goal_amount),
            'formatted_remaining' => $this->formatMoney($this->campaign->getRemainingAmount()),
            'progress_bar_class' => $this->getProgressBarClass(),
            'progress_text_class' => $this->getProgressTextClass(),
        ]);
    }

    /**
     * Get urgency indicator data.
     *
     * @return array<string, mixed>
     */
    public function getUrgencyData(): array
    {
        $daysRemaining = $this->campaign->getDaysRemaining();

        return [
            'days_remaining' => $daysRemaining,
            'urgency_level' => $this->getUrgencyLevel($daysRemaining),
            'urgency_class' => $this->getUrgencyClass($daysRemaining),
            'urgency_text' => $this->getUrgencyText($daysRemaining),
            'is_ending_soon' => $this->isEndingSoon(),
            'is_ending_today' => $daysRemaining === 0,
        ];
    }

    /**
     * Get DonationProgress value object for the campaign.
     */
    private function getDonationProgress(): DonationProgress
    {
        $raised = new Money((float) $this->campaign->current_amount, 'EUR');
        $goal = new Money((float) $this->campaign->goal_amount, 'EUR');

        // Calculate recent momentum (placeholder - would need actual implementation)
        $recentMomentum = null;

        if ($this->campaign->donations_count > 0) {
            // This would require actual database query for recent donations
            $recentMomentum = new Money($this->campaign->current_amount / max(1, $this->campaign->getDaysRemaining()), 'EUR');
        }

        return new DonationProgress(
            raised: $raised,
            goal: $goal,
            donorCount: $this->campaign->donations_count ?? 0,
            daysRemaining: $this->campaign->getDaysRemaining(),
            isActive: $this->campaign->isActive(),
            averageDonation: null, // Would need actual calculation
            largestDonation: null, // Would need actual calculation
            recentMomentum: $recentMomentum,
        );
    }

    /**
     * Check if campaign is ending soon (within 7 days).
     */
    private function isEndingSoon(): bool
    {
        return $this->campaign->isActive() && $this->campaign->getDaysRemaining() <= 7;
    }

    /**
     * Check if campaign is featured.
     */
    private function isFeatured(): bool
    {
        // Logic to determine if campaign is featured
        // This could be based on database field or business rules
        return $this->campaign->featured_image !== null;
    }

    /**
     * Get organization data for display.
     *
     * @return array<string, mixed>
     */
    private function getOrganizationData(): array
    {
        if ($this->campaign->organization === null) {
            return [];
        }

        return [
            'id' => $this->campaign->organization->id,
            'name' => $this->campaign->organization->getName(),
            'slug' => null,
        ];
    }

    /**
     * Get creator data for display.
     *
     * @return array<string, mixed>
     */
    private function getCreatorData(): array
    {
        if ($this->campaign->creator === null) {
            return [];
        }

        return [
            'id' => $this->campaign->creator->getId(),
            'name' => $this->campaign->creator->getName(),
            'avatar' => $this->campaign->creator->profile_photo_url ?? null,
        ];
    }

    /**
     * Get corporate matching data.
     *
     * @return array<string, mixed>
     */
    private function getCorporateMatchingData(): array
    {
        if (! $this->campaign->has_corporate_matching) {
            return [
                'enabled' => false,
            ];
        }

        return [
            'enabled' => true,
            'rate' => $this->campaign->corporate_matching_rate,
            'max_amount' => $this->campaign->max_corporate_matching,
            'formatted_rate' => $this->formatPercentage(((float) $this->campaign->corporate_matching_rate) * 100, 0),
            'formatted_max' => $this->formatMoney((float) $this->campaign->max_corporate_matching),
        ];
    }

    /**
     * Get CSS classes for campaign card.
     *
     * @return array<string, mixed>
     */
    private function getCssClasses(): array
    {
        return [
            'card' => $this->generateClasses([
                'campaign-card',
                $this->conditionalClass($this->campaign->hasReachedGoal(), 'campaign-card--completed'),
                $this->conditionalClass($this->isEndingSoon(), 'campaign-card--urgent'),
                $this->conditionalClass($this->isFeatured(), 'campaign-card--featured'),
                $this->conditionalClass(! $this->campaign->isActive(), 'campaign-card--inactive'),
            ]),
            'status' => $this->generateClasses([
                'campaign-status',
                'campaign-status--' . $this->campaign->status->value,
            ]),
            'progress_bar' => $this->getProgressBarClass(),
            'urgency' => $this->getUrgencyClass($this->campaign->getDaysRemaining()),
        ];
    }

    /**
     * Get progress bar CSS class based on percentage.
     */
    private function getProgressBarClass(): string
    {
        $percentage = $this->campaign->getProgressPercentage();

        if ($percentage >= 100) {
            return 'progress-bar--completed';
        }

        if ($percentage >= 75) {
            return 'progress-bar--high';
        }

        if ($percentage >= 50) {
            return 'progress-bar--medium';
        }

        if ($percentage >= 25) {
            return 'progress-bar--low';
        }

        return 'progress-bar--minimal';
    }

    /**
     * Get progress text CSS class.
     */
    private function getProgressTextClass(): string
    {
        if ($this->campaign->hasReachedGoal()) {
            return 'progress-text--completed';
        }

        if ($this->isEndingSoon()) {
            return 'progress-text--urgent';
        }

        return 'progress-text--normal';
    }

    /**
     * Get urgency level (1-5 scale).
     */
    private function getUrgencyLevel(int $daysRemaining): int
    {
        if ($daysRemaining === 0) {
            return 5; // Critical
        }

        if ($daysRemaining <= 3) {
            return 4; // High
        }

        if ($daysRemaining <= 7) {
            return 3; // Medium
        }

        if ($daysRemaining <= 14) {
            return 2; // Low
        }

        return 1; // Normal
    }

    /**
     * Get urgency CSS class.
     */
    private function getUrgencyClass(int $daysRemaining): string
    {
        $level = $this->getUrgencyLevel($daysRemaining);

        return match ($level) {
            5 => 'urgency--critical',
            4 => 'urgency--high',
            3 => 'urgency--medium',
            2 => 'urgency--low',
            default => 'urgency--normal',
        };
    }

    /**
     * Get urgency text message.
     */
    private function getUrgencyText(int $daysRemaining): string
    {
        if ($daysRemaining === 0) {
            return 'Ends today';
        }

        if ($daysRemaining === 1) {
            return '1 day left';
        }

        if ($daysRemaining <= 7) {
            return $daysRemaining . ' days left';
        }

        return $daysRemaining . ' days remaining';
    }

    /**
     * Get share URL for campaign.
     */
    private function getShareUrl(): string
    {
        return route('campaigns.show', $this->campaign->id);
    }

    /**
     * Get dashboard priority score (for sorting).
     */
    private function getDashboardPriority(): int
    {
        $score = 0;

        // Higher priority for ending soon
        if ($this->isEndingSoon()) {
            $score += 100;
        }

        // Higher priority for active campaigns
        if ($this->campaign->isActive()) {
            $score += 50;
        }

        // Higher priority for campaigns close to goal
        if ($this->campaign->getProgressPercentage() > 80) {
            $score += 30;
        }

        // Higher priority for featured campaigns
        if ($this->isFeatured()) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Check if campaign requires action from user.
     */
    private function requiresAction(): bool
    {
        // Campaign needs action if it's ending soon and not completed
        return $this->isEndingSoon() && ! $this->campaign->hasReachedGoal();
    }

    /**
     * Get recent donations count (placeholder - would need actual implementation).
     */
    private function getRecentDonationsCount(): int
    {
        // This would require actual database query to count recent donations
        return 0;
    }

    /**
     * Calculate momentum score based on recent activity.
     */
    private function getMomentumScore(): float
    {
        // Simple momentum calculation based on progress and time remaining
        $progress = $this->campaign->getProgressPercentage();
        $daysRemaining = max(1, $this->campaign->getDaysRemaining());

        return round($progress / $daysRemaining, 2);
    }

    /**
     * Highlight search terms in text.
     */
    private function highlightText(string $text): string
    {
        if (! $this->searchTerm || strlen($this->searchTerm) < 2) {
            return $text;
        }

        // Escape special regex characters in search term
        $escapedTerm = preg_quote($this->searchTerm, '/');

        // Apply highlighting with case-insensitive matching
        $result = preg_replace(
            '/(' . $escapedTerm . ')/iu',
            '<mark class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded text-gray-900 dark:text-yellow-100">$1</mark>',
            $text,
        );

        return $result ?? $text;
    }

    /**
     * Get status color class for Tailwind CSS.
     */
    private function getStatusColorClass(): string
    {
        return match ($this->campaign->status->value) {
            'active' => 'bg-secondary text-white',
            'paused' => 'bg-yellow-500 text-white',
            'completed' => 'bg-primary text-white',
            'draft' => 'bg-gray-500 text-white',
            'cancelled' => 'bg-red-500 text-white',
            'expired' => 'bg-red-600 text-white',
            'pending_approval' => 'bg-yellow-600 text-white',
            'rejected' => 'bg-red-700 text-white',
        };
    }

    /**
     * Get timing display text for campaign.
     */
    private function getTimingDisplayText(int $daysRemaining, bool $isExpired): string
    {
        if ($isExpired) {
            return 'Expired';
        }

        if ($daysRemaining === 0) {
            return 'Last day!';
        }

        return $daysRemaining . ' days left';
    }

    /**
     * Get timing urgency color classes.
     */
    private function getTimingUrgencyColor(int $daysRemaining, bool $isExpired): string
    {
        if ($isExpired) {
            return 'text-red-600 dark:text-red-400';
        }

        if ($daysRemaining === 0) {
            return 'text-orange-600 dark:text-orange-400';
        }

        if ($daysRemaining <= 7) {
            return 'text-orange-600 dark:text-orange-400';
        }

        return '';
    }
}

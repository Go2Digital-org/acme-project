<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Campaign read model optimized for campaign details, status, and progress.
 */
final class CampaignReadModel extends AbstractReadModel
{
    public function __construct(
        /**
         * @return array<string>
         */
        int $campaignId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($campaignId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for campaign data
    }

    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign',
            'campaign:' . $this->id,
            'organization:' . $this->getOrganizationId(),
            'status:' . $this->getStatus(),
        ]);
    }

    // Basic Campaign Information
    public function getCampaignId(): int
    {
        return (int) $this->id;
    }

    public function getTitle(): string
    {
        return $this->get('title', '');
    }

    public function getDescription(): string
    {
        return $this->get('description', '');
    }

    public function getSlug(): string
    {
        return $this->get('slug', '');
    }

    public function getImageUrl(): ?string
    {
        return $this->get('image_url');
    }

    public function getVideoUrl(): ?string
    {
        return $this->get('video_url');
    }

    /**
     * @return array<string, mixed>
     */
    public function getTags(): array
    {
        return $this->get('tags', []);
    }

    // Financial Information
    public function getTargetAmount(): float
    {
        return (float) $this->get('target_amount', 0);
    }

    public function getCurrentAmount(): float
    {
        return (float) $this->get('current_amount', 0);
    }

    public function getCurrency(): string
    {
        return $this->get('currency', 'USD');
    }

    public function getProgressPercentage(): float
    {
        $target = $this->getTargetAmount();
        if ($target <= 0) {
            return 0.0;
        }

        return min(100, ($this->getCurrentAmount() / $target) * 100);
    }

    public function getRemainingAmount(): float
    {
        $remaining = $this->getTargetAmount() - $this->getCurrentAmount();

        return max(0, $remaining);
    }

    public function isTargetReached(): bool
    {
        return $this->getCurrentAmount() >= $this->getTargetAmount();
    }

    // Timeline Information
    public function getStartDate(): ?string
    {
        return $this->get('start_date');
    }

    public function getEndDate(): ?string
    {
        return $this->get('end_date');
    }

    public function getDurationInDays(): int
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if (! $start || ! $end) {
            return 0;
        }

        $startTime = strtotime($start);
        $endTime = strtotime($end);

        return (int) (($endTime - $startTime) / (60 * 60 * 24));
    }

    public function getRemainingDays(): int
    {
        $end = $this->getEndDate();
        if (! $end) {
            return 0;
        }

        $endTime = strtotime($end);
        $remaining = (int) (($endTime - time()) / (60 * 60 * 24));

        return max(0, $remaining);
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active' && $this->getRemainingDays() > 0;
    }

    public function isExpired(): bool
    {
        return $this->getRemainingDays() <= 0 && $this->getStatus() !== 'completed';
    }

    // Status Information
    public function getStatus(): string
    {
        return $this->get('status', 'draft');
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->getStatus() === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->getStatus() === 'cancelled';
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === 'rejected';
    }

    // Organization Information
    public function getOrganizationId(): int
    {
        return (int) $this->get('organization_id', 0);
    }

    public function getOrganizationName(): ?string
    {
        return $this->get('organization_name');
    }

    public function getOrganizationType(): ?string
    {
        return $this->get('organization_type');
    }

    // Creator Information
    public function getCreatedById(): int
    {
        return (int) $this->get('created_by_id', 0);
    }

    public function getCreatedByName(): ?string
    {
        return $this->get('created_by_name');
    }

    public function getCreatedByEmail(): ?string
    {
        return $this->get('created_by_email');
    }

    // Statistics
    public function getDonationCount(): int
    {
        return (int) $this->get('donation_count', 0);
    }

    public function getUniqueDonatorsCount(): int
    {
        return (int) $this->get('unique_donators_count', 0);
    }

    public function getAverageDonationAmount(): float
    {
        $count = $this->getDonationCount();
        if ($count <= 0) {
            return 0.0;
        }

        return $this->getCurrentAmount() / $count;
    }

    public function getLastDonationAt(): ?string
    {
        return $this->get('last_donation_at');
    }

    public function getViewCount(): int
    {
        return (int) $this->get('view_count', 0);
    }

    public function getShareCount(): int
    {
        return (int) $this->get('share_count', 0);
    }

    public function getBookmarkCount(): int
    {
        return (int) $this->get('bookmark_count', 0);
    }

    // Category Information
    public function getCategoryId(): ?int
    {
        $catId = $this->get('category_id');

        return $catId ? (int) $catId : null;
    }

    public function getCategoryName(): ?string
    {
        return $this->get('category_name');
    }

    public function getCategorySlug(): ?string
    {
        return $this->get('category_slug');
    }

    // Approval Information
    public function getApprovedAt(): ?string
    {
        return $this->get('approved_at');
    }

    public function getApprovedById(): ?int
    {
        $approverId = $this->get('approved_by_id');

        return $approverId ? (int) $approverId : null;
    }

    public function getApprovedByName(): ?string
    {
        return $this->get('approved_by_name');
    }

    public function getRejectionReason(): ?string
    {
        return $this->get('rejection_reason');
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

    public function getPublishedAt(): ?string
    {
        return $this->get('published_at');
    }

    public function getCompletedAt(): ?string
    {
        return $this->get('completed_at');
    }

    // Featured and Priority
    public function isFeatured(): bool
    {
        return $this->get('is_featured', false);
    }

    public function getPriority(): int
    {
        return (int) $this->get('priority', 0);
    }

    public function isUrgent(): bool
    {
        return $this->getPriority() >= 5;
    }

    // Social Sharing
    public function getShareUrl(): string
    {
        $slug = $this->getSlug();
        if ($slug !== '' && $slug !== '0') {
            return url("/campaigns/{$slug}");
        }

        return url("/campaigns/{$this->getCampaignId()}");
    }

    public function getTwitterShareText(): string
    {
        $title = $this->getTitle();
        $progress = number_format($this->getProgressPercentage(), 1);
        $url = $this->getShareUrl();

        return "Support '{$title}' - {$progress}% funded! {$url} #CharityCampaign";
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getCampaignId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'slug' => $this->getSlug(),
            'image_url' => $this->getImageUrl(),
            'video_url' => $this->getVideoUrl(),
            'tags' => $this->getTags(),
            'financial' => [
                'target_amount' => $this->getTargetAmount(),
                'current_amount' => $this->getCurrentAmount(),
                'currency' => $this->getCurrency(),
                'progress_percentage' => $this->getProgressPercentage(),
                'remaining_amount' => $this->getRemainingAmount(),
                'is_target_reached' => $this->isTargetReached(),
            ],
            'timeline' => [
                'start_date' => $this->getStartDate(),
                'end_date' => $this->getEndDate(),
                'duration_days' => $this->getDurationInDays(),
                'remaining_days' => $this->getRemainingDays(),
                'is_active' => $this->isActive(),
                'is_expired' => $this->isExpired(),
            ],
            'status' => [
                'status' => $this->getStatus(),
                'status_label' => $this->getStatusLabel(),
                'is_draft' => $this->isDraft(),
                'is_pending_approval' => $this->isPendingApproval(),
                'is_approved' => $this->isApproved(),
                'is_active' => $this->isActive(),
                'is_completed' => $this->isCompleted(),
                'is_cancelled' => $this->isCancelled(),
                'is_rejected' => $this->isRejected(),
            ],
            'organization' => [
                'id' => $this->getOrganizationId(),
                'name' => $this->getOrganizationName(),
                'type' => $this->getOrganizationType(),
            ],
            'creator' => [
                'id' => $this->getCreatedById(),
                'name' => $this->getCreatedByName(),
                'email' => $this->getCreatedByEmail(),
            ],
            'statistics' => [
                'donation_count' => $this->getDonationCount(),
                'unique_donators_count' => $this->getUniqueDonatorsCount(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'last_donation_at' => $this->getLastDonationAt(),
                'view_count' => $this->getViewCount(),
                'share_count' => $this->getShareCount(),
                'bookmark_count' => $this->getBookmarkCount(),
            ],
            'category' => [
                'id' => $this->getCategoryId(),
                'name' => $this->getCategoryName(),
                'slug' => $this->getCategorySlug(),
            ],
            'approval' => [
                'approved_at' => $this->getApprovedAt(),
                'approved_by_id' => $this->getApprovedById(),
                'approved_by_name' => $this->getApprovedByName(),
                'rejection_reason' => $this->getRejectionReason(),
            ],
            'metadata' => [
                'is_featured' => $this->isFeatured(),
                'priority' => $this->getPriority(),
                'is_urgent' => $this->isUrgent(),
                'share_url' => $this->getShareUrl(),
            ],
            'timestamps' => [
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
                'published_at' => $this->getPublishedAt(),
                'completed_at' => $this->getCompletedAt(),
            ],
        ];
    }

    /**
     * Get summary data optimized for lists and cards
     */
    /**
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getCampaignId(),
            'title' => $this->getTitle(),
            'slug' => $this->getSlug(),
            'image_url' => $this->getImageUrl(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
            'target_amount' => $this->getTargetAmount(),
            'current_amount' => $this->getCurrentAmount(),
            'currency' => $this->getCurrency(),
            'progress_percentage' => $this->getProgressPercentage(),
            'remaining_days' => $this->getRemainingDays(),
            'is_active' => $this->isActive(),
            'is_featured' => $this->isFeatured(),
            'is_urgent' => $this->isUrgent(),
            'organization_name' => $this->getOrganizationName(),
            'category_name' => $this->getCategoryName(),
            'donation_count' => $this->getDonationCount(),
            'unique_donators_count' => $this->getUniqueDonatorsCount(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}

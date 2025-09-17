<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Specification;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a campaign can be approved.
 *
 * A campaign can be approved when:
 * - It is in pending approval status
 * - It has valid basic information (title, description)
 * - It has a valid goal amount (positive number)
 * - It has valid date range (start before end, end in future)
 * - It belongs to an active and verified organization
 * - It has not been previously approved or rejected
 */
class CampaignApprovalSpecification extends CompositeSpecification
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        // This specification only works with Campaign concrete class due to property access requirements
        if (! ($candidate instanceof Campaign)) {
            return false;
        }

        // Campaign must be in pending approval status
        if ($candidate->status !== CampaignStatus::PENDING_APPROVAL) {
            return false;
        }

        // Must not be previously approved or rejected
        if ($candidate->approved_at !== null || $candidate->rejected_at !== null) {
            return false;
        }

        // Must have valid title
        $title = $candidate->getTitle();
        if (in_array(trim($title), ['', '0'], true) || $title === 'Untitled') {
            return false;
        }

        // Must have description
        $description = $candidate->getDescription();
        if (in_array(trim($description ?? ''), ['', '0'], true)) {
            return false;
        }

        // Must have valid goal amount
        if ($candidate->goal_amount <= 0) {
            return false;
        }

        // Must have valid date range
        if ($candidate->start_date === null || $candidate->end_date === null) {
            return false;
        }

        if ($candidate->start_date->isAfter($candidate->end_date)) {
            return false;
        }

        // End date must be in the future
        if (! $candidate->end_date->isFuture()) {
            return false;
        }

        // Organization must exist and be loaded
        if (! $candidate->relationLoaded('organization') || $candidate->organization === null) {
            $candidate->load('organization');
            if ($candidate->organization === null) {
                return false;
            }
        }

        // Organization must be active and verified to create approved campaigns
        if (! $candidate->organization->is_active || ! $candidate->organization->is_verified) {
            return false;
        }

        // Campaign must have a category (either legacy string or category model)
        return ! (empty($candidate->category) && $candidate->category_id === null);
    }
}

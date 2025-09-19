<?php

declare(strict_types=1);

namespace Modules\Organization\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Organization read model optimized for organization details, status, and profile information.
 */
final class OrganizationReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $organizationId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($organizationId, $data, $version);
        $this->setCacheTtl(1800); // 30 minutes for organization data
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'organization',
            'organization:' . $this->id,
            'status:' . $this->getStatus(),
        ]);
    }

    // Basic Organization Information
    public function getOrganizationId(): int
    {
        return (int) $this->id;
    }

    public function getName(): string
    {
        return $this->get('name', '');
    }

    public function getSlug(): string
    {
        return $this->get('slug', '');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getWebsite(): ?string
    {
        return $this->get('website');
    }

    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    public function getPhone(): ?string
    {
        return $this->get('phone');
    }

    public function getLogoUrl(): ?string
    {
        return $this->get('logo_url');
    }

    public function getBannerUrl(): ?string
    {
        return $this->get('banner_url');
    }

    // Organization Type and Category
    public function getType(): string
    {
        return $this->get('type', 'nonprofit');
    }

    public function getTypeLabel(): string
    {
        return match ($this->getType()) {
            'nonprofit' => 'Non-Profit',
            'charity' => 'Charity',
            'foundation' => 'Foundation',
            'religious' => 'Religious Organization',
            'educational' => 'Educational Institution',
            'healthcare' => 'Healthcare Organization',
            'environmental' => 'Environmental Organization',
            'community' => 'Community Organization',
            'corporate' => 'Corporate Social Responsibility',
            default => 'Organization',
        };
    }

    public function getCategory(): ?string
    {
        return $this->get('category');
    }

    public function getCategoryLabel(): ?string
    {
        $category = $this->getCategory();
        if (! $category) {
            return null;
        }

        return match ($category) {
            'health' => 'Health & Medical',
            'education' => 'Education',
            'environment' => 'Environment',
            'animals' => 'Animal Welfare',
            'arts' => 'Arts & Culture',
            'community' => 'Community Development',
            'disaster_relief' => 'Disaster Relief',
            'human_rights' => 'Human Rights',
            'poverty' => 'Poverty & Homelessness',
            'children' => 'Children & Youth',
            'elderly' => 'Senior Services',
            'veterans' => 'Veterans Affairs',
            'disability' => 'Disability Services',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    // Address Information
    public function getAddress(): ?string
    {
        return $this->get('address');
    }

    public function getCity(): ?string
    {
        return $this->get('city');
    }

    public function getState(): ?string
    {
        return $this->get('state');
    }

    public function getCountry(): ?string
    {
        return $this->get('country');
    }

    public function getPostalCode(): ?string
    {
        return $this->get('postal_code');
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->getAddress(),
            $this->getCity(),
            $this->getState(),
            $this->getPostalCode(),
            $this->getCountry(),
        ]);

        return implode(', ', $parts);
    }

    // Status and Verification
    public function getStatus(): string
    {
        return $this->get('status', 'pending');
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'pending' => 'Pending Verification',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'inactive' => 'Inactive',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->getStatus() === 'suspended';
    }

    public function isInactive(): bool
    {
        return $this->getStatus() === 'inactive';
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === 'rejected';
    }

    public function isVerified(): bool
    {
        return $this->get('is_verified', false);
    }

    public function getVerifiedAt(): ?string
    {
        return $this->get('verified_at');
    }

    public function getVerificationStatus(): string
    {
        if ($this->isVerified()) {
            return 'verified';
        }

        return $this->isPending() ? 'pending' : 'unverified';
    }

    // Tax Status
    public function isTaxExempt(): bool
    {
        return $this->get('is_tax_exempt', false);
    }

    public function getTaxId(): ?string
    {
        return $this->get('tax_id');
    }

    public function getTaxExemptNumber(): ?string
    {
        return $this->get('tax_exempt_number');
    }

    // Settings and Preferences
    public function allowsPublicDonations(): bool
    {
        return $this->get('allows_public_donations', true);
    }

    public function allowsAnonymousDonations(): bool
    {
        return $this->get('allows_anonymous_donations', true);
    }

    public function requiresDonorApproval(): bool
    {
        return $this->get('requires_donor_approval', false);
    }

    public function getMinDonationAmount(): float
    {
        return (float) $this->get('min_donation_amount', 0);
    }

    public function getMaxDonationAmount(): ?float
    {
        $max = $this->get('max_donation_amount');

        return $max ? (float) $max : null;
    }

    // Statistics
    public function getTotalCampaigns(): int
    {
        return (int) $this->get('total_campaigns', 0);
    }

    public function getActiveCampaigns(): int
    {
        return (int) $this->get('active_campaigns', 0);
    }

    public function getCompletedCampaigns(): int
    {
        return (int) $this->get('completed_campaigns', 0);
    }

    public function getTotalAmountRaised(): float
    {
        return (float) $this->get('total_amount_raised', 0);
    }

    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getUniqueDonors(): int
    {
        return (int) $this->get('unique_donors', 0);
    }

    public function getTotalMembers(): int
    {
        return (int) $this->get('total_members', 0);
    }

    public function getActiveMembers(): int
    {
        return (int) $this->get('active_members', 0);
    }

    public function getAdminMembers(): int
    {
        return (int) $this->get('admin_members', 0);
    }

    // Performance Metrics
    public function getAverageAmountPerCampaign(): float
    {
        $campaigns = $this->getTotalCampaigns();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $campaigns;
    }

    public function getAverageDonationAmount(): float
    {
        $donations = $this->getTotalDonations();
        if ($donations <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $donations;
    }

    public function getCampaignSuccessRate(): float
    {
        $total = $this->getTotalCampaigns();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getCompletedCampaigns() / $total) * 100;
    }

    public function getRetentionRate(): float
    {
        $total = $this->getTotalMembers();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getActiveMembers() / $total) * 100;
    }

    // Social Media and Links
    public function getFacebookUrl(): ?string
    {
        return $this->get('facebook_url');
    }

    public function getTwitterUrl(): ?string
    {
        return $this->get('twitter_url');
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->get('linkedin_url');
    }

    public function getInstagramUrl(): ?string
    {
        return $this->get('instagram_url');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSocialMediaLinks(): array
    {
        return array_filter([
            'facebook' => $this->getFacebookUrl(),
            'twitter' => $this->getTwitterUrl(),
            'linkedin' => $this->getLinkedinUrl(),
            'instagram' => $this->getInstagramUrl(),
        ]);
    }

    public function hasSocialMediaLinks(): bool
    {
        return $this->getSocialMediaLinks() !== [];
    }

    // Features and Capabilities
    public function supportsCorporateMatching(): bool
    {
        return $this->get('supports_corporate_matching', false);
    }

    public function supportsRecurringDonations(): bool
    {
        return $this->get('supports_recurring_donations', true);
    }

    public function supportsVolunteerPrograms(): bool
    {
        return $this->get('supports_volunteer_programs', false);
    }

    public function hasMatchingProgram(): bool
    {
        return $this->get('has_matching_program', false);
    }

    public function getMatchingRatio(): ?float
    {
        $ratio = $this->get('matching_ratio');

        return $ratio ? (float) $ratio : null;
    }

    public function getMatchingCap(): ?float
    {
        $cap = $this->get('matching_cap');

        return $cap ? (float) $cap : null;
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

    public function getFoundedDate(): ?string
    {
        return $this->get('founded_date');
    }

    public function getRegistrationDate(): ?string
    {
        return $this->get('registration_date');
    }

    public function getLastActivityAt(): ?string
    {
        return $this->get('last_activity_at');
    }

    // Time Calculations
    public function getAge(): int
    {
        $created = $this->getCreatedAt();
        if (! $created) {
            return 0;
        }

        return (int) ((time() - strtotime($created)) / (60 * 60 * 24)); // Days
    }

    public function getYearsSinceFoundation(): int
    {
        $founded = $this->getFoundedDate();
        if (! $founded) {
            return 0;
        }

        return (int) ((time() - strtotime($founded)) / (60 * 60 * 24 * 365)); // Years
    }

    public function getDaysSinceLastActivity(): int
    {
        $lastActivity = $this->getLastActivityAt();
        if (! $lastActivity) {
            return 0;
        }

        return (int) ((time() - strtotime($lastActivity)) / (60 * 60 * 24));
    }

    // Display Helpers
    public function getDisplayName(): string
    {
        return $this->getName();
    }

    public function getShortDescription(int $length = 150): string
    {
        $description = $this->getDescription();
        if (! $description) {
            return '';
        }

        return strlen($description) > $length ? substr($description, 0, $length) . '...' : $description;
    }

    public function getProfileUrl(): string
    {
        $slug = $this->getSlug();
        if ($slug !== '' && $slug !== '0') {
            return url("/organizations/{$slug}");
        }

        return url("/organizations/{$this->getOrganizationId()}");
    }

    public function getLogoUrlOrDefault(): string
    {
        return $this->getLogoUrl() ?? url('/images/default-organization-logo.png');
    }

    // Badge and Recognition
    public function hasVerificationBadge(): bool
    {
        return $this->isVerified() && $this->isActive();
    }

    public function isFeatured(): bool
    {
        return $this->get('is_featured', false);
    }

    public function getFeaturedRank(): ?int
    {
        $rank = $this->get('featured_rank');

        return $rank ? (int) $rank : null;
    }

    // Health and Status Indicators
    public function isHealthy(): bool
    {
        return $this->isActive() &&
               $this->getActiveCampaigns() > 0 &&
               $this->getDaysSinceLastActivity() < 30;
    }

    public function needsAttention(): bool
    {
        return $this->isActive() && (
            $this->getActiveCampaigns() === 0 ||
            $this->getDaysSinceLastActivity() > 30 ||
            $this->getActiveMembers() === 0
        );
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getOrganizationId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'description' => $this->getDescription(),
            'short_description' => $this->getShortDescription(),
            'display_name' => $this->getDisplayName(),
            'website' => $this->getWebsite(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'logo_url' => $this->getLogoUrl(),
            'logo_url_or_default' => $this->getLogoUrlOrDefault(),
            'banner_url' => $this->getBannerUrl(),
            'profile_url' => $this->getProfileUrl(),
            'type' => $this->getType(),
            'type_label' => $this->getTypeLabel(),
            'category' => $this->getCategory(),
            'category_label' => $this->getCategoryLabel(),
            'address' => [
                'address' => $this->getAddress(),
                'city' => $this->getCity(),
                'state' => $this->getState(),
                'country' => $this->getCountry(),
                'postal_code' => $this->getPostalCode(),
                'full_address' => $this->getFullAddress(),
            ],
            'status' => [
                'status' => $this->getStatus(),
                'status_label' => $this->getStatusLabel(),
                'is_pending' => $this->isPending(),
                'is_active' => $this->isActive(),
                'is_suspended' => $this->isSuspended(),
                'is_inactive' => $this->isInactive(),
                'is_rejected' => $this->isRejected(),
            ],
            'verification' => [
                'is_verified' => $this->isVerified(),
                'verified_at' => $this->getVerifiedAt(),
                'verification_status' => $this->getVerificationStatus(),
                'has_verification_badge' => $this->hasVerificationBadge(),
            ],
            'tax' => [
                'is_tax_exempt' => $this->isTaxExempt(),
                'tax_id' => $this->getTaxId(),
                'tax_exempt_number' => $this->getTaxExemptNumber(),
            ],
            'settings' => [
                'allows_public_donations' => $this->allowsPublicDonations(),
                'allows_anonymous_donations' => $this->allowsAnonymousDonations(),
                'requires_donor_approval' => $this->requiresDonorApproval(),
                'min_donation_amount' => $this->getMinDonationAmount(),
                'max_donation_amount' => $this->getMaxDonationAmount(),
            ],
            'statistics' => [
                'total_campaigns' => $this->getTotalCampaigns(),
                'active_campaigns' => $this->getActiveCampaigns(),
                'completed_campaigns' => $this->getCompletedCampaigns(),
                'total_amount_raised' => $this->getTotalAmountRaised(),
                'total_donations' => $this->getTotalDonations(),
                'unique_donors' => $this->getUniqueDonors(),
                'total_members' => $this->getTotalMembers(),
                'active_members' => $this->getActiveMembers(),
                'admin_members' => $this->getAdminMembers(),
            ],
            'metrics' => [
                'average_amount_per_campaign' => $this->getAverageAmountPerCampaign(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'campaign_success_rate' => $this->getCampaignSuccessRate(),
                'retention_rate' => $this->getRetentionRate(),
            ],
            'social_media' => $this->getSocialMediaLinks(),
            'features' => [
                'supports_corporate_matching' => $this->supportsCorporateMatching(),
                'supports_recurring_donations' => $this->supportsRecurringDonations(),
                'supports_volunteer_programs' => $this->supportsVolunteerPrograms(),
                'has_matching_program' => $this->hasMatchingProgram(),
                'matching_ratio' => $this->getMatchingRatio(),
                'matching_cap' => $this->getMatchingCap(),
            ],
            'display' => [
                'is_featured' => $this->isFeatured(),
                'featured_rank' => $this->getFeaturedRank(),
                'is_healthy' => $this->isHealthy(),
                'needs_attention' => $this->needsAttention(),
            ],
            'timing' => [
                'age_days' => $this->getAge(),
                'years_since_foundation' => $this->getYearsSinceFoundation(),
                'days_since_last_activity' => $this->getDaysSinceLastActivity(),
            ],
            'timestamps' => [
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
                'founded_date' => $this->getFoundedDate(),
                'registration_date' => $this->getRegistrationDate(),
                'last_activity_at' => $this->getLastActivityAt(),
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
            'id' => $this->getOrganizationId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'short_description' => $this->getShortDescription(),
            'logo_url' => $this->getLogoUrlOrDefault(),
            'type' => $this->getType(),
            'type_label' => $this->getTypeLabel(),
            'category' => $this->getCategory(),
            'category_label' => $this->getCategoryLabel(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
            'is_verified' => $this->isVerified(),
            'is_featured' => $this->isFeatured(),
            'total_campaigns' => $this->getTotalCampaigns(),
            'active_campaigns' => $this->getActiveCampaigns(),
            'total_amount_raised' => $this->getTotalAmountRaised(),
            'profile_url' => $this->getProfileUrl(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'country' => $this->getCountry(),
        ];
    }

    /**
     * Get data optimized for public display
     */
    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->getOrganizationId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'description' => $this->getDescription(),
            'website' => $this->getWebsite(),
            'logo_url' => $this->getLogoUrlOrDefault(),
            'banner_url' => $this->getBannerUrl(),
            'type_label' => $this->getTypeLabel(),
            'category_label' => $this->getCategoryLabel(),
            'is_verified' => $this->isVerified(),
            'is_tax_exempt' => $this->isTaxExempt(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'country' => $this->getCountry(),
            'social_media' => $this->getSocialMediaLinks(),
            'statistics' => [
                'total_campaigns' => $this->getTotalCampaigns(),
                'active_campaigns' => $this->getActiveCampaigns(),
                'completed_campaigns' => $this->getCompletedCampaigns(),
                'total_amount_raised' => $this->getTotalAmountRaised(),
            ],
            'features' => [
                'supports_recurring_donations' => $this->supportsRecurringDonations(),
                'supports_corporate_matching' => $this->supportsCorporateMatching(),
                'has_matching_program' => $this->hasMatchingProgram(),
            ],
            'founded_date' => $this->getFoundedDate(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Organization profile read model optimized for detailed organization profiles and admin views.
 */
final class OrganizationProfileReadModel extends AbstractReadModel
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
        $this->setCacheTtl(3600); // 1 hour for detailed profile data
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'organization_profile',
            'organization:' . $this->id,
            'detailed_profile',
        ]);
    }

    // Basic Profile Information
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

    public function getTagline(): ?string
    {
        return $this->get('tagline');
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getMission(): ?string
    {
        return $this->get('mission');
    }

    public function getVision(): ?string
    {
        return $this->get('vision');
    }

    public function getValues(): ?string
    {
        return $this->get('values');
    }

    public function getHistory(): ?string
    {
        return $this->get('history');
    }

    // Contact Information
    public function getPrimaryEmail(): ?string
    {
        return $this->get('primary_email');
    }

    public function getSecondaryEmail(): ?string
    {
        return $this->get('secondary_email');
    }

    public function getPrimaryPhone(): ?string
    {
        return $this->get('primary_phone');
    }

    public function getSecondaryPhone(): ?string
    {
        return $this->get('secondary_phone');
    }

    public function getFax(): ?string
    {
        return $this->get('fax');
    }

    public function getWebsite(): ?string
    {
        return $this->get('website');
    }

    public function getBlog(): ?string
    {
        return $this->get('blog');
    }

    // Media and Branding
    public function getLogoUrl(): ?string
    {
        return $this->get('logo_url');
    }

    public function getBannerUrl(): ?string
    {
        return $this->get('banner_url');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGalleryImages(): array
    {
        return $this->get('gallery_images', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVideos(): array
    {
        return $this->get('videos', []);
    }

    public function hasGallery(): bool
    {
        return $this->getGalleryImages() !== [];
    }

    public function hasVideos(): bool
    {
        return $this->getVideos() !== [];
    }

    // Address and Location
    /**
     * @return array<string, string|null>
     */
    public function getHeadquartersAddress(): array
    {
        return [
            'address' => $this->get('headquarters_address'),
            'address_2' => $this->get('headquarters_address_2'),
            'city' => $this->get('headquarters_city'),
            'state' => $this->get('headquarters_state'),
            'postal_code' => $this->get('headquarters_postal_code'),
            'country' => $this->get('headquarters_country'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function getMailingAddress(): array
    {
        return [
            'address' => $this->get('mailing_address'),
            'address_2' => $this->get('mailing_address_2'),
            'city' => $this->get('mailing_city'),
            'state' => $this->get('mailing_state'),
            'postal_code' => $this->get('mailing_postal_code'),
            'country' => $this->get('mailing_country'),
        ];
    }

    public function hasMailingAddress(): bool
    {
        $mailing = $this->getMailingAddress();

        return ! empty($mailing['address']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBranches(): array
    {
        return $this->get('branches', []);
    }

    public function hasBranches(): bool
    {
        return $this->getBranches() !== [];
    }

    // Legal and Registration Information
    public function getLegalName(): ?string
    {
        return $this->get('legal_name');
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->get('registration_number');
    }

    public function getTaxId(): ?string
    {
        return $this->get('tax_id');
    }

    public function getTaxExemptNumber(): ?string
    {
        return $this->get('tax_exempt_number');
    }

    public function getIncorporationDate(): ?string
    {
        return $this->get('incorporation_date');
    }

    public function getFoundedDate(): ?string
    {
        return $this->get('founded_date');
    }

    public function getLegalStructure(): ?string
    {
        return $this->get('legal_structure');
    }

    // Leadership and Staff
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getExecutiveTeam(): array
    {
        return $this->get('executive_team', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBoardMembers(): array
    {
        return $this->get('board_members', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getKeyStaff(): array
    {
        return $this->get('key_staff', []);
    }

    public function hasExecutiveTeam(): bool
    {
        return $this->getExecutiveTeam() !== [];
    }

    public function hasBoardMembers(): bool
    {
        return $this->getBoardMembers() !== [];
    }

    // Financial Information
    public function getAnnualBudget(): ?float
    {
        $budget = $this->get('annual_budget');

        return $budget ? (float) $budget : null;
    }

    public function getLastFiscalYear(): ?string
    {
        return $this->get('last_fiscal_year');
    }

    /**
     * @return array<string, mixed>
     */
    public function getFinancialReports(): array
    {
        return $this->get('financial_reports', []);
    }

    public function hasFinancialReports(): bool
    {
        return $this->getFinancialReports() !== [];
    }

    // Programs and Services
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPrograms(): array
    {
        return $this->get('programs', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getServices(): array
    {
        return $this->get('services', []);
    }

    public function hasPrograms(): bool
    {
        return $this->getPrograms() !== [];
    }

    public function hasServices(): bool
    {
        return $this->getServices() !== [];
    }

    // Impact and Statistics
    public function getBeneficiariesServed(): ?int
    {
        $beneficiaries = $this->get('beneficiaries_served');

        return $beneficiaries ? (int) $beneficiaries : null;
    }

    public function getVolunteersCount(): int
    {
        return (int) $this->get('volunteers_count', 0);
    }

    public function getStaffCount(): int
    {
        return (int) $this->get('staff_count', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getImpactMetrics(): array
    {
        return $this->get('impact_metrics', []);
    }

    public function hasImpactMetrics(): bool
    {
        return $this->getImpactMetrics() !== [];
    }

    // Partnerships and Affiliations
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPartners(): array
    {
        return $this->get('partners', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAffiliations(): array
    {
        return $this->get('affiliations', []);
    }

    /**
     * @return array<int, string>
     */
    public function getCertifications(): array
    {
        return $this->get('certifications', []);
    }

    public function hasPartners(): bool
    {
        return $this->getPartners() !== [];
    }

    public function hasAffiliations(): bool
    {
        return $this->getAffiliations() !== [];
    }

    public function hasCertifications(): bool
    {
        return $this->getCertifications() !== [];
    }

    // Awards and Recognition
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAwards(): array
    {
        return $this->get('awards', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecognitions(): array
    {
        return $this->get('recognitions', []);
    }

    public function hasAwards(): bool
    {
        return $this->getAwards() !== [];
    }

    public function hasRecognitions(): bool
    {
        return $this->getRecognitions() !== [];
    }

    // Social Media and Online Presence
    public function getFacebookUrl(): ?string
    {
        return $this->get('facebook_url');
    }

    public function getTwitterHandle(): ?string
    {
        return $this->get('twitter_handle');
    }

    public function getTwitterUrl(): ?string
    {
        $handle = $this->getTwitterHandle();

        return $handle ? "https://twitter.com/{$handle}" : $this->get('twitter_url');
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->get('linkedin_url');
    }

    public function getInstagramUrl(): ?string
    {
        return $this->get('instagram_url');
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->get('youtube_url');
    }

    public function getTiktokUrl(): ?string
    {
        return $this->get('tiktok_url');
    }

    /**
     * @return array<string, string>
     */
    public function getAllSocialMediaLinks(): array
    {
        return array_filter([
            'facebook' => $this->getFacebookUrl(),
            'twitter' => $this->getTwitterUrl(),
            'linkedin' => $this->getLinkedinUrl(),
            'instagram' => $this->getInstagramUrl(),
            'youtube' => $this->getYoutubeUrl(),
            'tiktok' => $this->getTiktokUrl(),
        ]);
    }

    // Donation and Fundraising Settings
    public function getDefaultCurrency(): string
    {
        return $this->get('default_currency', 'USD');
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

    /**
     * @return array<int, float>
     */
    public function getSuggestedDonationAmounts(): array
    {
        return $this->get('suggested_donation_amounts', []);
    }

    public function supportsCorporateMatching(): bool
    {
        return $this->get('supports_corporate_matching', false);
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

    // Communication Preferences
    /**
     * @return array<string, mixed>
     */
    public function getEmailTemplates(): array
    {
        return $this->get('email_templates', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getNotificationPreferences(): array
    {
        return $this->get('notification_preferences', []);
    }

    public function hasCustomEmailTemplates(): bool
    {
        return $this->getEmailTemplates() !== [];
    }

    // Privacy and Security Settings
    public function getPrivacyLevel(): string
    {
        return $this->get('privacy_level', 'public');
    }

    public function isPublic(): bool
    {
        return $this->getPrivacyLevel() === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->getPrivacyLevel() === 'private';
    }

    public function isRestricted(): bool
    {
        return $this->getPrivacyLevel() === 'restricted';
    }

    public function allowsPublicDonations(): bool
    {
        return $this->get('allows_public_donations', true);
    }

    public function requiresDonorApproval(): bool
    {
        return $this->get('requires_donor_approval', false);
    }

    // Verification and Trust Indicators
    public function isVerified(): bool
    {
        return $this->get('is_verified', false);
    }

    public function getVerificationLevel(): string
    {
        return $this->get('verification_level', 'none');
    }

    public function getTrustScore(): int
    {
        return (int) $this->get('trust_score', 0);
    }

    public function isTaxExempt(): bool
    {
        return $this->get('is_tax_exempt', false);
    }

    // Activity and Status
    public function getLastActivityAt(): ?string
    {
        return $this->get('last_activity_at');
    }

    public function getLastCampaignAt(): ?string
    {
        return $this->get('last_campaign_at');
    }

    public function isActive(): bool
    {
        $lastActivity = $this->getLastActivityAt();
        if (! $lastActivity) {
            return false;
        }

        // Consider active if activity within last 90 days
        return (time() - strtotime($lastActivity)) < (90 * 24 * 60 * 60);
    }

    // Profile Completeness
    public function getProfileCompleteness(): float
    {
        $fields = [
            'description', 'mission', 'website', 'logo_url',
            'primary_email', 'primary_phone', 'headquarters_address',
            'founded_date', 'legal_structure',
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (! empty($this->get($field))) {
                $completed++;
            }
        }

        // Additional points for optional content
        if ($this->hasGallery()) {
            $completed += 0.5;
        }
        if ($this->hasPrograms()) {
            $completed += 0.5;
        }
        if ($this->hasPartners()) {
            $completed += 0.5;
        }
        if ($this->hasFinancialReports()) {
            $completed += 0.5;
        }

        return min(100, ($completed / count($fields)) * 100);
    }

    public function isProfileComplete(): bool
    {
        return $this->getProfileCompleteness() >= 80;
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getOrganizationId(),
            'basic_info' => [
                'name' => $this->getName(),
                'slug' => $this->getSlug(),
                'tagline' => $this->getTagline(),
                'description' => $this->getDescription(),
                'mission' => $this->getMission(),
                'vision' => $this->getVision(),
                'values' => $this->getValues(),
                'history' => $this->getHistory(),
            ],
            'contact' => [
                'primary_email' => $this->getPrimaryEmail(),
                'secondary_email' => $this->getSecondaryEmail(),
                'primary_phone' => $this->getPrimaryPhone(),
                'secondary_phone' => $this->getSecondaryPhone(),
                'fax' => $this->getFax(),
                'website' => $this->getWebsite(),
                'blog' => $this->getBlog(),
            ],
            'media' => [
                'logo_url' => $this->getLogoUrl(),
                'banner_url' => $this->getBannerUrl(),
                'gallery_images' => $this->getGalleryImages(),
                'videos' => $this->getVideos(),
                'has_gallery' => $this->hasGallery(),
                'has_videos' => $this->hasVideos(),
            ],
            'addresses' => [
                'headquarters' => $this->getHeadquartersAddress(),
                'mailing' => $this->getMailingAddress(),
                'has_mailing_address' => $this->hasMailingAddress(),
                'branches' => $this->getBranches(),
                'has_branches' => $this->hasBranches(),
            ],
            'legal' => [
                'legal_name' => $this->getLegalName(),
                'registration_number' => $this->getRegistrationNumber(),
                'tax_id' => $this->getTaxId(),
                'tax_exempt_number' => $this->getTaxExemptNumber(),
                'incorporation_date' => $this->getIncorporationDate(),
                'founded_date' => $this->getFoundedDate(),
                'legal_structure' => $this->getLegalStructure(),
                'is_tax_exempt' => $this->isTaxExempt(),
            ],
            'leadership' => [
                'executive_team' => $this->getExecutiveTeam(),
                'board_members' => $this->getBoardMembers(),
                'key_staff' => $this->getKeyStaff(),
                'has_executive_team' => $this->hasExecutiveTeam(),
                'has_board_members' => $this->hasBoardMembers(),
            ],
            'financial' => [
                'annual_budget' => $this->getAnnualBudget(),
                'last_fiscal_year' => $this->getLastFiscalYear(),
                'financial_reports' => $this->getFinancialReports(),
                'has_financial_reports' => $this->hasFinancialReports(),
            ],
            'programs' => [
                'programs' => $this->getPrograms(),
                'services' => $this->getServices(),
                'has_programs' => $this->hasPrograms(),
                'has_services' => $this->hasServices(),
            ],
            'impact' => [
                'beneficiaries_served' => $this->getBeneficiariesServed(),
                'volunteers_count' => $this->getVolunteersCount(),
                'staff_count' => $this->getStaffCount(),
                'impact_metrics' => $this->getImpactMetrics(),
                'has_impact_metrics' => $this->hasImpactMetrics(),
            ],
            'partnerships' => [
                'partners' => $this->getPartners(),
                'affiliations' => $this->getAffiliations(),
                'certifications' => $this->getCertifications(),
                'has_partners' => $this->hasPartners(),
                'has_affiliations' => $this->hasAffiliations(),
                'has_certifications' => $this->hasCertifications(),
            ],
            'recognition' => [
                'awards' => $this->getAwards(),
                'recognitions' => $this->getRecognitions(),
                'has_awards' => $this->hasAwards(),
                'has_recognitions' => $this->hasRecognitions(),
            ],
            'social_media' => $this->getAllSocialMediaLinks(),
            'donation_settings' => [
                'default_currency' => $this->getDefaultCurrency(),
                'min_donation_amount' => $this->getMinDonationAmount(),
                'max_donation_amount' => $this->getMaxDonationAmount(),
                'suggested_amounts' => $this->getSuggestedDonationAmounts(),
                'supports_corporate_matching' => $this->supportsCorporateMatching(),
                'matching_ratio' => $this->getMatchingRatio(),
                'matching_cap' => $this->getMatchingCap(),
            ],
            'privacy' => [
                'privacy_level' => $this->getPrivacyLevel(),
                'is_public' => $this->isPublic(),
                'allows_public_donations' => $this->allowsPublicDonations(),
                'requires_donor_approval' => $this->requiresDonorApproval(),
            ],
            'verification' => [
                'is_verified' => $this->isVerified(),
                'verification_level' => $this->getVerificationLevel(),
                'trust_score' => $this->getTrustScore(),
            ],
            'activity' => [
                'last_activity_at' => $this->getLastActivityAt(),
                'last_campaign_at' => $this->getLastCampaignAt(),
                'is_active' => $this->isActive(),
            ],
            'profile' => [
                'completeness' => $this->getProfileCompleteness(),
                'is_complete' => $this->isProfileComplete(),
            ],
        ];
    }

    /**
     * Get data optimized for public profile pages
     *
     * @return array<string, mixed>
     */
    public function toPublicProfile(): array
    {
        return [
            'id' => $this->getOrganizationId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'tagline' => $this->getTagline(),
            'description' => $this->getDescription(),
            'mission' => $this->getMission(),
            'vision' => $this->getVision(),
            'website' => $this->getWebsite(),
            'logo_url' => $this->getLogoUrl(),
            'banner_url' => $this->getBannerUrl(),
            'gallery_images' => $this->getGalleryImages(),
            'programs' => $this->getPrograms(),
            'services' => $this->getServices(),
            'impact_metrics' => $this->getImpactMetrics(),
            'awards' => $this->getAwards(),
            'social_media' => $this->getAllSocialMediaLinks(),
            'is_verified' => $this->isVerified(),
            'is_tax_exempt' => $this->isTaxExempt(),
            'founded_date' => $this->getFoundedDate(),
            'beneficiaries_served' => $this->getBeneficiariesServed(),
            'volunteers_count' => $this->getVolunteersCount(),
        ];
    }

    /**
     * Get data optimized for admin panels
     *
     * @return array<string, mixed>
     */
    public function toAdminView(): array
    {
        $publicData = $this->toArray();

        // Add admin-only fields
        $publicData['admin'] = [
            'profile_completeness' => $this->getProfileCompleteness(),
            'trust_score' => $this->getTrustScore(),
            'email_templates' => $this->getEmailTemplates(),
            'notification_preferences' => $this->getNotificationPreferences(),
        ];

        return $publicData;
    }
}

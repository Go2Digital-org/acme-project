<?php

declare(strict_types=1);

namespace Modules\User\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * User profile read model optimized for user details, permissions, and organization info.
 */
class UserProfileReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $userId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($userId, $data, $version);
        $this->setCacheTtl(1800); // 30 minutes for user profiles
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'user_profile',
            'user:' . $this->id,
            'organization:' . $this->getOrganizationId(),
        ]);
    }

    // Basic User Information
    public function getUserId(): int
    {
        return (int) $this->id;
    }

    public function getName(): string
    {
        return $this->get('name', '');
    }

    public function getEmail(): string
    {
        return $this->get('email', '');
    }

    public function getFirstName(): ?string
    {
        return $this->get('first_name');
    }

    public function getLastName(): ?string
    {
        return $this->get('last_name');
    }

    public function getFullName(): string
    {
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();

        if ($firstName && $lastName) {
            return trim($firstName . ' ' . $lastName);
        }

        return $this->getName();
    }

    public function getAvatarUrl(): ?string
    {
        return $this->get('avatar_url');
    }

    public function getPhone(): ?string
    {
        return $this->get('phone');
    }

    public function getJobTitle(): ?string
    {
        return $this->get('job_title');
    }

    public function getDepartment(): ?string
    {
        return $this->get('department');
    }

    public function getLocation(): ?string
    {
        return $this->get('location');
    }

    public function getTimezone(): ?string
    {
        return $this->get('timezone');
    }

    public function getLanguage(): string
    {
        return $this->get('language', 'en');
    }

    public function getBio(): ?string
    {
        return $this->get('bio');
    }

    // Account Status
    public function getStatus(): string
    {
        return $this->get('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->getStatus() === 'suspended';
    }

    public function isVerified(): bool
    {
        return $this->get('is_verified', false);
    }

    public function getEmailVerifiedAt(): ?string
    {
        return $this->get('email_verified_at');
    }

    // Organization Information
    public function getOrganizationId(): ?int
    {
        $orgId = $this->get('organization_id');

        return $orgId ? (int) $orgId : null;
    }

    public function getOrganizationName(): ?string
    {
        return $this->get('organization_name');
    }

    public function getOrganizationType(): ?string
    {
        return $this->get('organization_type');
    }

    public function getOrganizationStatus(): ?string
    {
        return $this->get('organization_status');
    }

    public function isOrganizationVerified(): bool
    {
        return $this->get('organization_is_verified', false);
    }

    // Roles and Permissions
    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->get('roles', []);
    }

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return $this->get('permissions', []);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    public function isAdmin(): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->hasRole('super_admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isOrganizationAdmin(): bool
    {
        if ($this->hasRole('organization_admin')) {
            return true;
        }

        return $this->isAdmin();
    }

    public function canCreateCampaigns(): bool
    {
        if ($this->hasPermission('campaigns.create')) {
            return true;
        }

        return $this->isOrganizationAdmin();
    }

    public function canApproveCampaigns(): bool
    {
        if ($this->hasPermission('campaigns.approve')) {
            return true;
        }

        return $this->isOrganizationAdmin();
    }

    public function canViewReports(): bool
    {
        if ($this->hasPermission('reports.view')) {
            return true;
        }

        return $this->isOrganizationAdmin();
    }

    public function canManageUsers(): bool
    {
        if ($this->hasPermission('users.manage')) {
            return true;
        }

        return $this->isOrganizationAdmin();
    }

    // Activity Statistics
    public function getCampaignsCreated(): int
    {
        return (int) $this->get('campaigns_created', 0);
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

    public function getDonationsMade(): int
    {
        return (int) $this->get('donations_made', 0);
    }

    public function getTotalDonatedAmount(): float
    {
        return (float) $this->get('total_donated_amount', 0);
    }

    public function getCampaignsSupported(): int
    {
        return (int) $this->get('campaigns_supported', 0);
    }

    public function getBookmarkedCampaigns(): int
    {
        return (int) $this->get('bookmarked_campaigns', 0);
    }

    // Recent Activity
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentCampaigns(): array
    {
        return $this->get('recent_campaigns', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentDonations(): array
    {
        return $this->get('recent_donations', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentBookmarks(): array
    {
        return $this->get('recent_bookmarks', []);
    }

    // Performance Metrics
    public function getAverageCampaignSuccessRate(): float
    {
        return (float) $this->get('average_campaign_success_rate', 0);
    }

    public function getAverageAmountRaisedPerCampaign(): float
    {
        $campaigns = $this->getCampaignsCreated();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $campaigns;
    }

    public function getCampaignCreationVelocity(): float
    {
        return (float) $this->get('campaign_creation_velocity', 0);
    }

    public function getDonationFrequency(): float
    {
        return (float) $this->get('donation_frequency', 0);
    }

    // Preferences and Settings
    /**
     * @return array<string, mixed>
     */
    public function getNotificationPreferences(): array
    {
        return $this->get('notification_preferences', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrivacySettings(): array
    {
        return $this->get('privacy_settings', []);
    }

    public function isEmailNotificationsEnabled(): bool
    {
        $prefs = $this->getNotificationPreferences();

        return $prefs['email'] ?? true;
    }

    public function isPushNotificationsEnabled(): bool
    {
        $prefs = $this->getNotificationPreferences();

        return $prefs['push'] ?? true;
    }

    public function getProfileVisibility(): string
    {
        $privacy = $this->getPrivacySettings();

        return $privacy['profile_visibility'] ?? 'organization';
    }

    public function isDonationHistoryPublic(): bool
    {
        $privacy = $this->getPrivacySettings();

        return $privacy['donation_history_public'] ?? false;
    }

    // Two-Factor Authentication
    public function isTwoFactorEnabled(): bool
    {
        return $this->get('two_factor_enabled', false);
    }

    public function getTwoFactorConfirmedAt(): ?string
    {
        return $this->get('two_factor_confirmed_at');
    }

    // API Access
    public function hasApiAccess(): bool
    {
        return $this->get('has_api_access', false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getApiTokens(): array
    {
        return $this->get('api_tokens', []);
    }

    public function getActiveApiTokensCount(): int
    {
        return (int) $this->get('active_api_tokens_count', 0);
    }

    // Login Statistics
    public function getLastLoginAt(): ?string
    {
        return $this->get('last_login_at');
    }

    public function getLastActiveAt(): ?string
    {
        return $this->get('last_active_at');
    }

    public function getLoginCount(): int
    {
        return (int) $this->get('login_count', 0);
    }

    public function getFailedLoginAttempts(): int
    {
        return (int) $this->get('failed_login_attempts', 0);
    }

    public function getLastFailedLoginAt(): ?string
    {
        return $this->get('last_failed_login_at');
    }

    public function isOnline(): bool
    {
        $lastActive = $this->getLastActiveAt();
        if (! $lastActive) {
            return false;
        }

        // Consider online if active within last 5 minutes
        $lastActiveTime = strtotime($lastActive);

        return (time() - $lastActiveTime) < 300;
    }

    // Achievements and Badges
    /**
     * @return array<int, string>
     */
    public function getBadges(): array
    {
        return $this->get('badges', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAchievements(): array
    {
        return $this->get('achievements', []);
    }

    public function hasBadge(string $badge): bool
    {
        return in_array($badge, $this->getBadges());
    }

    public function getReputation(): int
    {
        return (int) $this->get('reputation', 0);
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

    public function getDeletedAt(): ?string
    {
        return $this->get('deleted_at');
    }

    // Calculated Properties
    public function getAccountAge(): int
    {
        $created = $this->getCreatedAt();
        if (! $created) {
            return 0;
        }

        $createdTime = strtotime($created);

        return (int) ((time() - $createdTime) / (60 * 60 * 24)); // Days
    }

    public function getDaysSinceLastLogin(): int
    {
        $lastLogin = $this->getLastLoginAt();
        if (! $lastLogin) {
            return 0;
        }

        $lastLoginTime = strtotime($lastLogin);

        return (int) ((time() - $lastLoginTime) / (60 * 60 * 24));
    }

    // Profile Completeness
    public function getProfileCompleteness(): float
    {
        $requiredFields = [
            'name', 'email', 'first_name', 'last_name',
            'job_title', 'department', 'avatar_url',
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (! empty($this->get($field))) {
                $completedFields++;
            }
        }

        return ($completedFields / count($requiredFields)) * 100;
    }

    public function isProfileComplete(): bool
    {
        return $this->getProfileCompleteness() >= 80;
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toProfileArray(): array
    {
        return [
            'user' => [
                'id' => $this->getUserId(),
                'name' => $this->getName(),
                'email' => $this->getEmail(),
                'first_name' => $this->getFirstName(),
                'last_name' => $this->getLastName(),
                'full_name' => $this->getFullName(),
                'avatar_url' => $this->getAvatarUrl(),
                'phone' => $this->getPhone(),
                'job_title' => $this->getJobTitle(),
                'department' => $this->getDepartment(),
                'location' => $this->getLocation(),
                'timezone' => $this->getTimezone(),
                'language' => $this->getLanguage(),
                'bio' => $this->getBio(),
            ],
            'account' => [
                'status' => $this->getStatus(),
                'is_active' => $this->isActive(),
                'is_verified' => $this->isVerified(),
                'email_verified_at' => $this->getEmailVerifiedAt(),
                'profile_completeness' => $this->getProfileCompleteness(),
                'account_age' => $this->getAccountAge(),
            ],
            'organization' => [
                'id' => $this->getOrganizationId(),
                'name' => $this->getOrganizationName(),
                'type' => $this->getOrganizationType(),
                'status' => $this->getOrganizationStatus(),
                'is_verified' => $this->isOrganizationVerified(),
            ],
            'access' => [
                'roles' => $this->getRoles(),
                'permissions' => $this->getPermissions(),
                'is_admin' => $this->isAdmin(),
                'is_organization_admin' => $this->isOrganizationAdmin(),
                'can_create_campaigns' => $this->canCreateCampaigns(),
                'can_approve_campaigns' => $this->canApproveCampaigns(),
                'can_view_reports' => $this->canViewReports(),
                'can_manage_users' => $this->canManageUsers(),
            ],
            'activity' => [
                'campaigns_created' => $this->getCampaignsCreated(),
                'active_campaigns' => $this->getActiveCampaigns(),
                'completed_campaigns' => $this->getCompletedCampaigns(),
                'total_amount_raised' => $this->getTotalAmountRaised(),
                'donations_made' => $this->getDonationsMade(),
                'total_donated_amount' => $this->getTotalDonatedAmount(),
                'campaigns_supported' => $this->getCampaignsSupported(),
                'bookmarked_campaigns' => $this->getBookmarkedCampaigns(),
            ],
            'performance' => [
                'average_campaign_success_rate' => $this->getAverageCampaignSuccessRate(),
                'average_amount_per_campaign' => $this->getAverageAmountRaisedPerCampaign(),
                'campaign_creation_velocity' => $this->getCampaignCreationVelocity(),
                'donation_frequency' => $this->getDonationFrequency(),
                'reputation' => $this->getReputation(),
            ],
            'preferences' => [
                'notifications' => $this->getNotificationPreferences(),
                'privacy' => $this->getPrivacySettings(),
                'profile_visibility' => $this->getProfileVisibility(),
                'donation_history_public' => $this->isDonationHistoryPublic(),
            ],
            'security' => [
                'two_factor_enabled' => $this->isTwoFactorEnabled(),
                'two_factor_confirmed_at' => $this->getTwoFactorConfirmedAt(),
                'has_api_access' => $this->hasApiAccess(),
                'active_api_tokens_count' => $this->getActiveApiTokensCount(),
                'failed_login_attempts' => $this->getFailedLoginAttempts(),
            ],
            'login_stats' => [
                'last_login_at' => $this->getLastLoginAt(),
                'last_active_at' => $this->getLastActiveAt(),
                'login_count' => $this->getLoginCount(),
                'days_since_last_login' => $this->getDaysSinceLastLogin(),
                'is_online' => $this->isOnline(),
            ],
            'achievements' => [
                'badges' => $this->getBadges(),
                'achievements' => $this->getAchievements(),
            ],
            'recent_activity' => [
                'campaigns' => $this->getRecentCampaigns(),
                'donations' => $this->getRecentDonations(),
                'bookmarks' => $this->getRecentBookmarks(),
            ],
        ];
    }
}

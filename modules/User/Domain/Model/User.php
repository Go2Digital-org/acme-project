<?php

declare(strict_types=1);

namespace Modules\User\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Domain\ValueObject\UserRole;
use Modules\User\Domain\ValueObject\UserStatus;

/**
 * User Domain Model.
 *
 * Rich domain model representing a user in the ACME CSR platform.
 * Contains business logic and enforces domain rules.
 */
final class User
{
    public function __construct(
        private readonly int $id,
        private string $firstName,
        private string $lastName,
        private readonly EmailAddress $email,
        private UserStatus $status,
        private UserRole $role,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $emailVerifiedAt = null,
        private ?string $profilePhotoPath = null,
        private ?string $twoFactorSecret = null,
        /** @var array<int, string>|null */
        private ?array $twoFactorRecoveryCodes = null,
        private ?string $jobTitle = null,
        private ?string $phoneNumber = null,
        /** @var array<string, mixed> */
        private array $preferences = [],
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function getEmailString(): string
    {
        return $this->email->value;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getProfilePhotoPath(): ?string
    {
        return $this->profilePhotoPath;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /** @return array<array-key, mixed> */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    // Business Logic Methods

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function isVerified(): bool
    {
        return $this->emailVerifiedAt instanceof DateTimeImmutable;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->twoFactorSecret !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isEmployee(): bool
    {
        return $this->role->isEmployee();
    }

    public function canCreateCampaigns(): bool
    {
        return $this->role->canCreateCampaigns() && $this->isActive() && $this->isVerified();
    }

    public function canMakeDonations(): bool
    {
        return $this->role->canMakeDonations() && $this->isActive();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers() && $this->isActive();
    }

    // State Modification Methods

    public function updateProfile(
        string $firstName,
        string $lastName,
        ?string $jobTitle = null,
        ?string $phoneNumber = null,
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->jobTitle = $jobTitle;
        $this->phoneNumber = $phoneNumber;
    }

    public function updateProfilePhoto(?string $profilePhotoPath): void
    {
        $this->profilePhotoPath = $profilePhotoPath;
    }

    public function verifyEmail(DateTimeImmutable $verifiedAt): void
    {
        if ($this->emailVerifiedAt instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('Email is already verified');
        }

        $this->emailVerifiedAt = $verifiedAt;
    }

    public function activateAccount(): void
    {
        if ($this->status === UserStatus::ACTIVE) {
            throw new InvalidArgumentException('User is already active');
        }

        $this->status = UserStatus::ACTIVE;
    }

    public function deactivateAccount(): void
    {
        if ($this->status === UserStatus::INACTIVE) {
            throw new InvalidArgumentException('User is already inactive');
        }

        $this->status = UserStatus::INACTIVE;
    }

    public function suspendAccount(): void
    {
        $this->status = UserStatus::SUSPENDED;
    }

    public function promoteToAdmin(): void
    {
        if (! $this->isActive()) {
            throw new InvalidArgumentException('Cannot promote inactive user to admin');
        }

        $this->role = UserRole::ADMIN;
    }

    public function demoteToEmployee(): void
    {
        $this->role = UserRole::EMPLOYEE;
    }

    /**
     * @param  array<int, string>  $recoveryCodes
     */
    public function enableTwoFactor(string $secret, array $recoveryCodes): void
    {
        $this->twoFactorSecret = $secret;
        $this->twoFactorRecoveryCodes = $recoveryCodes;
    }

    public function disableTwoFactor(): void
    {
        $this->twoFactorSecret = null;
        $this->twoFactorRecoveryCodes = null;
    }

    /**
     * @param  array<string, mixed>  $preferences
     */
    public function updatePreferences(array $preferences): void
    {
        $this->preferences = array_merge($this->preferences, $preferences);
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    public function setPreference(string $key, mixed $value): void
    {
        $this->preferences[$key] = $value;
    }

    // Validation Methods

    public function validateForCampaignCreation(): void
    {
        if (! $this->canCreateCampaigns()) {
            throw new InvalidArgumentException('User cannot create campaigns. Must be active and verified.');
        }
    }

    public function validateForDonation(): void
    {
        if (! $this->canMakeDonations()) {
            throw new InvalidArgumentException('User cannot make donations. Must be active.');
        }
    }

    /**
     * Get user settings/preferences.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->preferences;
    }

    // Static Factory Methods

    public static function create(
        int $id,
        string $firstName,
        string $lastName,
        EmailAddress $email,
        UserRole $role = UserRole::EMPLOYEE,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            status: UserStatus::ACTIVE,
            role: $role,
            createdAt: $createdAt ?? new DateTimeImmutable,
        );
    }

    // Array conversion for persistence

    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email->value,
            'status' => $this->status->value,
            'role' => $this->role->value,
            'created_at' => $this->createdAt,
            'email_verified_at' => $this->emailVerifiedAt,
            'profile_photo_path' => $this->profilePhotoPath,
            'two_factor_secret' => $this->twoFactorSecret,
            'two_factor_recovery_codes' => $this->twoFactorRecoveryCodes,
            'job_title' => $this->jobTitle,
            'phone_number' => $this->phoneNumber,
            'preferences' => $this->preferences,
        ];
    }
}

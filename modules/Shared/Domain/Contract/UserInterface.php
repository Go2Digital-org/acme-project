<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contract;

use Illuminate\Support\Carbon;

/**
 * User Domain Interface.
 *
 * Domain contract for user entities that defines only the methods
 * needed by the domain layer, maintaining architectural boundaries.
 */
interface UserInterface
{
    /**
     * Get the user's unique identifier.
     */
    public function getId(): int;

    /**
     * Get the user's full name.
     */
    public function getFullName(): string;

    /**
     * Get the user's display name.
     */
    public function getName(): string;

    /**
     * Get the user's email address.
     */
    public function getEmail(): string;

    /**
     * Get the user's role.
     */
    public function getRole(): ?string;

    /**
     * Check if the user is active.
     */
    public function isActive(): bool;

    /**
     * Get the user's profile photo path.
     */
    public function getProfilePhotoPath(): ?string;

    /**
     * Get the user's email verification timestamp.
     */
    public function getEmailVerifiedAt(): ?Carbon;

    /**
     * Get the user's creation timestamp.
     */
    public function getCreatedAt(): ?Carbon;

    /**
     * Get the user's last update timestamp.
     */
    public function getUpdatedAt(): ?Carbon;

    /**
     * Get a user preference value.
     */
    public function getPreference(string $key, mixed $default = null): mixed;

    /**
     * Set a user preference value.
     */
    public function setPreference(string $key, mixed $value): void;

    /**
     * Update multiple preferences at once.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function updatePreferences(array $preferences): void;

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool;

    /**
     * Check if the user has any of the given roles.
     *
     * @param  array<string>|string  $roles
     */
    public function hasAnyRole(array|string $roles): bool;

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool;

    /**
     * Get the user's organization ID.
     */
    public function getOrganizationId(): ?int;

    /**
     * Check if the user account is locked.
     */
    public function isAccountLocked(): bool;

    /**
     * Check if the user's data processing is restricted.
     */
    public function isDataProcessingRestricted(): bool;

    /**
     * Check if the user's personal data is anonymized.
     */
    public function isPersonalDataAnonymized(): bool;

    /**
     * Get the user's organization.
     */
    public function getOrganization(): mixed;

    /**
     * Get user settings/preferences.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array;
}

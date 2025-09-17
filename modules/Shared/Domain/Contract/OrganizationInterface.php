<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contract;

use Illuminate\Support\Carbon;

/**
 * Organization Domain Interface.
 *
 * Domain contract for organization entities that defines only the methods
 * needed by the domain layer, maintaining architectural boundaries.
 */
interface OrganizationInterface
{
    /**
     * Get the organization's unique identifier.
     */
    public function getId(): int;

    /**
     * Get the organization's name.
     */
    public function getName(): string;

    /**
     * Get the organization's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the organization's mission.
     */
    public function getMission(): ?string;

    /**
     * Get the organization's email.
     */
    public function getEmail(): ?string;

    /**
     * Get the organization's website.
     */
    public function getWebsite(): ?string;

    /**
     * Get the organization's phone number.
     */
    public function getPhone(): ?string;

    /**
     * Get the organization's status.
     */
    public function getStatus(): string;

    /**
     * Check if the organization is active.
     */
    public function isActive(): bool;

    /**
     * Check if the organization is verified.
     */
    public function getIsVerified(): bool;

    /**
     * Check if the organization can create campaigns.
     */
    public function canCreateCampaigns(): bool;

    /**
     * Get the organization's creation timestamp.
     */
    public function getCreatedAt(): ?Carbon;

    /**
     * Get the organization's last update timestamp.
     */
    public function getUpdatedAt(): ?Carbon;

    /**
     * Get the organization's verification date.
     */
    public function getVerificationDate(): ?Carbon;

    /**
     * Get the organization's logo URL.
     */
    public function getLogoUrl(): ?string;

    /**
     * Get the organization's category.
     */
    public function getCategory(): ?string;

    /**
     * Get the organization's type.
     */
    public function getType(): ?string;

    /**
     * Get the organization's registration number.
     */
    public function getRegistrationNumber(): ?string;

    /**
     * Get the organization's tax ID.
     */
    public function getTaxId(): ?string;

    /**
     * Check if the organization is verified.
     */
    public function getIsActive(): bool;
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contract;

interface DonationInterface
{
    public function getAmount(): float;

    public function getStatus(): string;

    public function getCreatedAt(): string;

    public function getTransactionId(): ?string;

    public function getNotes(): ?string;

    public function getPaymentMethod(): ?string;

    public function getRecurringFrequency(): ?string;

    public function isAnonymous(): bool;

    public function getCampaignTitle(): ?string;

    public function getOrganizationName(): ?string;

    public function isEligibleForTaxReceipt(): bool;
}

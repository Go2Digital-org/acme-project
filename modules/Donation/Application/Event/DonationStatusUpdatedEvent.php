<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Event;

use DateTimeInterface;
use Modules\Donation\Domain\Model\Donation;

final readonly class DonationStatusUpdatedEvent
{
    public function __construct(
        public Donation $donation,
        public string $previousStatus,
        public string $newStatus,
        public DateTimeInterface $updatedAt,
        public ?int $updatedBy = null
    ) {}

    public function getDonationId(): int
    {
        return $this->donation->getId();
    }

    public function getCampaignId(): int
    {
        return $this->donation->getCampaignId();
    }

    public function getAmount(): float
    {
        return $this->donation->getAmount();
    }

    public function getDonorId(): ?int
    {
        return $this->donation->getDonorId();
    }
}

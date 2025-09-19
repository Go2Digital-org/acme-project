<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;
use Modules\Shared\Domain\Event\DonationEventInterface;

final class DonationCompletedEvent extends AbstractDomainEvent implements DonationEventInterface
{
    public function __construct(
        public readonly int $donationId,
        public readonly int $campaignId,
        public readonly ?int $userId,
        public readonly float $amount,
        public readonly string $currency,
    ) {
        parent::__construct($donationId);
    }

    public function getEventName(): string
    {
        return 'donation.completed';
    }

    public function getDonationId(): int
    {
        return $this->donationId;
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getEntityType(): string
    {
        return 'donation';
    }

    public function getEntityId(): int
    {
        return $this->donationId;
    }

    public function isAsync(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'donationId' => $this->donationId,
            'campaignId' => $this->campaignId,
            'userId' => $this->userId,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ]);
    }
}

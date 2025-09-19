<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

final class DonationRefundedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $donationId,
        public readonly int $campaignId,
        public readonly ?int $userId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $refundReason,
        public readonly int $processedByEmployeeId,
    ) {
        parent::__construct($donationId);
    }

    public function getEventName(): string
    {
        return 'donation.refunded';
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
            'refundReason' => $this->refundReason,
            'processedByEmployeeId' => $this->processedByEmployeeId,
        ]);
    }
}

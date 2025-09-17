<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Event;

use DateTimeImmutable;
use Modules\Shared\Application\Event\AbstractDomainEvent;

final class DonationProcessedEvent extends AbstractDomainEvent
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly int $donationId,
        public readonly int $campaignId,
        public readonly ?int $userId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $transactionId,
        public readonly string $paymentMethod = 'card',
        public readonly string $paymentGateway = 'stripe',
        public readonly bool $anonymous = false,
        public readonly ?string $notes = null,
        /** @var array<string, string>|null */
        public readonly ?array $notesTranslations = null,
        public readonly ?string $locale = 'en',
        /** @var array<string, mixed>|null */
        public readonly ?array $auditMetadata = null,
    ) {
        parent::__construct($donationId);
    }

    public function getEventName(): string
    {
        return 'donation.processed';
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
            'transactionId' => $this->transactionId,
            'paymentMethod' => $this->paymentMethod,
            'paymentGateway' => $this->paymentGateway,
            'anonymous' => $this->anonymous,
            'notes' => $this->notes,
            'notesTranslations' => $this->notesTranslations,
            'locale' => $this->locale,
            'auditMetadata' => $this->auditMetadata,
        ]);
    }

    /**
     * Get audit trail data for this event.
     */
    /** @return array<array-key, mixed> */
    public function getAuditData(): array
    {
        return [
            'event' => $this->getEventName(),
            'donation_id' => $this->donationId,
            'campaign_id' => $this->campaignId,
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_id' => $this->transactionId,
            'payment_method' => $this->paymentMethod,
            'payment_gateway' => $this->paymentGateway,
            'anonymous' => $this->anonymous,
            'locale' => $this->locale,
            'has_notes' => $this->notes !== null,
            'has_translations' => $this->notesTranslations !== null,
            'occurred_at' => $this->occurredAt,
            'metadata' => $this->auditMetadata,
        ];
    }

    /**
     * Check if this donation qualifies for special handling.
     */
    public function isLargeDonation(float $threshold = 1000.0): bool
    {
        return $this->amount >= $threshold;
    }

    /**
     * Get localized summary for notifications.
     */
    public function getLocalizedSummary(): string
    {
        $amountFormatted = number_format($this->amount, 2) . ' ' . $this->currency;

        if ($this->anonymous) {
            return "Anonymous donation of {$amountFormatted} processed successfully";
        }

        return "Donation of {$amountFormatted} from user {$this->userId} processed successfully";
    }
}

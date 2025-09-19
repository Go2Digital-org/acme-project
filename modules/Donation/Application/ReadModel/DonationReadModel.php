<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation read model optimized for donation details, status, and transaction information.
 */
final class DonationReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $donationId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($donationId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for donation data
    }

    /**
     * @return array<string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation',
            'donation:' . $this->id,
            'campaign:' . $this->getCampaignId(),
            'user:' . $this->getUserId(),
            'status:' . $this->getStatus(),
        ]);
    }

    // Basic Donation Information
    public function getDonationId(): int
    {
        return (int) $this->id;
    }

    public function getAmount(): float
    {
        return (float) $this->get('amount', 0);
    }

    public function getCurrency(): string
    {
        return $this->get('currency', 'USD');
    }

    public function getFormattedAmount(): string
    {
        $amount = number_format($this->getAmount(), 2);

        return "{$amount} {$this->getCurrency()}";
    }

    public function getMessage(): ?string
    {
        return $this->get('message');
    }

    public function isAnonymous(): bool
    {
        return $this->get('is_anonymous', false);
    }

    public function isRecurring(): bool
    {
        return $this->get('is_recurring', false);
    }

    public function getRecurringFrequency(): ?string
    {
        return $this->get('recurring_frequency');
    }

    // Status Information
    public function getStatus(): string
    {
        return $this->get('status', 'pending');
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partially Refunded',
            default => 'Unknown',
        };
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->getStatus() === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->getStatus() === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->getStatus() === 'cancelled';
    }

    public function isRefunded(): bool
    {
        if ($this->getStatus() === 'refunded') {
            return true;
        }

        return $this->getStatus() === 'partially_refunded';
    }

    public function isSuccessful(): bool
    {
        return $this->isCompleted();
    }

    // Campaign Information
    public function getCampaignId(): int
    {
        return (int) $this->get('campaign_id', 0);
    }

    public function getCampaignTitle(): ?string
    {
        return $this->get('campaign_title');
    }

    public function getCampaignSlug(): ?string
    {
        return $this->get('campaign_slug');
    }

    public function getCampaignStatus(): ?string
    {
        return $this->get('campaign_status');
    }

    public function getCampaignOrganizationId(): ?int
    {
        $orgId = $this->get('campaign_organization_id');

        return $orgId ? (int) $orgId : null;
    }

    public function getCampaignOrganizationName(): ?string
    {
        return $this->get('campaign_organization_name');
    }

    // User Information
    public function getUserId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function getUserName(): ?string
    {
        return $this->get('user_name');
    }

    public function getUserEmail(): ?string
    {
        return $this->get('user_email');
    }

    public function getUserAvatarUrl(): ?string
    {
        return $this->get('user_avatar_url');
    }

    public function getDisplayName(): string
    {
        if ($this->isAnonymous()) {
            return 'Anonymous Donor';
        }

        return $this->getUserName() ?? 'Unknown Donor';
    }

    // Payment Information
    public function getPaymentMethod(): ?string
    {
        return $this->get('payment_method');
    }

    public function getPaymentProvider(): ?string
    {
        return $this->get('payment_provider');
    }

    public function getTransactionId(): ?string
    {
        return $this->get('transaction_id');
    }

    public function getExternalTransactionId(): ?string
    {
        return $this->get('external_transaction_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGatewayResponse(): ?array
    {
        return $this->get('gateway_response');
    }

    public function getProcessingFee(): float
    {
        return (float) $this->get('processing_fee', 0);
    }

    public function getNetAmount(): float
    {
        return $this->getAmount() - $this->getProcessingFee();
    }

    // Refund Information
    public function getRefundedAmount(): float
    {
        return (float) $this->get('refunded_amount', 0);
    }

    public function getRefundReason(): ?string
    {
        return $this->get('refund_reason');
    }

    public function getRefundedAt(): ?string
    {
        return $this->get('refunded_at');
    }

    public function getRemainingRefundableAmount(): float
    {
        return max(0, $this->getAmount() - $this->getRefundedAmount());
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->getRefundedAmount() > 0 && $this->getRefundedAmount() < $this->getAmount();
    }

    public function isFullyRefunded(): bool
    {
        return $this->getRefundedAmount() >= $this->getAmount();
    }

    // Receipt and Tax Information
    public function getReceiptNumber(): ?string
    {
        return $this->get('receipt_number');
    }

    public function getReceiptUrl(): ?string
    {
        return $this->get('receipt_url');
    }

    public function isTaxDeductible(): bool
    {
        return $this->get('is_tax_deductible', false);
    }

    public function getTaxDeductibleAmount(): float
    {
        if (! $this->isTaxDeductible()) {
            return 0.0;
        }

        return (float) $this->get('tax_deductible_amount', $this->getAmount());
    }

    // Matching and Corporate Information
    public function hasMatching(): bool
    {
        return $this->get('has_matching', false);
    }

    public function getMatchingAmount(): float
    {
        return (float) $this->get('matching_amount', 0);
    }

    public function getTotalAmountWithMatching(): float
    {
        return $this->getAmount() + $this->getMatchingAmount();
    }

    public function getMatchingRatio(): float
    {
        if ($this->getAmount() <= 0) {
            return 0.0;
        }

        return $this->getMatchingAmount() / $this->getAmount();
    }

    public function isCorporateDonation(): bool
    {
        return $this->get('is_corporate', false);
    }

    public function getCorporateName(): ?string
    {
        return $this->get('corporate_name');
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

    public function getCompletedAt(): ?string
    {
        return $this->get('completed_at');
    }

    public function getProcessedAt(): ?string
    {
        return $this->get('processed_at');
    }

    public function getFailedAt(): ?string
    {
        return $this->get('failed_at');
    }

    // Attribution and Source
    public function getSource(): ?string
    {
        return $this->get('source');
    }

    public function getReferrer(): ?string
    {
        return $this->get('referrer');
    }

    public function getUtmCampaign(): ?string
    {
        return $this->get('utm_campaign');
    }

    public function getUtmSource(): ?string
    {
        return $this->get('utm_source');
    }

    public function getUtmMedium(): ?string
    {
        return $this->get('utm_medium');
    }

    // Comments and Reviews
    public function hasPublicComment(): bool
    {
        return ! in_array($this->getMessage(), [null, '', '0'], true) && ! $this->isAnonymous();
    }

    public function canBeDisplayedPublically(): bool
    {
        return ! $this->isAnonymous() && $this->isCompleted();
    }

    // Time Calculations
    public function getAgeInDays(): int
    {
        $created = $this->getCreatedAt();
        if (! $created) {
            return 0;
        }

        return (int) ((time() - strtotime($created)) / (60 * 60 * 24));
    }

    public function getProcessingTimeInMinutes(): int
    {
        $created = $this->getCreatedAt();
        $processed = $this->getProcessedAt() ?? $this->getCompletedAt();

        if (! $created || ! $processed) {
            return 0;
        }

        return (int) ((strtotime($processed) - strtotime($created)) / 60);
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getDonationId(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'formatted_amount' => $this->getFormattedAmount(),
            'message' => $this->getMessage(),
            'is_anonymous' => $this->isAnonymous(),
            'is_recurring' => $this->isRecurring(),
            'recurring_frequency' => $this->getRecurringFrequency(),
            'status' => [
                'status' => $this->getStatus(),
                'status_label' => $this->getStatusLabel(),
                'is_pending' => $this->isPending(),
                'is_processing' => $this->isProcessing(),
                'is_completed' => $this->isCompleted(),
                'is_failed' => $this->isFailed(),
                'is_cancelled' => $this->isCancelled(),
                'is_refunded' => $this->isRefunded(),
                'is_successful' => $this->isSuccessful(),
            ],
            'campaign' => [
                'id' => $this->getCampaignId(),
                'title' => $this->getCampaignTitle(),
                'slug' => $this->getCampaignSlug(),
                'status' => $this->getCampaignStatus(),
                'organization_id' => $this->getCampaignOrganizationId(),
                'organization_name' => $this->getCampaignOrganizationName(),
            ],
            'user' => [
                'id' => $this->getUserId(),
                'name' => $this->getUserName(),
                'email' => $this->getUserEmail(),
                'avatar_url' => $this->getUserAvatarUrl(),
                'display_name' => $this->getDisplayName(),
            ],
            'payment' => [
                'method' => $this->getPaymentMethod(),
                'provider' => $this->getPaymentProvider(),
                'transaction_id' => $this->getTransactionId(),
                'external_transaction_id' => $this->getExternalTransactionId(),
                'processing_fee' => $this->getProcessingFee(),
                'net_amount' => $this->getNetAmount(),
            ],
            'refund' => [
                'refunded_amount' => $this->getRefundedAmount(),
                'refund_reason' => $this->getRefundReason(),
                'refunded_at' => $this->getRefundedAt(),
                'remaining_refundable_amount' => $this->getRemainingRefundableAmount(),
                'is_partially_refunded' => $this->isPartiallyRefunded(),
                'is_fully_refunded' => $this->isFullyRefunded(),
            ],
            'receipt' => [
                'receipt_number' => $this->getReceiptNumber(),
                'receipt_url' => $this->getReceiptUrl(),
                'is_tax_deductible' => $this->isTaxDeductible(),
                'tax_deductible_amount' => $this->getTaxDeductibleAmount(),
            ],
            'matching' => [
                'has_matching' => $this->hasMatching(),
                'matching_amount' => $this->getMatchingAmount(),
                'total_amount_with_matching' => $this->getTotalAmountWithMatching(),
                'matching_ratio' => $this->getMatchingRatio(),
            ],
            'corporate' => [
                'is_corporate' => $this->isCorporateDonation(),
                'corporate_name' => $this->getCorporateName(),
            ],
            'attribution' => [
                'source' => $this->getSource(),
                'referrer' => $this->getReferrer(),
                'utm_campaign' => $this->getUtmCampaign(),
                'utm_source' => $this->getUtmSource(),
                'utm_medium' => $this->getUtmMedium(),
            ],
            'display' => [
                'has_public_comment' => $this->hasPublicComment(),
                'can_be_displayed_publicly' => $this->canBeDisplayedPublically(),
            ],
            'timing' => [
                'age_in_days' => $this->getAgeInDays(),
                'processing_time_minutes' => $this->getProcessingTimeInMinutes(),
            ],
            'timestamps' => [
                'created_at' => $this->getCreatedAt(),
                'updated_at' => $this->getUpdatedAt(),
                'completed_at' => $this->getCompletedAt(),
                'processed_at' => $this->getProcessedAt(),
                'failed_at' => $this->getFailedAt(),
            ],
        ];
    }

    /**
     * Get summary data optimized for lists and cards
     */
    /**
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getDonationId(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'formatted_amount' => $this->getFormattedAmount(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
            'is_anonymous' => $this->isAnonymous(),
            'display_name' => $this->getDisplayName(),
            'campaign_id' => $this->getCampaignId(),
            'campaign_title' => $this->getCampaignTitle(),
            'campaign_organization_name' => $this->getCampaignOrganizationName(),
            'message' => $this->getMessage(),
            'has_matching' => $this->hasMatching(),
            'matching_amount' => $this->getMatchingAmount(),
            'total_amount_with_matching' => $this->getTotalAmountWithMatching(),
            'is_corporate' => $this->isCorporateDonation(),
            'created_at' => $this->getCreatedAt(),
        ];
    }

    /**
     * Get data optimized for public display (respecting anonymity)
     */
    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        $data = [
            'id' => $this->getDonationId(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'formatted_amount' => $this->getFormattedAmount(),
            'display_name' => $this->getDisplayName(),
            'message' => $this->getMessage(),
            'has_matching' => $this->hasMatching(),
            'matching_amount' => $this->getMatchingAmount(),
            'total_amount_with_matching' => $this->getTotalAmountWithMatching(),
            'is_corporate' => $this->isCorporateDonation(),
            'created_at' => $this->getCreatedAt(),
        ];

        // Add corporate name only if not anonymous
        if (! $this->isAnonymous() && $this->getCorporateName()) {
            $data['corporate_name'] = $this->getCorporateName();
        }

        return $data;
    }
}

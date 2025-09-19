<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Service;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\ValueObject\Amount;
use Modules\Donation\Domain\ValueObject\PaymentIntent;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Domain\ValueObject\PaymentResult;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\ValueObject\Money;

/**
 * Core domain service for payment processing business logic
 */
class PaymentProcessingService
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway
    ) {}

    public function processDonation(
        Campaign $campaign,
        UserInterface $donor,
        Amount $amount,
        PaymentMethod $paymentMethod,
        bool $isAnonymous = false
    ): PaymentResult {
        // Validate campaign can accept donations
        if (! $campaign->canAcceptDonation()) {
            throw DonationException::campaignCannotAcceptDonation($campaign->getId());
        }

        // Create payment intent
        $paymentIntent = new PaymentIntent(
            donationId: 0, // Will be set after donation creation
            campaignId: $campaign->getId(),
            userId: $donor->getId(),
            amount: new Money($amount->getAmount(), $amount->currency),
            paymentMethod: $paymentMethod,
            description: "Donation to {$campaign->getTitle()}",
            returnUrl: config('app.url') . '/donation/return',
            cancelUrl: config('app.url') . '/donation/cancel',
            metadata: [
                'campaign_id' => $campaign->getId(),
                'donor_id' => $donor->getId(),
                'is_anonymous' => $isAnonymous,
            ]
        );

        // Process through gateway
        $result = $this->paymentGateway->createPaymentIntent($paymentIntent);

        // Handle corporate matching if enabled
        if ($campaign->has_corporate_matching && $result->status === PaymentStatus::SUCCEEDED) {
            $matchingAmount = $this->calculateCorporateMatching($campaign, $amount);

            if ($matchingAmount->getAmount() > 0) {
                $result = $result->withMatchingAmount($matchingAmount->getAmount());
            }
        }

        return $result;
    }

    public function confirmPayment(string $paymentIntentId): PaymentResult
    {
        return $this->paymentGateway->capturePayment($paymentIntentId);
    }

    public function calculateFees(Amount $amount, PaymentMethod $paymentMethod): Amount
    {
        // Business logic for fee calculation
        $feePercentage = match ($paymentMethod->getType()) {
            'credit_card' => 0.029, // 2.9%
            'bank_transfer' => 0.008, // 0.8%
            'digital_wallet' => 0.025, // 2.5%
            default => 0.030, // 3.0% default
        };

        $fixedFee = 0.30; // $0.30 fixed fee
        $calculatedFee = ($amount->getAmount() * $feePercentage) + $fixedFee;

        return new Amount($calculatedFee, $amount->currency);
    }

    public function calculateNetAmount(Amount $grossAmount, PaymentMethod $paymentMethod): Amount
    {
        $fees = $this->calculateFees($grossAmount, $paymentMethod);
        $netAmount = max(0, $grossAmount->getAmount() - $fees->getAmount());

        return new Amount($netAmount, $grossAmount->currency);
    }

    private function calculateCorporateMatching(Campaign $campaign, Amount $donationAmount): Amount
    {
        if (! $campaign->has_corporate_matching || ! $campaign->corporate_matching_rate) {
            return new Amount(0, $donationAmount->currency);
        }

        $matchingAmount = $donationAmount->getAmount() * $campaign->corporate_matching_rate;

        // Apply maximum matching limit if set
        if ($campaign->max_corporate_matching && $matchingAmount > $campaign->max_corporate_matching) {
            $matchingAmount = $campaign->max_corporate_matching;
        }

        return new Amount((float) $matchingAmount, $donationAmount->currency);
    }

    public function validateDonationLimits(Amount $amount, UserInterface $donor): void
    {
        // Minimum donation amount
        if ($amount->getAmount() < 1.00) {
            throw DonationException::belowMinimumAmount($amount->getAmount());
        }

        // Maximum single donation amount
        if ($amount->getAmount() > 10000.00) {
            throw DonationException::exceedsMaximumAmount($amount->getAmount());
        }

        // Additional business rules can be added here
        // e.g., daily limits, monthly limits, etc.
    }
}

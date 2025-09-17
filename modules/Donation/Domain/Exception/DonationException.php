<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Exception;

use Exception;
use Modules\Donation\Domain\Model\Donation;

class DonationException extends Exception
{
    public static function cannotProcess(Donation $donation): self
    {
        return new self(
            sprintf(
                'Donation %d cannot be processed. Current status: %s',
                $donation->id,
                $donation->status->getLabel(),
            ),
        );
    }

    public static function cannotCancel(Donation $donation): self
    {
        return new self(
            sprintf(
                'Donation %d cannot be cancelled. Current status: %s',
                $donation->id,
                $donation->status->getLabel(),
            ),
        );
    }

    public static function cannotRefund(Donation $donation): self
    {
        return new self(
            sprintf(
                'Donation %d cannot be refunded. Current status: %s',
                $donation->id,
                $donation->status->getLabel(),
            ),
        );
    }

    public static function invalidAmount(float $amount): self
    {
        return new self(sprintf('Invalid donation amount: %s', $amount));
    }

    public static function paymentGatewayNotSupported(string $gateway): self
    {
        return new self(sprintf('Payment gateway "%s" is not supported', $gateway));
    }

    public static function transactionFailed(string $reason): self
    {
        return new self(sprintf('Transaction failed: %s', $reason));
    }

    public static function campaignNotAcceptingDonations(int $campaignId): self
    {
        return new self(sprintf('Campaign %d is not accepting donations', $campaignId));
    }

    public static function notFound(?int $id = null): self
    {
        return $id
            ? new self("Donation with ID {$id} not found")
            : new self('Donation not found');
    }

    public static function unauthorizedAccess(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Unauthorized access to donation {$donation->id}")
            : new self('Unauthorized access to donation');
    }

    public static function cannotComplete(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Donation {$donation->id} cannot be completed")
            : new self('Donation cannot be completed');
    }

    public static function cannotFail(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Donation {$donation->id} cannot be failed")
            : new self('Donation cannot be failed');
    }

    public static function cannotBeCancelled(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Donation {$donation->id} cannot be cancelled")
            : new self('Donation cannot be cancelled');
    }

    public static function cannotBeCompleted(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Donation {$donation->id} cannot be completed")
            : new self('Donation cannot be completed');
    }

    public static function cannotBeRefunded(?Donation $donation = null): self
    {
        return $donation instanceof Donation
            ? new self("Donation {$donation->id} cannot be refunded")
            : new self('Donation cannot be refunded');
    }

    public static function campaignCannotAcceptDonation(int $campaignId): self
    {
        return new self("Campaign {$campaignId} cannot accept donations at this time");
    }

    public static function belowMinimumAmount(float $amount): self
    {
        return new self(sprintf('Donation amount %.2f is below the minimum allowed amount', $amount));
    }

    public static function exceedsMaximumAmount(float $amount): self
    {
        return new self(sprintf('Donation amount %.2f exceeds the maximum allowed amount', $amount));
    }
}

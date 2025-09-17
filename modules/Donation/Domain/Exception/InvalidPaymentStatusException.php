<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Exception;

final class InvalidPaymentStatusException extends DonationException
{
    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Invalid payment status transition from "%s" to "%s".', $from, $to));
    }

    public static function unknownStatus(string $status): self
    {
        return new self(sprintf('Unknown payment status: "%s".', $status));
    }

    public static function alreadyInStatus(string $status): self
    {
        return new self(sprintf('Payment is already in "%s" status.', $status));
    }
}

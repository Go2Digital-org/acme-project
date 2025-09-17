<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Exception;

final class DonationNotFoundException extends DonationException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Donation with ID %d not found.', $id));
    }

    public static function withTransactionId(string $transactionId): self
    {
        return new self(sprintf('Donation with transaction ID "%s" not found.', $transactionId));
    }
}

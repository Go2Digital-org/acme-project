<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Exception;

use Modules\Shared\Domain\Exception\ApiException;

final class DonationNotAllowedException extends ApiException
{
    protected int $statusCode = 409;

    public static function campaignClosed(int $campaignId): self
    {
        return new self(
            message: "Campaign {$campaignId} is no longer accepting donations.",
            statusCode: 409,
        );
    }

    public static function goalAlreadyReached(int $campaignId): self
    {
        return new self(
            message: "Campaign {$campaignId} has already reached its goal.",
            statusCode: 409,
        );
    }

    public static function campaignExpired(int $campaignId): self
    {
        return new self(
            message: "Campaign {$campaignId} has expired.",
            statusCode: 409,
        );
    }
}

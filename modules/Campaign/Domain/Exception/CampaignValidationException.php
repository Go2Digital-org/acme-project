<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Exception;

use Modules\Shared\Domain\Exception\ApiException;

final class CampaignValidationException extends ApiException
{
    protected int $statusCode = 422;

    public static function invalidDateRange(string $startDate, string $endDate): self
    {
        return new self(
            message: 'Invalid date range: End date must be after start date.',
            details: [
                'end_date' => ["End date ({$endDate}) must be after start date ({$startDate})."],
            ],
            statusCode: 422,
        );
    }

    public static function invalidGoalAmount(float $amount): self
    {
        return new self(
            message: 'Invalid goal amount.',
            details: [
                'goal_amount' => ["Goal amount must be greater than 0. Provided: {$amount}"],
            ],
            statusCode: 422,
        );
    }

    public static function cannotDeleteWithDonations(): self
    {
        return new self(
            message: 'Cannot delete campaign that has received donations.',
            statusCode: 409,
        );
    }

    public static function unauthorizedAction(string $action): self
    {
        return new self(
            message: "You are not authorized to {$action} this campaign.",
            statusCode: 403,
        );
    }
}

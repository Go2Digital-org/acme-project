<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Exception;

use DomainException;
use Modules\Campaign\Domain\Model\Campaign;

class CampaignException extends DomainException
{
    public static function cannotAcceptDonation(Campaign $campaign): self
    {
        return new self(
            "Campaign '{$campaign->title}' cannot accept donations. Status: {$campaign->status->getLabel()}",
        );
    }

    public static function invalidDateRange(string $startDate, string $endDate): self
    {
        return new self(
            "Invalid date range: start date ({$startDate}) must be before end date ({$endDate})",
        );
    }

    public static function campaignNotFound(int $id): self
    {
        return new self("Campaign with ID {$id} not found");
    }

    public static function invalidGoalAmount(float $amount): self
    {
        return new self("Goal amount must be greater than 0, got: {$amount}");
    }

    public static function notFound(?int $id = null): self
    {
        return $id
            ? new self("Campaign with ID {$id} not found")
            : new self('Campaign not found');
    }

    public static function unauthorizedAccess(?Campaign $campaign = null): self
    {
        return $campaign instanceof Campaign
            ? new self("Unauthorized access to campaign '{$campaign->title}'")
            : new self('Unauthorized access to campaign');
    }

    public static function cannotActivate(?Campaign $campaign = null): self
    {
        return $campaign instanceof Campaign
            ? new self("Campaign '{$campaign->title}' cannot be activated")
            : new self('Campaign cannot be activated');
    }

    public static function cannotComplete(?Campaign $campaign = null): self
    {
        return $campaign instanceof Campaign
            ? new self("Campaign '{$campaign->title}' cannot be completed")
            : new self('Campaign cannot be completed');
    }

    public static function cannotDelete(?Campaign $campaign = null): self
    {
        if ($campaign instanceof Campaign) {
            if ($campaign->current_amount > 0) {
                return new self("Campaign '{$campaign->title}' cannot be deleted because it has received donations.");
            }
            if ($campaign->status->value === 'completed') {
                return new self("Campaign '{$campaign->title}' cannot be deleted because it is completed.");
            }

            return new self("Campaign '{$campaign->title}' cannot be deleted");
        }

        return new self('Campaign cannot be deleted');
    }

    public static function cannotUpdate(?Campaign $campaign = null): self
    {
        return $campaign instanceof Campaign
            ? new self("Campaign '{$campaign->title}' cannot be updated")
            : new self('Campaign cannot be updated');
    }

    public static function organizationCannotCreateCampaigns(): self
    {
        return new self('Organization cannot create campaigns');
    }

    public static function invalidStatusTransition(mixed $fromStatus, mixed $toStatus): self
    {
        return new self("Invalid status transition from '{$fromStatus}' to '{$toStatus}'");
    }
}

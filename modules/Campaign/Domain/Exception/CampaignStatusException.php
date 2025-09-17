<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Exception;

final class CampaignStatusException extends CampaignException
{
    public static function cannotPublishInactive(): self
    {
        return new self('Cannot publish an inactive campaign.');
    }

    public static function cannotPublishAlreadyPublished(): self
    {
        return new self('Campaign is already published.');
    }

    public static function cannotCompleteInactive(): self
    {
        return new self('Cannot complete an inactive campaign.');
    }

    public static function cannotCompleteAlreadyCompleted(): self
    {
        return new self('Campaign is already completed.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Invalid status transition from "%s" to "%s".', $from, $to));
    }

    public static function alreadyInStatus(string $status): self
    {
        return new self(sprintf('Campaign is already in "%s" status.', $status));
    }
}

<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Exception;

final class CampaignNotFoundException extends CampaignException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Campaign with ID %d not found.', $id));
    }

    public static function withSlug(string $slug): self
    {
        return new self(sprintf('Campaign with slug "%s" not found.', $slug));
    }
}

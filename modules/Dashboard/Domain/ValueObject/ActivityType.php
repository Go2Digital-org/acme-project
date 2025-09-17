<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\ValueObject;

enum ActivityType: string
{
    public function getLabel(): string
    {
        return match ($this) {
            self::DONATION => 'Made a donation',
            self::CAMPAIGN_CREATED => 'Created a campaign',
            self::CAMPAIGN_BOOKMARKED => 'Bookmarked a campaign',
            self::CAMPAIGN_SHARED => 'Shared a campaign',
            self::MILESTONE_REACHED => 'Reached a milestone',
            self::PROFILE_UPDATED => 'Updated profile',
            self::TEAM_JOINED => 'Joined a team',
        };
    }
    case DONATION = 'donation';
    case CAMPAIGN_CREATED = 'campaign_created';
    case CAMPAIGN_BOOKMARKED = 'campaign_bookmarked';
    case CAMPAIGN_SHARED = 'campaign_shared';
    case MILESTONE_REACHED = 'milestone_reached';
    case PROFILE_UPDATED = 'profile_updated';
    case TEAM_JOINED = 'team_joined';
}

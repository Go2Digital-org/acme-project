<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Event;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Team\Domain\ValueObject\Role;
use Modules\Team\Domain\ValueObject\TeamId;

/**
 * Team member added domain event
 */
class TeamMemberAddedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly TeamId $teamId,
        public readonly int $userId,
        public readonly Role $role,
        public readonly int $addedBy
    ) {}
}

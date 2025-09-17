<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Event;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Team\Domain\ValueObject\TeamId;

/**
 * Team created domain event
 */
class TeamCreatedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly TeamId $teamId,
        public readonly int $organizationId,
        public readonly int $ownerId,
        public readonly string $teamName
    ) {}
}

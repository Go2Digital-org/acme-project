<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting user notification preferences.
 */
final readonly class GetNotificationPreferencesQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public bool $includeDefaults = true,
        public ?string $channel = null,
    ) {}
}

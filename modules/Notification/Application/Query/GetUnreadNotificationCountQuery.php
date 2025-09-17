<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get unread notification count for a user.
 */
final readonly class GetUnreadNotificationCountQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
    ) {}
}

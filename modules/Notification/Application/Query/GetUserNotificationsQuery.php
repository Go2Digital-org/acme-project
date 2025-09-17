<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get notifications for a specific user.
 */
final readonly class GetUserNotificationsQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
        public int $page = 1,
        public int $perPage = 20,
        public ?string $status = null,
        public ?string $type = null,
        public bool $unreadOnly = false,
        public ?string $readAt = null,
    ) {}
}

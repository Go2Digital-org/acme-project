<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting detailed notification information.
 */
final readonly class GetNotificationDetailsQuery implements QueryInterface
{
    public function __construct(
        public int $notificationId,
        public int $userId,
        public bool $includeMetadata = true,
        public bool $includeEvents = false,
        public bool $markAsRead = false,
    ) {}
}

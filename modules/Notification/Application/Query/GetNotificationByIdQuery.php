<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetNotificationByIdQuery implements QueryInterface
{
    public function __construct(
        public int $notificationId,
        public ?int $userId = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class BulkSendNotificationsCommand implements CommandInterface
{
    /**
     * @param  array<int, int>  $notificationIds
     * @param  array<string>  $channels
     */
    public function __construct(
        public array $notificationIds,
        public ?array $channels = null,
        public bool $force = false,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class BulkSendNotificationsCommand implements CommandInterface
{
    /**
     * @param  array<string>  $notificationIds
     * @param  array<string>|null  $channels
     */
    public function __construct(
        /** @var array<string, mixed> */
        public array $notificationIds,
        public ?array $channels = null,
        public bool $force = false,
    ) {}
}

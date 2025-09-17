<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for marking multiple notifications as read.
 */
final readonly class BulkMarkAsReadCommand implements CommandInterface
{
    /**
     * @param  array<int>  $notificationIds
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $userId,
        public array $notificationIds = [],
        public bool $markAllAsRead = false,
        public ?string $type = null,
        public ?string $channel = null,
        public array $metadata = [],
    ) {}
}

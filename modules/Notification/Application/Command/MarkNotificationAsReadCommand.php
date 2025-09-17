<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to mark a notification as read.
 */
final readonly class MarkNotificationAsReadCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $notificationId,
        public string $userId,
        public array $context = [],
    ) {}
}

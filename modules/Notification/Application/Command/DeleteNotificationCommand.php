<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for deleting a notification.
 */
final readonly class DeleteNotificationCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $notificationId,
        public int $userId,
        public bool $hardDelete = false,
        public ?string $reason = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}
}

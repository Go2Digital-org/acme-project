<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for canceling a scheduled notification.
 */
final readonly class CancelScheduledNotificationCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $notificationId,
        public int $userId,
        public ?string $reason = null,
        public bool $cancelRecurring = false,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}
}

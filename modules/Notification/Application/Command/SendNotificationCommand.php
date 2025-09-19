<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to send a notification immediately or schedule it for later.
 */
final readonly class SendNotificationCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $deliveryOptions
     */
    public function __construct(
        public string $notificationId,
        public bool $forceImmediate = false,
        /** @var array<string, mixed> */
        public array $deliveryOptions = [],
    ) {}
}

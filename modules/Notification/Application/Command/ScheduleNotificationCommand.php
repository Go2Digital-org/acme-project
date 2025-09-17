<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for scheduling a notification to be sent at a specific time.
 */
final readonly class ScheduleNotificationCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     * @param  ?array<string, mixed>  $recurringConfig
     */
    public function __construct(
        public int $recipientId,
        public ?int $senderId,
        public string $title,
        public string $message,
        public string $type,
        public string $channel,
        public string $priority,
        public CarbonInterface $scheduledFor,
        public array $data = [],
        public array $metadata = [],
        public ?string $scheduleId = null,
        public bool $recurring = false,
        public ?array $recurringConfig = null,
    ) {}
}

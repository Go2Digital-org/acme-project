<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\CarbonInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for creating multiple notifications in a single operation.
 */
final readonly class BulkCreateNotificationsCommand implements CommandInterface
{
    /**
     * @param array<array{
     *     notifiable_id: int,
     *     sender_id: ?int,
     *     title: string,
     *     message: string,
     *     type: string,
     *     channel: string,
     *     priority: string,
     *     data: array<string, mixed>,
     *     metadata: array<string, mixed>,
     *     scheduled_for: ?CarbonInterface
     * }> $notifications
     * @param  array<string, mixed>  $globalMetadata
     */
    public function __construct(
        /** @var array<string, mixed> */
        public array $notifications,
        public ?int $batchId = null,
        public ?string $source = null,
        /** @var array<string, mixed> */
        public array $globalMetadata = [],
    ) {}
}

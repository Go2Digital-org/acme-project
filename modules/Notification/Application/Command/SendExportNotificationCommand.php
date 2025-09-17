<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class SendExportNotificationCommand implements CommandInterface
{
    public const STATUS_STARTED = 'started';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        public int $exportId,
        public int $userId,
        public string $status,
        public string $exportType,
        public ?string $fileName = null,
        public ?string $downloadUrl = null,
        public ?string $errorMessage = null,
        public ?int $recordCount = null,
    ) {}
}

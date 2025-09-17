<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class LogAuditEventCommand implements CommandInterface
{
    public function __construct(
        public string $event,
        public string $auditableType,
        public int $auditableId,
        public ?int $userId = null,
        public ?string $userType = null,
        /** @var array<string, mixed>|null */
        public ?array $oldValues = null,
        /** @var array<string, mixed>|null */
        public ?array $newValues = null,
        public ?string $url = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $tags = null
    ) {}
}

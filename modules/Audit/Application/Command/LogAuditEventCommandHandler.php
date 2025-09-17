<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class LogAuditEventCommandHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    public function handle(LogAuditEventCommand $command): Audit
    {
        return $this->repository->create([
            'event' => $command->event,
            'auditable_type' => $command->auditableType,
            'auditable_id' => $command->auditableId,
            'user_id' => $command->userId,
            'user_type' => $command->userType,
            'old_values' => $command->oldValues,
            'new_values' => $command->newValues,
            'url' => $command->url,
            'ip_address' => $command->ipAddress,
            'user_agent' => $command->userAgent,
            'tags' => $command->tags,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

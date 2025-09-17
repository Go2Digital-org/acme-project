<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class GetAuditLogQueryHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    public function handle(GetAuditLogQuery $query): ?Audit
    {
        return $this->repository->findById($query->auditId);
    }
}

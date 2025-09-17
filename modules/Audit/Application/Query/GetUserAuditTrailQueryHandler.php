<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class GetUserAuditTrailQueryHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    /** @return LengthAwarePaginator<int, Audit> */
    public function handle(GetUserAuditTrailQuery $query): LengthAwarePaginator
    {
        $queryBuilder = $this->repository->newQuery()
            ->where('user_id', $query->userId)
            ->with(['auditable', 'user']);

        if ($query->startDate !== null) {
            $queryBuilder->where('created_at', '>=', $query->startDate);
        }

        if ($query->endDate !== null) {
            $queryBuilder->where('created_at', '<=', $query->endDate);
        }

        if ($query->event !== null) {
            $queryBuilder->where('event', $query->event);
        }

        if ($query->auditableType !== null) {
            $queryBuilder->where('auditable_type', $query->auditableType);
        }

        return $queryBuilder
            ->orderBy('created_at', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}

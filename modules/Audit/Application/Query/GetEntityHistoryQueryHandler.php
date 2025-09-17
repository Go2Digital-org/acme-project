<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class GetEntityHistoryQueryHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    /** @return LengthAwarePaginator<int, Audit> */
    public function handle(GetEntityHistoryQuery $query): LengthAwarePaginator
    {
        $queryBuilder = $this->repository->newQuery()
            ->where('auditable_type', $query->auditableType)
            ->where('auditable_id', $query->auditableId)
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

        return $queryBuilder
            ->orderBy('created_at', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}

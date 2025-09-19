<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class SearchAuditLogsQueryHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    /** @return LengthAwarePaginator<int, Audit> */
    public function handle(SearchAuditLogsQuery $query): LengthAwarePaginator
    {
        $queryBuilder = $this->repository->newQuery()->with(['auditable', 'user']);

        // Search functionality
        if ($query->search !== null) {
            $searchTerm = '%' . $query->search . '%';
            $queryBuilder->where(function ($q) use ($searchTerm): void {
                $q->where('event', 'like', $searchTerm)
                    ->orWhere('url', 'like', $searchTerm)
                    ->orWhere('ip_address', 'like', $searchTerm)
                    ->orWhere('user_agent', 'like', $searchTerm)
                    ->orWhere('tags', 'like', $searchTerm)
                    ->orWhereRaw('JSON_SEARCH(old_values, "all", ?) IS NOT NULL', [$searchTerm])
                    ->orWhereRaw('JSON_SEARCH(new_values, "all", ?) IS NOT NULL', [$searchTerm]);
            });
        }

        // Filter by event
        if ($query->event !== null) {
            $queryBuilder->where('event', $query->event);
        }

        // Filter by auditable type
        if ($query->auditableType !== null) {
            $queryBuilder->where('auditable_type', $query->auditableType);
        }

        // Filter by auditable ID
        if ($query->auditableId !== null) {
            $queryBuilder->where('auditable_id', $query->auditableId);
        }

        // Filter by user
        if ($query->userId !== null) {
            $queryBuilder->where('user_id', $query->userId);
        }

        // Date range filters
        if ($query->startDate !== null) {
            $queryBuilder->where('created_at', '>=', $query->startDate);
        }

        if ($query->endDate !== null) {
            $queryBuilder->where('created_at', '<=', $query->endDate);
        }

        // Sorting
        $sortBy = in_array($query->sortBy, ['created_at', 'event', 'auditable_type', 'user_id'])
            ? ($query->sortBy ?? 'created_at')
            : 'created_at';

        $sortOrder = in_array(strtolower($query->sortOrder ?? ''), ['asc', 'desc'])
            ? ($query->sortOrder ?? 'desc')
            : 'desc';

        return $queryBuilder
            ->orderBy($sortBy, $sortOrder)
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}

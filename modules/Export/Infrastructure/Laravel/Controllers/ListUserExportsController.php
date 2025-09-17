<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Query\GetUserExportsQuery;
use Modules\Export\Application\QueryHandler\GetUserExportsQueryHandler;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class ListUserExportsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private GetUserExportsQueryHandler $handler,
    ) {}

    /**
     * List user's export history with pagination and filtering.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:pending,processing,completed,failed,cancelled'],
            'resource_type' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'in:created_at,completed_at,status,format'],
            'sort_order' => ['nullable', 'in:asc,desc'],
        ]);

        $user = $this->getAuthenticatedUser($request);

        $status = null;
        if ($request->query('status')) {
            $status = ExportStatus::tryFrom($request->query('status'));
        }

        $query = new GetUserExportsQuery(
            userId: $user->id,
            page: max((int) $request->query('page', 1), 1),
            perPage: min((int) $request->query('per_page', 20), 100),
            status: $status,
            resourceType: $request->query('resource_type'),
            sortBy: $request->query('sort_by', 'created_at'),
            sortOrder: $request->query('sort_order', 'desc'),
        );

        $result = $this->handler->handle($query);

        return ApiResponse::success(
            data: $result->toArray(),
            message: 'User exports retrieved successfully.',
        );
    }
}

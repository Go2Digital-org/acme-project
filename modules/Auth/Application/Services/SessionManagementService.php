<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Service;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Application\Query\GetUserSessionsQuery;
use Modules\Auth\Application\Query\GetUserSessionsQueryHandler;

final readonly class SessionManagementService
{
    public function __construct(
        private GetUserSessionsQueryHandler $getUserSessionsHandler,
    ) {}

    /** @return array<array-key, mixed> */
    public function getUserSessions(int $userId): array
    {
        return $this->getUserSessionsHandler->handle(
            new GetUserSessionsQuery($userId),
        );
    }

    public function deleteSession(string $sessionId): void
    {
        /** @var Builder $queryBuilder */
        $queryBuilder = DB::table('sessions');
        $queryBuilder->where('id', $sessionId)->delete();
    }

    public function deleteOtherSessions(int $userId, string $currentSessionId): void
    {
        /** @var Builder $queryBuilder */
        $queryBuilder = DB::table('sessions');
        $queryBuilder
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}

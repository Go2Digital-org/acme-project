<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Application\Query\GetDashboardCacheStatusQuery;
use Modules\Dashboard\Application\Query\GetDashboardCacheStatusQueryHandler;
use Modules\Dashboard\Infrastructure\ApiPlatform\Resource\DashboardCacheStatusResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProviderInterface<DashboardCacheStatusResource>
 */
final readonly class DashboardCacheStatusProvider implements ProviderInterface
{
    public function __construct(
        private GetDashboardCacheStatusQueryHandler $queryHandler
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): DashboardCacheStatusResource {
        // Authentication is handled by Sanctum middleware at the route level
        // The user will always be authenticated when this provider runs
        $user = Auth::user();

        // If somehow no user (shouldn't happen with middleware), use a default response
        if (! $user) {
            return DashboardCacheStatusResource::fromCacheStatus(
                status: 'error',
                ready: false,
                progress: null,
                cacheDetails: ['error' => 'Authentication required']
            );
        }

        $userId = $user->id;

        $query = new GetDashboardCacheStatusQuery(userId: $userId);
        $cacheStatus = $this->queryHandler->handle($query);

        return DashboardCacheStatusResource::fromCacheStatus(
            status: $cacheStatus->status,
            ready: $cacheStatus->ready,
            progress: $cacheStatus->progress,
            cacheDetails: $cacheStatus->cacheDetails
        );
    }
}

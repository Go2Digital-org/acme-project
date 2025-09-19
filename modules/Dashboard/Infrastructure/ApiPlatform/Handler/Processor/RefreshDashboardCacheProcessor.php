<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use Modules\Dashboard\Application\Service\UserDashboardService;
use Modules\Dashboard\Infrastructure\ApiPlatform\Resource\DashboardResource;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @implements ProcessorInterface<DashboardResource, DashboardResource>
 */
final readonly class RefreshDashboardCacheProcessor implements ProcessorInterface
{
    public function __construct(
        private UserDashboardService $dashboardService,
        private Guard $auth
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): DashboardResource {
        $user = $this->auth->user();

        if (! $user) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'User must be authenticated');
        }

        $userId = $user->id;
        $force = $data->force ?? false;

        if ($force) {
            $status = $this->dashboardService->refreshUserDashboard($userId);
        } else {
            $completeData = $this->dashboardService->getCompleteUserDashboard($userId);
            $status = $completeData['cache_status']['overall_status'] ?? 'unknown';
        }

        $message = match ($status) {
            'warming_started' => 'Dashboard cache refresh has been initiated',
            'already_warming' => 'Cache refresh is already in progress',
            'cache_hit', 'hit' => 'Dashboard cache is ready and up to date',
            'miss' => 'Dashboard cache is being prepared',
            default => 'Dashboard cache status: ' . $status
        };

        return DashboardResource::refreshResponse(
            status: $status,
            message: $message
        );
    }
}

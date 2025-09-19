<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use Modules\Dashboard\Application\Command\InvalidateUserDashboardCacheCommand;
use Modules\Dashboard\Application\Command\InvalidateUserDashboardCacheCommandHandler;
use Modules\Dashboard\Infrastructure\ApiPlatform\Resource\DashboardCacheStatusResource;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @implements ProcessorInterface<DashboardCacheStatusResource, DashboardCacheStatusResource>
 */
final readonly class InvalidateDashboardCacheProcessor implements ProcessorInterface
{
    public function __construct(
        private InvalidateUserDashboardCacheCommandHandler $commandHandler,
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
    ): DashboardCacheStatusResource {
        $user = $this->auth->user();

        if (! $user) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'User must be authenticated');
        }

        $userId = $user->id;

        $command = new InvalidateUserDashboardCacheCommand(userId: $userId);
        $result = $this->commandHandler->handle($command);

        return DashboardCacheStatusResource::invalidateResponse(
            result: $result['result'],
            message: $result['message']
        );
    }
}

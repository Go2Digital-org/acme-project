<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Command;

use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class InvalidateUserDashboardCacheCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserDashboardCacheService $cacheService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(CommandInterface $command): array
    {
        assert($command instanceof InvalidateUserDashboardCacheCommand);

        $this->cacheService->invalidateUserCache($command->userId);

        return [
            'result' => 'success',
            'message' => 'Dashboard cache has been invalidated successfully',
        ];
    }
}

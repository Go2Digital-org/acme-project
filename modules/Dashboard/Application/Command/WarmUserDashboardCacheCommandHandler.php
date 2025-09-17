<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Command;

use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class WarmUserDashboardCacheCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserDashboardCacheService $cacheService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(CommandInterface $command): array
    {
        assert($command instanceof WarmUserDashboardCacheCommand);

        $result = $this->cacheService->warmUserCache($command->userId, $command->force);

        return [
            'result' => $result,
            'message' => match ($result) {
                'already_warming' => 'Cache warming is already in progress',
                'cache_hit' => 'Cache is already warm and ready',
                'warming_started' => 'Cache warming has been initiated',
                default => 'Unknown status'
            },
            'job_id' => $result === 'warming_started' ? uniqid('cache_warm_', true) : null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<object, array<array-key, mixed>>
 */
final readonly class ClearAllNotificationsProcessor implements ProcessorInterface
{
    /** @return array<array-key, mixed> */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        // Mark all unread notifications as read
        $user->unreadNotifications()->update(['read_at' => now()]);

        return [];
    }
}

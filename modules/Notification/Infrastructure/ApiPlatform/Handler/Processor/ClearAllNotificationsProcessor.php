<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<object, array<string, mixed>>
 */
final readonly class ClearAllNotificationsProcessor implements ProcessorInterface
{
    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        // Mark all unread notifications as read
        $user->unreadNotifications()->update(['read_at' => now()]);

        $unreadCount = $user->unreadNotifications()->count();

        return [
            'success' => true,
            'message' => 'All notifications marked as read',
            'unread_count' => $unreadCount,
        ];
    }
}

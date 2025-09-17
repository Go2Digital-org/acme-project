<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Modules\Shared\Infrastructure\ApiPlatform\Resource\NotificationResource;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<object, NotificationResource>
 */
final readonly class MarkNotificationAsReadProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): NotificationResource
    {
        $user = Auth::user();

        if (! ($user instanceof User)) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $notificationId = $uriVariables['id'];

        $notification = $user->notifications()->find($notificationId);

        if (! ($notification instanceof DatabaseNotification)) {
            throw new NotFoundHttpException('Notification not found');
        }

        if ($notification->getAttribute('read_at') === null) {
            $notification->markAsRead();
        }

        return NotificationResource::fromModel($notification);
    }
}

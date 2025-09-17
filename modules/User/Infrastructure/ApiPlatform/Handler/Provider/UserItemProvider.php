<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\User\Infrastructure\ApiPlatform\Resource\UserResource;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<UserResource>
 */
final readonly class UserItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        if (! isset($uriVariables['id']) || ! is_numeric($uriVariables['id'])) {
            throw new NotFoundHttpException('Invalid user ID');
        }

        $id = (int) $uriVariables['id'];

        $user = User::find($id);

        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        return UserResource::fromModel($user);
    }
}

<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<object, void>
 */
final readonly class DeleteUserProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! isset($uriVariables['id']) || ! is_numeric($uriVariables['id'])) {
            throw new NotFoundHttpException('Invalid user ID');
        }

        $id = (int) $uriVariables['id'];
        $currentUser = null;
        if (isset($context['request']) && method_exists($context['request'], 'user')) {
            $currentUser = $context['request']->user();
        }

        $user = User::find($id);

        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        // Prevent users from deleting their own account
        if ($currentUser && $currentUser->id === $user->id) {
            throw new UnprocessableEntityHttpException('Cannot delete your own account');
        }

        $user->delete();
    }
}

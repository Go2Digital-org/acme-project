<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Modules\Auth\Infrastructure\ApiPlatform\Resource\AuthenticationResource;

/**
 * @implements ProviderInterface<AuthenticationResource>
 */
final readonly class UserProfileProvider implements ProviderInterface
{
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): AuthenticationResource {
        $request = $context['request'] ?? throw new InvalidArgumentException('Request context is required');
        $user = $request->user();

        if ($user === null) {
            throw new InvalidArgumentException('User is not authenticated');
        }

        return new AuthenticationResource(
            user: [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'organization_id' => $user->organization_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            message: 'User profile retrieved successfully.',
        );
    }
}

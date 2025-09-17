<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Modules\Auth\Infrastructure\ApiPlatform\Resource\AuthenticationResource;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProcessorInterface<object, AuthenticationResource>
 */
final readonly class RegisterProcessor implements ProcessorInterface
{
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): AuthenticationResource {
        if (! property_exists($data, 'name') || ! property_exists($data, 'email') || ! property_exists($data, 'password')) {
            throw new InvalidArgumentException('Invalid registration data provided.');
        }

        /** @var string|null $name */
        $name = $data->name ?? null;
        /** @var string|null $email */
        $email = $data->email ?? null;
        /** @var string|null $password */
        $password = $data->password ?? null;
        /** @var int|null $organizationId */
        $organizationId = property_exists($data, 'organization_id') ? $data->organization_id : null;

        if ($name === null || $email === null || $password === null) {
            throw new InvalidArgumentException('Name, email and password are required.');
        }

        // Create new user
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ];

        if ($organizationId !== null) {
            $userData['organization_id'] = $organizationId;
        }

        $user = User::create($userData);

        // Generate API token
        $token = $user->createToken('api-token')->plainTextToken;

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
            token: $token,
            token_type: 'Bearer',
            message: 'Employee registered successfully.',
        );
    }
}

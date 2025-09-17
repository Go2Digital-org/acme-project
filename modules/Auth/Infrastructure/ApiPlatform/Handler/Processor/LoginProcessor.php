<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Infrastructure\ApiPlatform\Resource\AuthenticationResource;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProcessorInterface<object, AuthenticationResource>
 */
final readonly class LoginProcessor implements ProcessorInterface
{
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): AuthenticationResource {
        if (! property_exists($data, 'email') || ! property_exists($data, 'password')) {
            throw new HttpResponseException(
                ApiResponse::badRequest('Invalid login data provided.'),
            );
        }

        /** @var string|null $email */
        $email = $data->email ?? null;
        /** @var string|null $password */
        $password = $data->password ?? null;

        if ($email === null || $password === null) {
            throw new HttpResponseException(
                ApiResponse::badRequest('Email and password are required.'),
            );
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new HttpResponseException(
                ApiResponse::unauthorized('The provided credentials are incorrect.'),
            );
        }

        // Revoke existing tokens for security
        $user->tokens()->delete();

        // Create new token
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
            message: 'Successfully authenticated.',
        );
    }
}

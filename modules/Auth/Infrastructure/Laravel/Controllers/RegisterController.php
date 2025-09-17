<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\Request\RegisterRequest;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Application\Command\CreateUserCommand;
use Modules\User\Application\Command\CreateUserCommandHandler;
use Modules\User\Infrastructure\Laravel\Models\User;

final class RegisterController
{
    public function __construct(
        private readonly CreateUserCommandHandler $createUserHandler,
    ) {}

    /**
     * Handle employee registration and API token generation.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create user through command handler
        $command = new CreateUserCommand(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        $domainUser = $this->createUserHandler->handle($command);

        // Generate API token - need to use Laravel User model for token creation
        $laravelUser = User::find($domainUser->getId());

        if (! $laravelUser) {
            return ApiResponse::error('Failed to retrieve user for token generation');
        }

        $token = $laravelUser->createToken('api-token')->plainTextToken;

        return ApiResponse::created(
            data: [
                'user' => [
                    'id' => $laravelUser->id,
                    'name' => $laravelUser->name,
                    'email' => $laravelUser->email,
                    'email_verified_at' => $laravelUser->email_verified_at?->toISOString(),
                    'created_at' => $laravelUser->created_at?->toISOString(),
                    'updated_at' => $laravelUser->updated_at?->toISOString(),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            message: 'Employee registered successfully.',
        );
    }
}

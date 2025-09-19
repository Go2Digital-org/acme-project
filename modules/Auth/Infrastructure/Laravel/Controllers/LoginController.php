<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\Command\AuthenticateUserCommand;
use Modules\Auth\Application\Command\AuthenticateUserCommandHandler;
use Modules\Auth\Application\Request\LoginRequest;
use Modules\Auth\Domain\Exception\AuthenticationException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class LoginController
{
    public function __construct(
        private AuthenticateUserCommandHandler $authenticateUserHandler,
    ) {}

    /**
     * Handle employee authentication and API token generation.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        try {
            // Authenticate user through command handler
            $command = new AuthenticateUserCommand(
                email: $credentials['email'],
                password: $credentials['password'],
            );

            $domainUser = $this->authenticateUserHandler->handle($command);

            // Get Laravel User model for token operations
            $laravelUser = User::find($domainUser->getId());

            if (! $laravelUser) {
                return ApiResponse::error('Failed to retrieve user for token generation');
            }

            // Revoke existing tokens for security
            $laravelUser->tokens()->delete();

            // Create new token
            $token = $laravelUser->createToken('api-token')->plainTextToken;

            return ApiResponse::success(
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
                message: 'Successfully authenticated.',
                statusCode: 200,
            );
        } catch (AuthenticationException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }
    }
}

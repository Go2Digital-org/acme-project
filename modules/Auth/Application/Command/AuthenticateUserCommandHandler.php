<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Domain\Exception\AuthenticationException;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Infrastructure\Laravel\Models\User as LaravelUser;

/**
 * Authenticate User Command Handler.
 *
 * Handles user authentication through the domain layer.
 * Validates credentials and returns authenticated user.
 */
final readonly class AuthenticateUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Handle user authentication.
     *
     * @throws AuthenticationException
     */
    public function handle(AuthenticateUserCommand $command): User
    {
        $email = new EmailAddress($command->email);

        // Find user by email in domain
        $user = $this->userRepository->findByEmail($email);

        if (! $user instanceof User) {
            throw AuthenticationException::invalidCredentials();
        }

        // Verify password using infrastructure model
        $laravelUser = LaravelUser::where('email', $command->email)->first();

        if (! $laravelUser || ! Hash::check($command->password, $laravelUser->password)) {
            throw AuthenticationException::invalidCredentials();
        }

        return $user;
    }
}

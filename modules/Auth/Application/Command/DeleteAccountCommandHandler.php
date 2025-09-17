<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class DeleteAccountCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): null
    {
        if (! $command instanceof DeleteAccountCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = User::findOrFail($command->userId);

        // Verify password before deletion
        if (! Hash::check($command->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password does not match your current password.'],
            ]);
        }

        // Soft delete the user (or hard delete based on requirements)
        $user->delete();

        // Logout the user
        $authGuard = auth();
        $authGuard->logout();

        return null;
    }
}

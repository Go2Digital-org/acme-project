<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class UpdatePasswordCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): null
    {
        if (! $command instanceof UpdatePasswordCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = User::findOrFail($command->userId);

        // Verify current password
        if (! Hash::check($command->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($command->newPassword),
        ]);

        // Logout other devices for security
        $authGuard = auth();
        $authGuard->logoutOtherDevices($command->newPassword);

        return null;
    }
}

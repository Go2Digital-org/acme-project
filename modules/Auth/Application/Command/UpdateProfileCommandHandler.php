<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class UpdateProfileCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): null
    {
        if (! $command instanceof UpdateProfileCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = User::findOrFail($command->userId);

        $user->update([
            'name' => $command->name,
            'email' => $command->email,
        ]);

        if ($command->profilePhoto) {
            // Delete old profile photo if exists
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Update user with new profile photo path
            $user->update([
                'profile_photo_path' => $command->profilePhoto,
            ]);
        }

        return null;
    }
}

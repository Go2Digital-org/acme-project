<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Auth\Application\EventHandler\BroadcastAvatarUpdateHandler;
use Modules\Auth\Domain\Event\AvatarUpdatedEvent;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use RuntimeException;

final readonly class UpdateAvatarCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(CommandInterface $command): null
    {
        if (! $command instanceof UpdateAvatarCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = $this->userRepository->findById($command->userId);

        if (! $user instanceof User) {
            throw new RuntimeException('User not found');
        }

        // Delete old avatar if exists
        $oldPhotoPath = $user->getProfilePhotoPath();

        if ($oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        // Update user with new avatar path
        $user->updateProfilePhoto($command->imagePath);

        $this->userRepository->save($user);

        // Dispatch avatar updated event
        $photoUrl = Storage::disk('public')->url($command->imagePath);
        $event = new AvatarUpdatedEvent(
            userId: $user->getId(),
            photoUrl: $photoUrl,
            photoPath: $command->imagePath,
        );

        // Trigger event handler (in a real app, this would be handled by event dispatcher)
        $handler = new BroadcastAvatarUpdateHandler;
        $handler->handle($event);

        return null;
    }
}

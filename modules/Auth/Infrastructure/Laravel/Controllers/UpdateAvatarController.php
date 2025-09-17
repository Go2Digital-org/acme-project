<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\File;
use Modules\Auth\Application\Command\UpdateAvatarCommand;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class UpdateAvatarController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                File::image()
                    ->max(2048) // 2MB max
                    ->dimensions(new Dimensions([
                        'min_width' => 100,
                        'min_height' => 100,
                        'max_width' => 2000,
                        'max_height' => 2000,
                    ])),
            ],
        ]);

        $user = $this->getAuthenticatedUser($request);
        $avatarFile = $request->file('avatar');

        if ($avatarFile === null) {
            return response()->json(['message' => 'No file provided.'], 400);
        }

        // Store the avatar
        $storedPath = $avatarFile->store('profile-photos', 'public');

        if ($storedPath === false) {
            return response()->json(['message' => 'Failed to store avatar.'], 500);
        }

        // Update user avatar using command
        $command = new UpdateAvatarCommand(
            userId: $user->getId(),
            imagePath: $storedPath,
        );

        $this->commandBus->dispatch($command);

        $photoUrl = Storage::disk('public')->url($storedPath);

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'photo_url' => $photoUrl,
        ]);
    }
}

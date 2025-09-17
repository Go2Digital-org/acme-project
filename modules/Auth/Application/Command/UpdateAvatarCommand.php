<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdateAvatarCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public string $imagePath,
    ) {}
}

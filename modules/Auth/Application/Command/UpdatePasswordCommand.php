<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdatePasswordCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}

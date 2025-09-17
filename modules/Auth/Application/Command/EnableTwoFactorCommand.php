<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class EnableTwoFactorCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public bool $enable = true,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class DeleteUserCommand implements CommandInterface
{
    public function __construct(
        public int $id,
    ) {}
}

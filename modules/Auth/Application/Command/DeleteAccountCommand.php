<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class DeleteAccountCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public string $password,
    ) {}
}

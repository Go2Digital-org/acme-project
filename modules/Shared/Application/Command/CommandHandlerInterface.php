<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

interface CommandBusInterface
{
    /**
     * Handle a command and return its result.
     */
    public function handle(CommandInterface $command): mixed;

    /**
     * Dispatch a command without expecting a result.
     */
    public function dispatch(CommandInterface $command): void;
}

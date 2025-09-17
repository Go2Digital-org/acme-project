<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Bus;

use Modules\Shared\Application\Command\CommandInterface;
use Throwable;

/**
 * Command Bus interface that provides enhanced command handling capabilities.
 *
 * This interface extends the basic command handling with automatic transaction management,
 * event dispatching, and enhanced error handling. It serves as a decorator around
 * existing command handlers while maintaining backward compatibility.
 */
interface CommandBusInterface
{
    /**
     * Execute a command with automatic transaction management and event dispatching.
     *
     * This method wraps the command execution in a database transaction and
     * automatically dispatches any events after successful command completion.
     *
     * @param  CommandInterface  $command  The command to execute
     * @return mixed The result from the command handler
     *
     * @throws Throwable When command execution fails
     */
    public function execute(CommandInterface $command): mixed;

    /**
     * Dispatch a command without expecting a return value.
     *
     * This is a convenience method for fire-and-forget command execution
     * with the same transaction and event handling guarantees.
     *
     * @param  CommandInterface  $command  The command to dispatch
     *
     * @throws Throwable When command execution fails
     */
    public function dispatch(CommandInterface $command): void;

    /**
     * Check if a handler exists for the given command.
     *
     * @param  CommandInterface  $command  The command to check
     * @return bool True if a handler exists, false otherwise
     */
    public function hasHandler(CommandInterface $command): bool;

    /**
     * Get the handler class name for a given command.
     *
     * @param  CommandInterface  $command  The command to get handler for
     * @return string|null The handler class name or null if not found
     */
    public function getHandlerClass(CommandInterface $command): ?string;
}

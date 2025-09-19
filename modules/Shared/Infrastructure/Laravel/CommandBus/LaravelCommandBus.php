<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\CommandBus;

use Illuminate\Contracts\Container\Container;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use RuntimeException;

/**
 * Laravel Implementation of Command Bus.
 *
 * Maps commands to their respective handlers using Laravel's service container.
 */
final readonly class LaravelCommandBus implements CommandBusInterface
{
    /**
     * @param  array<class-string<CommandInterface>, class-string<CommandHandlerInterface>>  $commandToHandlerMap
     */
    public function __construct(
        private Container $container,
        /** @var array<class-string<CommandInterface>, class-string<CommandHandlerInterface>> */
        private array $commandToHandlerMap = [],
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        $handler = $this->resolveHandler($command);

        return $handler->handle($command);
    }

    public function dispatch(CommandInterface $command): void
    {
        $this->handle($command);
    }

    private function resolveHandler(CommandInterface $command): CommandHandlerInterface
    {
        $commandClass = $command::class;

        // First try the explicit mapping
        if (isset($this->commandToHandlerMap[$commandClass])) {
            $handlerClass = $this->commandToHandlerMap[$commandClass];
        } else {
            // Use convention: CommandName -> CommandNameHandler
            $handlerClass = $commandClass . 'Handler';
        }

        if (! class_exists($handlerClass)) {
            throw new RuntimeException(
                "Handler not found for command {$commandClass}. Expected: {$handlerClass}",
            );
        }

        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof CommandHandlerInterface) {
            throw new RuntimeException(
                "Handler {$handlerClass} must implement CommandHandlerInterface",
            );
        }

        return $handler;
    }
}

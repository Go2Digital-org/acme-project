<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Bus;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Application\Command\CommandBusInterface as LegacyCommandBusInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\Shared\Domain\Event\DomainEventInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Enhanced Command Bus implementation with transaction management and event handling.
 *
 * This implementation wraps existing command handlers with automatic:
 * - Database transaction management
 * - Event dispatching after successful execution
 * - Enhanced error handling and logging
 * - Handler discovery using conventions
 *
 * It maintains backward compatibility with existing handlers while adding
 * enterprise-grade command processing capabilities.
 *
 * Usage Examples:
 *
 * Basic command execution:
 * ```php
 * // Inject the CommandBusInterface into your controller or service
 * public function __construct(
 *     private readonly CommandBusInterface $commandBus
 * ) {}
 *
 * // Execute a command (automatically wrapped in transaction)
 * $result = $this->commandBus->execute(new CreateCampaignCommand(...));
 *
 * // Fire-and-forget execution
 * $this->commandBus->dispatch(new UpdateCampaignCommand(...));
 * ```
 *
 * Handler discovery verification:
 * ```php
 * // Check if a handler exists for a command
 * if ($this->commandBus->hasHandler($command)) {
 *     $result = $this->commandBus->execute($command);
 * }
 *
 * // Get the handler class name
 * $handlerClass = $this->commandBus->getHandlerClass($command);
 * ```
 *
 * All commands executed through this bus automatically get:
 * - Database transaction wrapping (rollback on failure)
 * - Event dispatching after successful completion
 * - Detailed logging with timing and error information
 * - Backward compatibility with existing handlers
 */
final class CommandBus implements CommandBusInterface, LegacyCommandBusInterface
{
    /**
     * Collection of events to dispatch after successful command execution
     *
     * @var array<DomainEventInterface>
     */
    private array $pendingEvents = [];

    /**
     * @param  array<class-string<CommandInterface>, class-string<CommandHandlerInterface>>  $commandToHandlerMap
     */
    public function __construct(private readonly Container $container, private readonly EventDispatcher $eventDispatcher, private readonly array $commandToHandlerMap = []) {}

    public function execute(CommandInterface $command): mixed
    {
        $commandClass = $command::class;
        $startTime = microtime(true);

        Log::debug('CommandBus: Executing command', [
            'command' => $commandClass,
            'command_data' => $this->sanitizeCommandData($command),
        ]);

        try {
            return DB::transaction(function () use ($command, $commandClass) {
                $handler = $this->resolveHandler($command);

                // Clear any pending events from previous executions
                $this->clearPendingEvents();

                // Execute the command
                $result = $handler->handle($command);

                // Dispatch any events that were collected during execution
                $this->dispatchPendingEvents();

                Log::info('CommandBus: Command executed successfully', [
                    'command' => $commandClass,
                    'events_dispatched' => count($this->pendingEvents),
                ]);

                return $result;
            });
        } catch (Throwable $exception) {
            $executionTime = microtime(true) - $startTime;

            Log::error('CommandBus: Command execution failed', [
                'command' => $commandClass,
                'execution_time_ms' => round($executionTime * 1000, 2),
                'error' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            // Clear any pending events on failure
            $this->clearPendingEvents();

            throw $exception;
        } finally {
            $executionTime = microtime(true) - $startTime;

            Log::debug('CommandBus: Command execution completed', [
                'command' => $commandClass,
                'execution_time_ms' => round($executionTime * 1000, 2),
            ]);
        }
    }

    public function dispatch(CommandInterface $command): void
    {
        $this->execute($command);
    }

    /**
     * Handle a command and return its result (legacy compatibility method).
     *
     * This method provides backward compatibility with the legacy CommandBusInterface.
     * It delegates to the execute method which provides the enhanced functionality.
     *
     * @param  CommandInterface  $command  The command to handle
     * @return mixed The result from the command handler
     *
     * @throws Throwable When command execution fails
     */
    public function handle(CommandInterface $command): mixed
    {
        return $this->execute($command);
    }

    public function hasHandler(CommandInterface $command): bool
    {
        try {
            $this->resolveHandler($command);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function getHandlerClass(CommandInterface $command): ?string
    {
        $commandClass = $command::class;

        // First try the explicit mapping
        if (isset($this->commandToHandlerMap[$commandClass])) {
            return $this->commandToHandlerMap[$commandClass];
        }

        // Use convention: CommandName -> CommandNameHandler
        $handlerClass = $commandClass . 'Handler';

        if (class_exists($handlerClass)) {
            return $handlerClass;
        }

        return null;
    }

    /**
     * Add an event to be dispatched after successful command execution.
     *
     * This method allows command handlers to register events that should be
     * dispatched only after the command completes successfully.
     */
    public function addPendingEvent(DomainEventInterface $event): void
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * Resolve the appropriate handler for the given command.
     *
     * Uses the same resolution strategy as the existing LaravelCommandBus
     * to maintain backward compatibility.
     *
     * @throws RuntimeException When no handler is found or handler is invalid
     */
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

    /**
     * Dispatch all pending events collected during command execution.
     */
    private function dispatchPendingEvents(): void
    {
        foreach ($this->pendingEvents as $event) {
            try {
                $this->eventDispatcher->dispatch($event);

                Log::debug('CommandBus: Event dispatched', [
                    'event' => $event::class,
                    'event_name' => $event instanceof DomainEventInterface ? $event->getEventName() : 'unknown',
                ]);
            } catch (Throwable $exception) {
                Log::warning('CommandBus: Failed to dispatch event', [
                    'event' => $event::class,
                    'error' => $exception->getMessage(),
                ]);

                // Don't re-throw event dispatch failures as they shouldn't
                // rollback the command transaction
            }
        }
    }

    /**
     * Clear all pending events.
     */
    private function clearPendingEvents(): void
    {
        $this->pendingEvents = [];
    }

    /**
     * Sanitize command data for logging (remove sensitive information).
     *
     * @return array<string, mixed>
     */
    private function sanitizeCommandData(CommandInterface $command): array
    {
        $data = [];

        // Use reflection to get public properties safely
        $reflection = new ReflectionClass($command);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $value = $property->getValue($command);

            // Sanitize sensitive fields
            if (str_contains(strtolower($propertyName), 'password') ||
                str_contains(strtolower($propertyName), 'token') ||
                str_contains(strtolower($propertyName), 'secret')) {
                $data[$propertyName] = '[REDACTED]';
            } else {
                $data[$propertyName] = $this->truncateValue($value);
            }
        }

        return $data;
    }

    /**
     * Truncate large values for logging.
     */
    private function truncateValue(mixed $value): mixed
    {
        if (is_string($value) && strlen($value) > 200) {
            return substr($value, 0, 200) . '... [truncated]';
        }

        if (is_array($value) && count($value) > 10) {
            return array_slice($value, 0, 10) + ['...' => '[truncated]'];
        }

        return $value;
    }
}

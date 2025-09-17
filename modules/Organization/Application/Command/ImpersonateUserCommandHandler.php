<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Organization\Domain\Service\ImpersonationService;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Throwable;

/**
 * Handler for user impersonation commands.
 *
 * This handler orchestrates the impersonation process, delegating to the domain
 * service for business logic and returning the redirect URL for the controller.
 */
final readonly class ImpersonateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    /**
     * Handle the impersonation command.
     *
     * @return string The redirect URL after successful impersonation
     *
     * @throws InvalidArgumentException If command type is invalid
     * @throws Throwable If impersonation fails
     */
    public function handle(CommandInterface $command): string
    {
        if (! $command instanceof ImpersonateUserCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Log the impersonation attempt
        Log::info('Processing impersonation request', [
            'token' => substr($command->token, 0, 20) . '...',
            'locale' => $command->locale,
            'tenant_initialized' => tenancy()->initialized,
            'tenant_id' => tenant()?->id,
            'session_id' => session()->getId(),
        ]);

        try {
            // Ensure we have a tenant context
            if (! tenancy()->initialized || ! tenant()) {
                throw new InvalidArgumentException('No tenant context initialized');
            }

            // Execute impersonation
            $tokenModel = $this->impersonationService->impersonateWithToken(
                $command->token,
                tenant()
            );

            // Log successful impersonation
            Log::info('Impersonation successful', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'guard' => $tokenModel->auth_guard, // @phpstan-ignore-line
                'session_id' => session()->getId(),
            ]);

            // Return the redirect URL
            return $this->impersonationService->getRedirectUrl($command->locale);

        } catch (Throwable $e) {
            Log::error('Impersonation failed', [
                'error' => $e->getMessage(),
                'token' => substr($command->token, 0, 20) . '...',
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

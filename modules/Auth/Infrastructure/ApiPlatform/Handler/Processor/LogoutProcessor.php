<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use Modules\Auth\Infrastructure\ApiPlatform\Resource\AuthenticationResource;

/**
 * @implements ProcessorInterface<object, AuthenticationResource>
 */
final readonly class LogoutProcessor implements ProcessorInterface
{
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): AuthenticationResource {
        $request = $context['request'] ?? null;

        // Revoke current token if request and user are available
        if ($request instanceof Request) {
            $user = $request->user();

            if ($user !== null) {
                $currentToken = $user->currentAccessToken();
                if ($currentToken) {
                    $currentToken->delete();
                }
                // Also revoke all tokens for extra security in tests
                $user->tokens()->delete();
            }
        }

        return new AuthenticationResource(
            message: 'Successfully logged out.',
        );
    }
}

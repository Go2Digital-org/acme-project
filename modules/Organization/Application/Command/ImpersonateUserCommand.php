<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command for user impersonation within a tenant context.
 *
 * This command encapsulates the data needed to perform user impersonation,
 * following the CQRS pattern in our hexagonal architecture.
 */
final readonly class ImpersonateUserCommand implements CommandInterface
{
    public function __construct(
        public string $token,
        public ?string $locale = null,
    ) {}
}

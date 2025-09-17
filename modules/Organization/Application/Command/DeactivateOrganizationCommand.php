<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class DeactivateOrganizationCommand implements CommandInterface
{
    public function __construct(
        public int $organizationId,
        public ?int $deactivatedByEmployeeId = null,
        public ?string $reason = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ToggleMaintenanceModeCommand implements CommandInterface
{
    public function __construct(
        public bool $enabled,
        public ?string $message,
        public ?string $allowedIps,
        public int $triggeredBy
    ) {}
}

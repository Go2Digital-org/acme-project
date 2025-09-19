<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Command;

final class CreateTenantCommand
{
    public function __construct(
        public string $name,
        public string $subdomain,
        public string $email,
        /** @var array<string, mixed> */
        public array $config = [],
        /** @var array<string, mixed> */
        public array $adminData = []
    ) {}
}

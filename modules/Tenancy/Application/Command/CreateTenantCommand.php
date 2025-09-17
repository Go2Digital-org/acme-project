<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Command;

final class CreateTenantCommand
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $adminData
     */
    public function __construct(
        public string $name,
        public string $subdomain,
        public string $email,
        public array $config = [],
        public array $adminData = []
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Command;

use Modules\Tenancy\Domain\Event\TenantCreatedEvent;
use Modules\Tenancy\Domain\Model\Tenant;
use Modules\Tenancy\Domain\Repository\TenantRepositoryInterface;
use Modules\Tenancy\Domain\ValueObject\TenantId;

final readonly class CreateTenantCommandHandler
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository
    ) {}

    public function handle(CreateTenantCommand $command): Tenant
    {
        $tenantId = TenantId::generate();

        $tenant = new Tenant;
        $tenant->id = $tenantId->toString();
        $tenant->subdomain = $command->subdomain;
        $tenant->database = 'tenant_' . $tenantId->toShortString();
        $tenant->data = array_merge($command->config, [
            'name' => $command->name,
            'email' => $command->email,
            'admin' => $command->adminData,
        ]);
        $tenant->provisioning_status = Tenant::STATUS_PENDING;

        $this->tenantRepository->save($tenant);

        // Dispatch domain event
        event(new TenantCreatedEvent($tenant));

        return $tenant;
    }
}

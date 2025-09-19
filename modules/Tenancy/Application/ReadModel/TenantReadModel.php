<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\ReadModel;

use Modules\Tenancy\Domain\Model\Tenant;

final class TenantReadModel
{
    public function __construct(
        public string $id,
        public string $name,
        public string $subdomain,
        public string $domain,
        public string $database,
        public string $email,
        /** @var array<string, mixed> */
        public array $config,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}

    public static function fromDomainModel(Tenant $tenant): self
    {
        return new self(
            id: $tenant->getId(),
            name: $tenant->getName(),
            subdomain: $tenant->getDomain(),
            domain: $tenant->getDomain(),
            database: $tenant->database ?? '',
            email: $tenant->data['email'] ?? '',
            config: $tenant->getConfig(),
            isActive: $tenant->isActive(),
            createdAt: $tenant->created_at->format('Y-m-d H:i:s'),
            updatedAt: $tenant->updated_at->format('Y-m-d H:i:s')
        );
    }
}

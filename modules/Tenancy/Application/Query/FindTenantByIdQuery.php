<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\Query;

use Modules\Tenancy\Domain\ValueObject\TenantId;

final class FindTenantByIdQuery
{
    public function __construct(
        public TenantId $tenantId
    ) {}
}

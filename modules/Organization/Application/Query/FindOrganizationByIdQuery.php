<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

readonly class FindOrganizationByIdQuery implements QueryInterface
{
    public function __construct(
        public int $organizationId,
    ) {}
}

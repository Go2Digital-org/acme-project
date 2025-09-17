<?php

declare(strict_types=1);

namespace Modules\Import\Application\Query;

final class GetImportStatusQuery
{
    public function __construct(
        public string $importJobId
    ) {}
}

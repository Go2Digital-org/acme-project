<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

final readonly class SuperSeedCommand implements CommandInterface
{
    public function __construct(
        public string $model,
        public int $count,
        public int $batchSize,
        public ?int $organizationId = null,
        public bool $autoIndex = false,
    ) {}
}

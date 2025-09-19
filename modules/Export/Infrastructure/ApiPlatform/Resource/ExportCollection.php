<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Resource;

/**
 * Collection wrapper for Export resources to satisfy API Platform generic constraints.
 */
readonly class ExportCollection
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $data,
        public int $current_page,
        public int $last_page,
        public int $per_page,
        public int $total,
    ) {}
}

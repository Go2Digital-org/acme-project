<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class IndexEntityCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $entityType,
        public string $entityId,
        public array $data,
        public bool $shouldQueue = true,
    ) {}

    /**
     * Create from entity model.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromEntity(string $type, string $id, array $data): self
    {
        return new self(
            entityType: $type,
            entityId: $id,
            data: $data,
            shouldQueue: true,
        );
    }
}

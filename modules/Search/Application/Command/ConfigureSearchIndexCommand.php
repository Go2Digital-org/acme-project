<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to configure search index settings.
 */
final readonly class ConfigureSearchIndexCommand implements CommandInterface
{
    /**
     * @param  class-string|null  $modelClass  Specific model to configure, or null for all
     * @param  array<string, mixed>  $settings  Custom settings to apply
     */
    public function __construct(
        public ?string $modelClass = null,
        /** @var array<string, mixed> */
        public array $settings = [],
    ) {}
}

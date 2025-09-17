<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetAdminSettingsQuery implements QueryInterface
{
    /**
     * @param  array<string>|null  $sections  ['general', 'maintenance', 'email', 'notifications']
     */
    public function __construct(
        public ?array $sections = null,
        public bool $includeSensitiveData = false
    ) {}
}

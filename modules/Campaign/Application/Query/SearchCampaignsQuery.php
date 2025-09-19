<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchCampaignsQuery implements QueryInterface
{
    public function __construct(
        public string $searchTerm = '',
        /** @var array<string, mixed> */
        public array $filters = [],
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc',
        public ?string $locale = null,
        public bool $searchTranslations = true,
        public bool $onlyCompleteTranslations = false,
    ) {}
}

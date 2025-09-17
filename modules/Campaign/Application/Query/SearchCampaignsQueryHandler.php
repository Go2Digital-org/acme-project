<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchCampaignsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        if (! $query instanceof SearchCampaignsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Set locale context if provided
        if ($query->locale) {
            App::setLocale($query->locale);
        }

        // Add search term to filters with translation support
        $filters = $query->filters;

        if ($query->searchTerm !== '') {
            $filters['search'] = $query->searchTerm;
            $filters['search_translations'] = $query->searchTranslations;
        }

        // Add translation completeness filter
        if ($query->onlyCompleteTranslations) {
            $filters['complete_translations'] = true;

            if ($query->locale) {
                $filters['complete_translations_locale'] = $query->locale;
            }
        }

        // Add locale filter for results
        if ($query->locale) {
            $filters['locale'] = $query->locale;
        }

        return $this->repository->searchWithTranslations(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder,
        );
    }
}

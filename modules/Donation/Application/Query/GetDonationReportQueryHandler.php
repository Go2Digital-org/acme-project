<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use InvalidArgumentException;
use Modules\Donation\Application\ReadModel\DonationReportReadModel;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationReportRepository;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for retrieving donation reports using read models.
 */
final readonly class GetDonationReportQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationReportRepository $repository,
    ) {}

    public function handle(QueryInterface $query): ?DonationReportReadModel
    {
        if (! $query instanceof GetDonationReportQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $reportId = $query->getReportId();

        // Combine all filters
        $filters = array_merge(
            $query->filters ?? [],
            $query->dateRange ?? []
        );

        if ($query->organizationId) {
            $filters['organization_id'] = $query->organizationId;
        }

        if ($query->campaignId) {
            $filters['campaign_id'] = $query->campaignId;
        }

        if ($query->forceRefresh) {
            $result = $this->repository->refresh($reportId);

            return $result instanceof DonationReportReadModel ? $result : null;
        }

        $result = $this->repository->find($reportId, $filters);

        return $result instanceof DonationReportReadModel ? $result : null;
    }
}

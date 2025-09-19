<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Export;

use Illuminate\Database\Eloquent\Builder;
use Modules\Donation\Domain\Model\Donation;

/**
 * Contract for donation export repository.
 * Used by the export system to access donation data.
 */
interface DonationExportRepositoryInterface extends ExportableRepositoryInterface
{
    /**
     * Get donations query with all required relationships.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Donation>
     */
    public function getDonationsWithRelations(array $filters = []): Builder;

    /**
     * Get donation summary query grouped by status.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Donation>
     */
    public function getDonationSummaryQuery(array $filters = []): Builder;

    /**
     * Get donations grouped by campaign query.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Donation>
     */
    public function getDonationsByCampaignQuery(array $filters = []): Builder;

    /**
     * Get donations grouped by employee query.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Donation>
     */
    public function getDonationsByEmployeeQuery(array $filters = []): Builder;
}

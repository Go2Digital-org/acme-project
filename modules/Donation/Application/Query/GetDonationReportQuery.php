<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for retrieving donation report data.
 */
final readonly class GetDonationReportQuery implements QueryInterface
{
    /**
     * @param  array<string, mixed>|null  $filters
     * @param  array<string, mixed>|null  $dateRange
     */
    public function __construct(
        public string $reportType,
        public ?array $filters = null,
        public ?array $dateRange = null,
        public ?int $organizationId = null,
        public ?int $campaignId = null,
        public bool $forceRefresh = false
    ) {}

    /**
     * Generate report ID based on parameters.
     */
    public function getReportId(): string
    {
        $parts = [$this->reportType];

        if ($this->organizationId) {
            $parts[] = 'org_' . $this->organizationId;
        }

        if ($this->campaignId) {
            $parts[] = 'campaign_' . $this->campaignId;
        }

        if ($this->dateRange) {
            $parts[] = 'date_' . md5(serialize($this->dateRange));
        }

        $parts[] = (string) time();

        return implode('_', $parts);
    }
}

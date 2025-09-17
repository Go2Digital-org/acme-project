<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Modules\Campaign\Infrastructure\Laravel\Repository\CampaignAnalyticsRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationReportRepository;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationDashboardRepository;
use Modules\Shared\Application\ReadModel\ReadModelCacheInvalidator;

/**
 * Command to refresh read model caches and rebuild data.
 */
class RefreshReadModelsCommand extends Command
{
    protected $signature = 'read-models:refresh 
                           {--type=* : Type of read models to refresh (campaign-analytics, donation-reports, organization-dashboard)}
                           {--id=* : Specific IDs to refresh}
                           {--organization=* : Organization IDs to filter by}
                           {--clear-cache : Clear cache without rebuilding}
                           {--force : Force refresh even if cache is valid}';

    protected $description = 'Refresh read model caches and rebuild data from domain models';

    private ReadModelCacheInvalidator $invalidator;

    private CampaignAnalyticsRepository $campaignAnalyticsRepo;

    private DonationReportRepository $donationReportRepo;

    private OrganizationDashboardRepository $organizationDashboardRepo;

    public function handle(): int
    {
        $this->invalidator = App::make(ReadModelCacheInvalidator::class);
        $this->campaignAnalyticsRepo = App::make(CampaignAnalyticsRepository::class);
        $this->donationReportRepo = App::make(DonationReportRepository::class);
        $this->organizationDashboardRepo = App::make(OrganizationDashboardRepository::class);

        $types = array_filter((array) $this->option('type'), fn ($value): bool => $value !== null);
        $ids = array_filter(array_map('intval', (array) $this->option('id')));
        $organizationIds = array_filter(array_map('intval', (array) $this->option('organization')));
        $clearCache = $this->option('clear-cache');
        $force = $this->option('force');

        if ($clearCache) {
            return $this->clearCaches($types);
        }

        if ($types === []) {
            $types = ['campaign-analytics', 'donation-reports', 'organization-dashboard'];
        }

        $this->info('Starting read model refresh...');

        foreach ($types as $type) {
            if ($type !== null) {
                $this->refreshReadModelType($type, $ids, $organizationIds, $force);
            }
        }

        $this->info('Read model refresh completed.');

        return 0;
    }

    /**
     * @param  array<string>  $types
     */
    private function clearCaches(array $types): int
    {
        $this->info('Clearing read model caches...');

        if ($types === []) {
            $this->invalidator->invalidateAll();
            $this->info('All read model caches cleared.');
        } else {
            foreach ($types as $type) {
                $this->clearCacheForType($type);
            }
        }

        return 0;
    }

    private function clearCacheForType(string $type): void
    {
        switch ($type) {
            case 'campaign-analytics':
                $this->campaignAnalyticsRepo->clearAllCache();
                $this->info('Campaign analytics cache cleared.');
                break;

            case 'donation-reports':
                $this->donationReportRepo->clearAllCache();
                $this->info('Donation reports cache cleared.');
                break;

            case 'organization-dashboard':
                $this->organizationDashboardRepo->clearAllCache();
                $this->info('Organization dashboard cache cleared.');
                break;

            default:
                $this->warn("Unknown read model type: {$type}");
        }
    }

    /**
     * @param  array<int>  $ids
     * @param  array<int>  $organizationIds
     */
    private function refreshReadModelType(string $type, array $ids, array $organizationIds, bool $force): void
    {
        $this->info("Refreshing {$type} read models...");

        match ($type) {
            'campaign-analytics' => $this->refreshCampaignAnalytics($ids, $organizationIds, $force),
            'donation-reports' => $this->refreshDonationReports($organizationIds, $force),
            'organization-dashboard' => $this->refreshOrganizationDashboards($ids ?: $organizationIds, $force),
            default => $this->warn("Unknown read model type: {$type}"),
        };
    }

    /**
     * @param  array<int>  $campaignIds
     * @param  array<int>  $organizationIds
     */
    private function refreshCampaignAnalytics(array $campaignIds, array $organizationIds, bool $force): void
    {
        if ($campaignIds !== []) {
            // Refresh specific campaigns
            $bar = $this->output->createProgressBar(count($campaignIds));
            $bar->start();

            foreach ($campaignIds as $campaignId) {
                $this->campaignAnalyticsRepo->refresh((int) $campaignId);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Refreshed ' . count($campaignIds) . ' campaign analytics.');

            return;
        }

        if ($organizationIds !== []) {
            // Refresh campaigns by organization
            foreach ($organizationIds as $orgId) {
                $this->campaignAnalyticsRepo->clearCacheForOrganization((int) $orgId);
                $this->info("Cleared campaign analytics cache for organization {$orgId}.");
            }

            return;
        }

        // Refresh all campaigns (use with caution)
        if ($force && $this->confirm('This will refresh ALL campaign analytics. Continue?')) {
            $this->campaignAnalyticsRepo->clearAllCache();
            $this->info('All campaign analytics cache cleared for lazy rebuild.');
        }
    }

    /**
     * @param  array<int>  $organizationIds
     */
    private function refreshDonationReports(array $organizationIds, bool $force): void
    {
        if ($organizationIds !== []) {
            // Generate fresh reports for specified organizations
            foreach ($organizationIds as $orgId) {
                $this->donationReportRepo->generateOrganizationReport((int) $orgId);
                $this->info("Generated fresh donation report for organization {$orgId}.");
            }

            return;
        }

        // Clear all report caches for lazy rebuild
        if ($force && $this->confirm('This will clear ALL donation report caches. Continue?')) {
            $this->donationReportRepo->clearAllCache();
            $this->info('All donation report caches cleared for lazy rebuild.');
        }
    }

    /**
     * @param  array<int>  $organizationIds
     */
    private function refreshOrganizationDashboards(array $organizationIds, bool $force): void
    {
        if ($organizationIds !== []) {
            // Refresh specific organizations
            $bar = $this->output->createProgressBar(count($organizationIds));
            $bar->start();

            foreach ($organizationIds as $orgId) {
                $this->organizationDashboardRepo->refresh((int) $orgId);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Refreshed ' . count($organizationIds) . ' organization dashboards.');

            return;
        }

        // Refresh all dashboards (use with caution)
        if ($force && $this->confirm('This will refresh ALL organization dashboards. Continue?')) {
            $this->organizationDashboardRepo->clearAllCache();
            $this->info('All organization dashboard cache cleared for lazy rebuild.');
        }
    }
}

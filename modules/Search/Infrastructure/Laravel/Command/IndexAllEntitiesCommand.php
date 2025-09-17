<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

class IndexAllEntitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:index
                            {entity? : The entity type to index (campaign, donation, user, organization, or all)}
                            {--fresh : Clear the index before importing}
                            {--chunk=1000 : The chunk size for batch processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index all entities or a specific entity type into Meilisearch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $entityArg = $this->argument('entity');
        $entity = is_string($entityArg) ? $entityArg : 'all';
        $fresh = (bool) $this->option('fresh');
        $chunkSize = (int) $this->option('chunk');

        $this->info("Starting indexing process for: {$entity}");

        if ($entity === 'all' || $entity === 'campaign') {
            $this->indexCampaigns($fresh, $chunkSize);
        }

        if ($entity === 'all' || $entity === 'donation') {
            $this->indexDonations($fresh, $chunkSize);
        }

        if ($entity === 'all' || $entity === 'user') {
            $this->indexUsers($fresh, $chunkSize);
        }

        if ($entity === 'all' || $entity === 'organization') {
            $this->indexOrganizations($fresh, $chunkSize);
        }

        $this->info('Indexing completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Index all campaigns.
     */
    private function indexCampaigns(bool $fresh, int $chunkSize): void
    {
        $this->info('Indexing campaigns...');

        if ($fresh) {
            $this->call('scout:flush', ['model' => Campaign::class]);
        }

        $this->call('scout:import', [
            'model' => Campaign::class,
            '--chunk' => $chunkSize,
        ]);

        $this->info('Campaigns indexed successfully.');
    }

    /**
     * Index all donations.
     */
    private function indexDonations(bool $fresh, int $chunkSize): void
    {
        $this->info('Indexing donations...');

        // Check if Donation model exists and has Searchable trait
        $donationClass = Donation::class;

        if (! class_exists($donationClass)) {
            $this->warn('Donation model not found or not searchable yet.');

            return;
        }

        if ($fresh) {
            $this->call('scout:flush', ['model' => $donationClass]);
        }

        $this->call('scout:import', [
            'model' => $donationClass,
            '--chunk' => $chunkSize,
        ]);

        $this->info('Donations indexed successfully.');
    }

    /**
     * Index all users.
     */
    private function indexUsers(bool $fresh, int $chunkSize): void
    {
        $this->info('Indexing users...');

        // Use the Laravel Eloquent User model for Scout, not the Domain model
        $userClass = User::class;

        if (! class_exists($userClass)) {
            $this->warn('User model not found or not searchable yet.');

            return;
        }

        if ($fresh) {
            $this->call('scout:flush', ['model' => $userClass]);
        }

        $this->call('scout:import', [
            'model' => $userClass,
            '--chunk' => $chunkSize,
        ]);

        $this->info('Users indexed successfully.');
    }

    /**
     * Index all organizations.
     */
    private function indexOrganizations(bool $fresh, int $chunkSize): void
    {
        $this->info('Indexing organizations...');

        // Check if Organization model exists and has Searchable trait
        $organizationClass = Organization::class;

        if (! class_exists($organizationClass)) {
            $this->warn('Organization model not found or not searchable yet.');

            return;
        }

        if ($fresh) {
            $this->call('scout:flush', ['model' => $organizationClass]);
        }

        $this->call('scout:import', [
            'model' => $organizationClass,
            '--chunk' => $chunkSize,
        ]);

        $this->info('Organizations indexed successfully.');
    }
}

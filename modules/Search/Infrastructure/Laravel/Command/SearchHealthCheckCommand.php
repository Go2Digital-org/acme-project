<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Modules\Search\Domain\Service\SearchEngineInterface;

class SearchHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health status of the search engine';

    public function __construct(
        private readonly SearchEngineInterface $searchEngine,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking search engine health...');

        try {
            $health = $this->searchEngine->health();

            if ($health['status'] === 'available' || $health['status'] === 'ok') {
                $this->info('✓ Search engine is healthy');

                // Check indexes
                $this->checkIndexes();

                return Command::SUCCESS;
            }

            $this->error('✗ Search engine is not available');

            if (isset($health['error'])) {
                $this->error('Error: ' . $health['error']);
            }

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error('✗ Failed to connect to search engine');
            $this->error('Error: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Check the status of all indexes.
     */
    private function checkIndexes(): void
    {
        $this->info('');
        $this->info('Checking indexes...');

        $indexes = [
            'acme_campaigns' => 'Campaigns',
            'acme_donations' => 'Donations',
            'acme_users' => 'Users',
            'acme_organizations' => 'Organizations',
        ];

        $headers = ['Index', 'Status', 'Documents'];
        $rows = [];

        foreach ($indexes as $indexName => $label) {
            try {
                if (! $this->searchEngine->indexExists($indexName)) {
                    $rows[] = [
                        $label,
                        '<fg=yellow>✗ Not found</>',
                        '-',
                    ];

                    continue;
                }

                $stats = $this->searchEngine->getIndexStats($indexName);
                $rows[] = [
                    $label,
                    '<fg=green>✓ Exists</>',
                    number_format($stats['numberOfDocuments'] ?? 0),
                ];
            } catch (Exception) {
                $rows[] = [
                    $label,
                    '<fg=red>✗ Error</>',
                    '-',
                ];
            }
        }

        $this->table($headers, $rows);
    }
}

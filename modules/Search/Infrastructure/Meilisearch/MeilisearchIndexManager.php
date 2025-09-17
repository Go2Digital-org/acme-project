<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Meilisearch;

use Exception;
use Meilisearch\Client;
use Modules\Search\Domain\Service\IndexManagerInterface;
use Modules\Search\Domain\ValueObject\IndexConfiguration;

class MeilisearchIndexManager implements IndexManagerInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function setupIndexes(): void
    {
        $indexes = [
            'acme_campaigns' => $this->getCampaignConfiguration(),
            'acme_donations' => $this->getDonationConfiguration(),
            'acme_users' => $this->getUserConfiguration(),
            'acme_organizations' => $this->getOrganizationConfiguration(),
        ];

        foreach ($indexes as $indexName => $config) {
            $this->createOrUpdateIndex($indexName, $config);
        }
    }

    public function reindexEntity(string $entityType): void
    {
        // This would trigger the reindexing process for a specific entity
        // Implementation would depend on your specific requirements
    }

    public function reindexAll(): void
    {
        $this->reindexEntity('campaign');
        $this->reindexEntity('donation');
        $this->reindexEntity('user');
        $this->reindexEntity('organization');
    }

    public function clearIndex(string $indexName): void
    {
        try {
            $index = $this->client->index($indexName);
            $task = $index->deleteAllDocuments();
            $this->client->waitForTask($task['taskUid']);
        } catch (Exception) {
            // Handle error
        }
    }

    public function getIndexConfiguration(string $indexName): IndexConfiguration
    {
        return match ($indexName) {
            'acme_campaigns' => $this->getCampaignConfiguration(),
            'acme_donations' => $this->getDonationConfiguration(),
            'acme_users' => $this->getUserConfiguration(),
            'acme_organizations' => $this->getOrganizationConfiguration(),
            default => new IndexConfiguration,
        };
    }

    public function optimizeIndex(string $indexName): void
    {
        // Meilisearch handles optimization automatically
        // This method could trigger specific optimization tasks if needed
    }

    public function listIndexes(): array
    {
        try {
            $indexes = $this->client->getIndexes();

            return array_map(fn (array $index) => $index['uid'], $indexes['results']);
        } catch (Exception) {
            return [];
        }
    }

    public function validateConfiguration(string $indexName): bool
    {
        $config = $this->getIndexConfiguration($indexName);

        return $config->validate();
    }

    public function getReindexProgress(string $entityType): array
    {
        // This would track the progress of reindexing
        // Implementation would depend on your specific requirements
        return [
            'total' => 0,
            'processed' => 0,
            'percentage' => 0.0,
        ];
    }

    /**
     * Create or update an index with configuration.
     */
    private function createOrUpdateIndex(string $indexName, IndexConfiguration $config): void
    {
        try {
            // Create index if it doesn't exist
            if (! $this->indexExists($indexName)) {
                $task = $this->client->createIndex($indexName, [
                    'primaryKey' => $config->primaryKey,
                ]);
                $this->client->waitForTask($task['taskUid']);
            }

            // Update settings
            $index = $this->client->index($indexName);
            $settings = $config->toMeilisearchSettings();
            $task = $index->updateSettings($settings);
            $this->client->waitForTask($task['taskUid']);
        } catch (Exception) {
            // Handle error
        }
    }

    /**
     * Check if an index exists.
     */
    private function indexExists(string $indexName): bool
    {
        try {
            $this->client->index($indexName)->stats();

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get campaign index configuration.
     */
    private function getCampaignConfiguration(): IndexConfiguration
    {
        return new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title', 'description', 'category', 'organization_name'],
            filterableFields: [
                'id',
                'status',
                'category',
                'organization_id',
                'start_date',
                'end_date',
                'goal_amount',
                'current_amount',
                'visibility',
                'is_featured',
            ],
            sortableFields: [
                'created_at',
                'updated_at',
                'start_date',
                'end_date',
                'goal_amount',
                'current_amount',
                'donations_count',
            ],
        );
    }

    /**
     * Get donation index configuration.
     */
    private function getDonationConfiguration(): IndexConfiguration
    {
        return new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['transaction_id', 'notes', 'donor_name', 'donor_email'],
            filterableFields: [
                'id',
                'campaign_id',
                'user_id',
                'status',
                'payment_method',
                'currency',
                'amount',
                'donated_at',
                'is_anonymous',
            ],
            sortableFields: [
                'amount',
                'donated_at',
                'created_at',
                'processed_at',
            ],
        );
    }

    /**
     * Get user index configuration.
     */
    private function getUserConfiguration(): IndexConfiguration
    {
        return new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['name', 'first_name', 'last_name', 'email', 'department', 'job_title'],
            filterableFields: [
                'id',
                'role',
                'department',
                'organization_id',
                'is_active',
                'user_id',
            ],
            sortableFields: [
                'name',
                'created_at',
                'last_login_at',
            ],
        );
    }

    /**
     * Get organization index configuration.
     */
    private function getOrganizationConfiguration(): IndexConfiguration
    {
        return new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['name', 'description', 'mission', 'category', 'address', 'city', 'country'],
            filterableFields: [
                'id',
                'category',
                'is_verified',
                'is_active',
                'country',
                'city',
            ],
            sortableFields: [
                'name',
                'created_at',
                'verification_date',
                'campaigns_count',
            ],
        );
    }
}

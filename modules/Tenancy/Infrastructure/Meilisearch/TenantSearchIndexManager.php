<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Meilisearch;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\Tenancy\Domain\Exception\TenantException;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Tenant Search Index Manager.
 *
 * Manages Meilisearch indexes for multi-tenant search functionality.
 * Each tenant gets isolated search indexes with a unique prefix.
 */
class TenantSearchIndexManager
{
    private readonly Client $client;

    public function __construct()
    {
        /** @var string $host */
        $host = config('scout.meilisearch.host');
        /** @var string $key */
        $key = config('scout.meilisearch.key');

        $this->client = new Client($host, $key);
    }

    /**
     * Create all search indexes for a tenant.
     *
     * @throws TenantException
     */
    public function createTenantIndexes(Organization $organization): void
    {
        $tenantPrefix = $this->getTenantPrefix($organization);

        try {
            // Define indexes and their searchable models
            $indexes = [
                'campaigns' => Campaign::class,
                'users' => User::class,
                'donations' => Donation::class,
            ];

            // Run within tenant context
            $organization->run(function () use ($indexes, $tenantPrefix): void {
                // Set tenant-specific prefix
                config(['scout.prefix' => $tenantPrefix]);

                foreach ($indexes as $indexName => $modelClass) {
                    $this->createIndex($tenantPrefix . $indexName, $modelClass);

                    // Import existing data if any
                    if (class_exists($modelClass)) {
                        Artisan::call('scout:import', [
                            'model' => $modelClass,
                        ]);
                    }
                }
            });

            Log::info('Search indexes created for tenant', [
                'organization_id' => $organization->id,
                'prefix' => $tenantPrefix,
                'indexes' => array_keys($indexes),
            ]);

        } catch (Exception $e) {
            throw TenantException::indexCreationFailed(
                (string) $organization->id,
                $e->getMessage()
            );
        }
    }

    /**
     * Create a single index with settings.
     */
    private function createIndex(string $indexName, string $modelClass): void
    {
        // Get or create index
        $index = $this->client->index($indexName);

        // Configure index settings based on model
        $settings = $this->getIndexSettings($modelClass);

        if ($settings !== []) {
            $index->updateSettings($settings);
        }

        Log::info('Search index created', [
            'index' => $indexName,
            'model' => $modelClass,
        ]);
    }

    /**
     * Get index settings for a model.
     *
     * @return array<string, mixed>
     */
    private function getIndexSettings(string $modelClass): array
    {
        $settings = [];

        switch ($modelClass) {
            case Campaign::class:
                $settings = [
                    'searchableAttributes' => [
                        'title',
                        'description',
                        'organization_name',
                        'tags',
                    ],
                    'filterableAttributes' => [
                        'status',
                        'organization_id',
                        'category',
                        'created_at',
                    ],
                    'sortableAttributes' => [
                        'created_at',
                        'updated_at',
                        'target_amount',
                        'current_amount',
                    ],
                ];
                break;

            case User::class:
                $settings = [
                    'searchableAttributes' => [
                        'name',
                        'email',
                        'department',
                        'job_title',
                    ],
                    'filterableAttributes' => [
                        'status',
                        'role',
                        'department',
                    ],
                    'sortableAttributes' => [
                        'created_at',
                        'name',
                    ],
                ];
                break;

            case Donation::class:
                $settings = [
                    'searchableAttributes' => [
                        'donor_name',
                        'donor_email',
                        'campaign_title',
                        'transaction_id',
                    ],
                    'filterableAttributes' => [
                        'status',
                        'payment_method',
                        'campaign_id',
                        'created_at',
                    ],
                    'sortableAttributes' => [
                        'amount',
                        'created_at',
                    ],
                ];
                break;
        }

        return $settings;
    }

    /**
     * Delete all indexes for a tenant.
     */
    public function deleteTenantIndexes(Organization $organization): void
    {
        $tenantPrefix = $this->getTenantPrefix($organization);

        $indexes = [
            $tenantPrefix . 'campaigns',
            $tenantPrefix . 'users',
            $tenantPrefix . 'donations',
        ];

        foreach ($indexes as $indexName) {
            try {
                $this->client->deleteIndex($indexName);
                Log::info('Search index deleted', ['index' => $indexName]);
            } catch (Exception $e) {
                Log::warning('Failed to delete search index', [
                    'index' => $indexName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reindex all data for a tenant.
     */
    public function reindexTenant(Organization $organization): void
    {
        $organization->run(function () use ($organization): void {
            // Set tenant prefix
            config(['scout.prefix' => $this->getTenantPrefix($organization)]);

            // Reimport all searchable models
            $models = [
                Campaign::class,
                User::class,
                Donation::class,
            ];

            foreach ($models as $model) {
                if (class_exists($model)) {
                    Artisan::call('scout:import', ['model' => $model]);
                }
            }
        });

        Log::info('Tenant reindexed', [
            'organization_id' => $organization->id,
            'prefix' => $this->getTenantPrefix($organization),
        ]);
    }

    /**
     * Get statistics for tenant indexes.
     */
    /**
     * @return array<string, mixed>
     */
    public function getIndexStats(Organization $organization): array
    {
        $tenantPrefix = $this->getTenantPrefix($organization);
        /** @var array<string, mixed> $stats */
        $stats = [];

        $indexes = ['campaigns', 'users', 'donations'];

        foreach ($indexes as $indexName) {
            try {
                $index = $this->client->index($tenantPrefix . $indexName);
                $indexStats = $index->stats();

                $stats[$indexName] = [
                    'numberOfDocuments' => $indexStats['numberOfDocuments'] ?? 0,
                    'isIndexing' => $indexStats['isIndexing'] ?? false,
                ];
            } catch (Exception $e) {
                $stats[$indexName] = [
                    'numberOfDocuments' => 0,
                    'isIndexing' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Get tenant-specific index prefix.
     */
    private function getTenantPrefix(Organization $organization): string
    {
        // Use subdomain as identifier, fallback to ID if subdomain is null
        $identifier = $organization->subdomain ?? 'org_' . $organization->id;

        return 'tenant_' . str_replace('-', '_', $identifier) . '_';
    }
}

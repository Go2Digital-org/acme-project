<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Category\Domain\Model\Category;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Performance optimization trait for tests.
 *
 * Provides methods to:
 * - Create minimal test data efficiently
 * - Batch operations for better performance
 * - Reduce database queries in tests
 * - Optimize factory usage
 */
trait OptimizedTesting
{
    protected static array $sharedTestData = [];

    /**
     * Create a set of shared test data that can be reused across test methods.
     * This reduces database insertions significantly.
     */
    protected function setUpSharedTestData(): void
    {
        if (! empty(static::$sharedTestData)) {
            return;
        }

        DB::transaction(function (): void {
            // Create minimal shared data
            static::$sharedTestData = [
                'user' => User::factory()->create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]),
                'organization' => Organization::factory()->verified()->create([
                    'name' => ['en' => 'Test Organization'],
                    'registration_number' => 'REG-SHARED-' . uniqid(),
                    'tax_id' => 'TAX-SHARED-' . uniqid(),
                    'category' => 'nonprofit',
                ]),
                'category' => Category::factory()->create([
                    'name' => ['en' => 'Test Category'],
                    'slug' => 'test-category-' . uniqid(),
                    'description' => ['en' => 'Test category for shared data'],
                ]),
            ];
        });
    }

    /**
     * Get shared test data to avoid creating duplicate records.
     */
    protected function getSharedTestData(?string $type = null): mixed
    {
        $this->setUpSharedTestData();

        if ($type === null) {
            return static::$sharedTestData;
        }

        return static::$sharedTestData[$type] ?? null;
    }

    /**
     * Create campaigns in batches for better performance.
     *
     * @return Collection<int, \Modules\Campaign\Domain\Model\Campaign>
     */
    protected function createCampaignsBatch(int $count = 10, array $attributes = []): Collection
    {
        $sharedData = $this->getSharedTestData();

        $defaultAttributes = [
            'organization_id' => $sharedData['organization']->id,
            'user_id' => $sharedData['user']->id,
            'category_id' => $sharedData['category']->id,
        ];

        return Campaign::factory()
            ->count($count)
            ->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Create users in batches for better performance.
     *
     * @return Collection<int, \Modules\User\Infrastructure\Laravel\Models\User>
     */
    protected function createUsersBatch(int $count = 10, array $attributes = []): Collection
    {
        $baseEmail = 'user' . uniqid() . '@example.com';

        return User::factory()
            ->sequence(fn ($sequence) => [
                'email' => str_replace('@', $sequence->index . '@', $baseEmail),
            ])
            ->count($count)
            ->create($attributes);
    }

    /**
     * Create organizations in batches for better performance.
     *
     * @return Collection<int, \Modules\Organization\Domain\Model\Organization>
     */
    protected function createOrganizationsBatch(int $count = 10, array $attributes = []): Collection
    {
        return Organization::factory()
            ->sequence(fn ($sequence) => [
                'registration_number' => 'REG-BATCH-' . $sequence->index . '-' . uniqid(),
                'tax_id' => 'TAX-BATCH-' . $sequence->index . '-' . uniqid(),
            ])
            ->count($count)
            ->create($attributes);
    }

    /**
     * Disable query logging for performance-critical tests.
     */
    protected function disableQueryLogging(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Enable query logging and return logged queries.
     */
    protected function enableQueryLogging(): array
    {
        DB::enableQueryLog();

        return DB::getQueryLog();
    }

    /**
     * Assert that a maximum number of queries were executed.
     *
     * @param  callable(): void  $callback
     */
    protected function assertQueryCount(int $maxQueries, callable $callback): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $callback();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $maxQueries,
            $queryCount,
            "Expected maximum {$maxQueries} queries, but {$queryCount} were executed."
        );
    }

    /**
     * Create minimal test data for a specific scenario.
     */
    protected function createMinimalScenario(string $scenario): array
    {
        return match ($scenario) {
            'campaign_with_donations' => $this->createCampaignWithDonationsScenario(),
            'user_with_campaigns' => $this->createUserWithCampaignsScenario(),
            'organization_with_campaigns' => $this->createOrganizationWithCampaignsScenario(),
            default => throw new \InvalidArgumentException("Unknown scenario: {$scenario}"),
        };
    }

    private function createCampaignWithDonationsScenario(): array
    {
        $sharedData = $this->getSharedTestData();

        $campaign = Campaign::factory()->create([
            'organization_id' => $sharedData['organization']->id,
            'user_id' => $sharedData['user']->id,
            'category_id' => $sharedData['category']->id,
            'goal_amount' => 10000,
            'current_amount' => 0,
        ]);

        return [
            'campaign' => $campaign,
            'user' => $sharedData['user'],
            'organization' => $sharedData['organization'],
        ];
    }

    private function createUserWithCampaignsScenario(): array
    {
        $sharedData = $this->getSharedTestData();

        $campaigns = Campaign::factory()
            ->count(3)
            ->create([
                'organization_id' => $sharedData['organization']->id,
                'user_id' => $sharedData['user']->id,
                'category_id' => $sharedData['category']->id,
            ]);

        return [
            'user' => $sharedData['user'],
            'campaigns' => $campaigns,
            'organization' => $sharedData['organization'],
        ];
    }

    private function createOrganizationWithCampaignsScenario(): array
    {
        $sharedData = $this->getSharedTestData();

        $campaigns = Campaign::factory()
            ->count(5)
            ->create([
                'organization_id' => $sharedData['organization']->id,
                'user_id' => $sharedData['user']->id,
                'category_id' => $sharedData['category']->id,
            ]);

        return [
            'organization' => $sharedData['organization'],
            'campaigns' => $campaigns,
            'user' => $sharedData['user'],
        ];
    }

    /**
     * Clear shared test data between test classes.
     */
    protected function clearSharedTestData(): void
    {
        static::$sharedTestData = [];
    }
}

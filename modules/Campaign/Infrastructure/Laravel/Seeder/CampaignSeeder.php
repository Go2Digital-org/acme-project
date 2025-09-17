<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Modules\Campaign\Infrastructure\Laravel\Factory\CampaignFactory;
use Modules\Category\Domain\Model\Category;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Campaign Seeder for Hexagonal Architecture.
 *
 * Creates comprehensive demo campaigns for ACME Corp CSR platform
 */
class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding campaigns for ACME Corp CSR platform...');

        // Ensure we have organizations, users, and categories to associate with campaigns
        $organizations = Organization::all();
        $users = User::all();
        $categories = Category::all();

        if ($organizations->isEmpty()) {
            $this->command->warn('  No organizations found. Please run OrganizationSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('  No users found. Please run UserSeeder first.');

            return;
        }

        if ($categories->isEmpty()) {
            $this->command->warn('  No categories found. Please run CategorySeeder first.');

            return;
        }

        $this->createActiveCampaigns($organizations, $users, $categories);
        $this->createCompletedCampaigns($organizations, $users);
        $this->createExpiredCampaigns($organizations, $users, $categories);
        $this->createDraftCampaigns($organizations, $users, $categories);
        $this->createSpecialCampaigns($organizations, $users, $categories);

        $this->command->info('Successfully seeded campaigns for enterprise CSR platform');
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     */
    private function createActiveCampaigns(Collection $organizations, Collection $users, Collection $categories): void
    {
        $this->command->info('Creating active campaigns...');

        // Education campaigns (active)
        $educationCategory = $categories->where('slug', 'education')->first();
        if ($educationCategory) {
            foreach (range(1, 15) as $i) {
                CampaignFactory::new()
                    ->active()
                    ->education()
                    ->create([
                        'organization_id' => $organizations->random()->id,
                        'user_id' => $users->random()->id,
                    ]);
            }
        }

        // Healthcare campaigns (active)
        $healthCategory = $categories->where('slug', 'health')->first();
        if ($healthCategory) {
            foreach (range(1, 12) as $i) {
                CampaignFactory::new()
                    ->active()
                    ->healthcare()
                    ->create([
                        'organization_id' => $organizations->random()->id,
                        'user_id' => $users->random()->id,
                    ]);
            }
        }

        // Environment campaigns (active)
        $environmentCategory = $categories->where('slug', 'environment')->first();
        if ($environmentCategory) {
            foreach (range(1, 18) as $i) {
                CampaignFactory::new()
                    ->active()
                    ->environment()
                    ->create([
                        'organization_id' => $organizations->random()->id,
                        'user_id' => $users->random()->id,
                    ]);
            }
        }

        // Community campaigns (active)
        $communityCategory = $categories->where('slug', 'community')->first();
        if ($communityCategory) {
            foreach (range(1, 10) as $i) {
                CampaignFactory::new()
                    ->active()
                    ->community()
                    ->create([
                        'organization_id' => $organizations->random()->id,
                        'user_id' => $users->random()->id,
                    ]);
            }
        }

        // General active campaigns with partial progress using random categories
        foreach (range(1, 25) as $i) {
            CampaignFactory::new()
                ->active()
                ->withPartialProgress()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, User>  $users
     */
    private function createCompletedCampaigns(Collection $organizations, Collection $users): void
    {
        $this->command->info('Creating completed campaigns...');

        // Create completed campaigns with different categories
        foreach (range(1, 30) as $i) {
            $factoryMethod = match (random_int(1, 4)) {
                1 => 'education',
                2 => 'healthcare',
                3 => 'environment',
                4 => 'community',
            };

            CampaignFactory::new()
                ->completed()
                ->$factoryMethod()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     */
    private function createExpiredCampaigns(Collection $organizations, Collection $users, Collection $categories): void
    {
        $this->command->info('Creating expired campaigns...');

        foreach (range(1, 25) as $i) {
            CampaignFactory::new()
                ->expired()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     */
    private function createDraftCampaigns(Collection $organizations, Collection $users, Collection $categories): void
    {
        $this->command->info('Creating draft campaigns...');

        foreach (range(1, 15) as $i) {
            CampaignFactory::new()
                ->draft()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     */
    private function createSpecialCampaigns(Collection $organizations, Collection $users, Collection $categories): void
    {
        $this->command->info('Creating special campaigns...');

        // High-value campaigns
        foreach (range(1, 5) as $i) {
            CampaignFactory::new()
                ->active()
                ->highGoal()
                ->nearingGoal()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }

        // Urgent campaigns
        foreach (range(1, 8) as $i) {
            CampaignFactory::new()
                ->urgent()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }

        // Long-term campaigns
        foreach (range(1, 6) as $i) {
            CampaignFactory::new()
                ->longTerm()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                    'category_id' => $categories->random()->id,
                ]);
        }

        // Low-goal community campaigns
        foreach (range(1, 12) as $i) {
            CampaignFactory::new()
                ->active()
                ->lowGoal()
                ->community()
                ->create([
                    'organization_id' => $organizations->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }
    }
}

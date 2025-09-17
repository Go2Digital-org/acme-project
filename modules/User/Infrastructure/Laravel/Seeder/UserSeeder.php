<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\User\Infrastructure\Laravel\Factory\UserFactory;

/**
 * User Seeder for Hexagonal Architecture.
 *
 * Creates ACME Corp employees for the CSR platform
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding ACME Corp employees...');

        $this->createManagers();
        $this->createEmployees();
        $this->createRecentUsers();

        $this->command->info('Successfully seeded ACME Corp employees');
    }

    private function createManagers(): void
    {
        $this->command->info('Creating managers...');

        // Senior managers and team leads
        UserFactory::new()
            ->verified()
            ->manager()
            ->count(12)
            ->create();
    }

    private function createEmployees(): void
    {
        $this->command->info('Creating employees...');

        // Regular employees across the organization
        UserFactory::new()
            ->verified()
            ->employee()
            ->count(80)
            ->create();

        // Some unverified new employees
        UserFactory::new()
            ->unverified()
            ->employee()
            ->count(8)
            ->create();
    }

    private function createRecentUsers(): void
    {
        $this->command->info('Creating recent employees...');

        // Recently joined employees
        UserFactory::new()
            ->verified()
            ->count(15)
            ->create([
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
    }
}

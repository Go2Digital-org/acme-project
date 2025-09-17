<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\Organization\Infrastructure\Laravel\Factory\OrganizationFactory;

/**
 * Organization Seeder for Hexagonal Architecture.
 *
 * Creates comprehensive demo data for ACME Corp CSR platform
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding organizations for ACME Corp CSR platform...');

        // Create diverse organizations across all categories
        $this->createEducationOrganizations();
        $this->createHealthcareOrganizations();
        $this->createEnvironmentalOrganizations();
        $this->createCommunityOrganizations();

        $this->command->info('Successfully seeded organizations for enterprise CSR platform');
    }

    private function createEducationOrganizations(): void
    {
        $this->command->info('Creating education organizations...');

        // Large, well-established education organizations
        OrganizationFactory::new()
            ->education()
            ->verified()
            ->active()
            ->withLogo()
            ->count(8)
            ->create();

        // Medium-sized education initiatives
        OrganizationFactory::new()
            ->education()
            ->verified()
            ->active()
            ->count(7)
            ->create();

        // Smaller, newer education programs
        OrganizationFactory::new()
            ->education()
            ->active()
            ->count(5)
            ->create();
    }

    private function createHealthcareOrganizations(): void
    {
        $this->command->info('Creating healthcare organizations...');

        // Major healthcare foundations
        OrganizationFactory::new()
            ->healthcare()
            ->verified()
            ->active()
            ->withLogo()
            ->count(6)
            ->create();

        // Regional health initiatives
        OrganizationFactory::new()
            ->healthcare()
            ->verified()
            ->active()
            ->count(8)
            ->create();

        // Community health programs
        OrganizationFactory::new()
            ->healthcare()
            ->active()
            ->count(6)
            ->create();
    }

    private function createEnvironmentalOrganizations(): void
    {
        $this->command->info('Creating environmental organizations...');

        // International environmental organizations
        OrganizationFactory::new()
            ->environment()
            ->verified()
            ->active()
            ->withLogo()
            ->count(5)
            ->create();

        // Regional environmental groups
        OrganizationFactory::new()
            ->environment()
            ->verified()
            ->active()
            ->count(7)
            ->create();

        // Local environmental initiatives
        OrganizationFactory::new()
            ->environment()
            ->active()
            ->count(8)
            ->create();
    }

    private function createCommunityOrganizations(): void
    {
        $this->command->info('Creating community development organizations...');

        // Established community foundations
        OrganizationFactory::new()
            ->community()
            ->verified()
            ->active()
            ->withLogo()
            ->count(4)
            ->create();

        // Regional community groups
        OrganizationFactory::new()
            ->community()
            ->verified()
            ->active()
            ->count(6)
            ->create();

        // Grassroots community initiatives
        OrganizationFactory::new()
            ->community()
            ->active()
            ->count(5)
            ->create();
    }
}

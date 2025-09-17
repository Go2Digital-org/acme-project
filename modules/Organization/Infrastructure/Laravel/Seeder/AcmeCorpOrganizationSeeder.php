<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\Organization\Domain\Model\Organization;

/**
 * ACME Corp Organization Seeder for Hexagonal Architecture.
 *
 * Creates the primary ACME Corp organization record with specific data
 * required for the CSR platform functionality.
 */
class AcmeCorpOrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update ACME Corp as the primary organization
        Organization::updateOrCreate(
            ['id' => 1],
            [
                'name' => ['en' => 'ACME Corp', 'nl' => 'ACME Corp', 'fr' => 'ACME Corp'],
                'registration_number' => 'ACME-2024-001',
                'tax_id' => '12-3456789',
                'category' => 'enterprise',
                'type' => 'corporation',
                'website' => 'https://acme-corp.com',
                'email' => 'csr@acme-corp.com',
                'phone' => '+1-555-ACME-CSR',
                'address' => '123 Corporate Boulevard',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'United States',
                'is_verified' => true,
                'is_active' => true,
                'verification_date' => now(),
                'description' => [
                    'en' => 'ACME Corp is an international company with over 20,000 employees committed to Corporate Social Responsibility.',
                    'nl' => 'ACME Corp is een internationaal bedrijf met meer dan 20.000 werknemers die zich inzetten voor maatschappelijk verantwoord ondernemen.',
                    'fr' => 'ACME Corp est une entreprise internationale avec plus de 20 000 employés engagés dans la responsabilité sociale des entreprises.',
                ],
                'mission' => [
                    'en' => 'Empowering employees to make a positive impact on communities through charitable giving and volunteer initiatives.',
                    'nl' => 'Werknemers in staat stellen een positieve impact te maken op gemeenschappen door liefdadigheidsdonaties en vrijwilligersinitiatieven.',
                    'fr' => 'Permettre aux employés d\'avoir un impact positif sur les communautés grâce aux dons caritatifs et aux initiatives de bénévolat.',
                ],
                'logo_url' => '/images/acme-corp-logo.png',
            ]
        );

        $this->command->info('ACME Corp organization created/updated successfully.');
    }
}

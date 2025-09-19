<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Organization\Infrastructure\Filament\Resources\OrganizationResource;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string
    {
        return 'Create New Organization';
    }

    protected function getFormModel(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        parent::mount();

        // Set defaults for all form sections
        $this->form->fill([
            // Multilingual Organization Information
            'name' => [
                'en' => 'ACME Demo Organization',
                'nl' => 'ACME Demo Organisatie',
                'fr' => 'Organisation Démo ACME',
            ],
            'description' => [
                'en' => 'A forward-thinking organization dedicated to making a positive impact in the community through sustainable and innovative programs.',
                'nl' => 'Een vooruitstrevende organisatie die zich inzet voor een positieve impact in de gemeenschap door duurzame en innovatieve programma\'s.',
                'fr' => 'Une organisation avant-gardiste dédiée à avoir un impact positif dans la communauté grâce à des programmes durables et innovants.',
            ],
            'mission' => [
                'en' => 'To empower communities and create lasting social change through collaborative partnerships, innovative solutions, and sustainable development practices.',
                'nl' => 'Gemeenschappen empoweren en blijvende sociale verandering creëren door samenwerking, innovatieve oplossingen en duurzame ontwikkelingspraktijken.',
                'fr' => 'Autonomiser les communautés et créer un changement social durable grâce à des partenariats collaboratifs, des solutions innovantes et des pratiques de développement durable.',
            ],
            // Organization Status
            'status' => 'active',
            'is_active' => true,
            // Contact Information
            'website' => 'https://demo-organization.test',
            'email' => 'admin@demo-organization.test',
            'phone' => '+32 2 123 4567',
            'address' => "Rue de la Loi 200\n1000 Brussels",
            'city' => 'Brussels',
            'state' => 'Brussels-Capital',
            'postal_code' => '1000',
            'country' => 'BE',
            // Tenant Configuration
            'subdomain' => 'demo-' . random_int(1000, 9999),
            // Admin Account
            'admin_name' => 'Demo Admin',
            'admin_email' => 'admin@demo-organization.test',
            'admin_password' => 'admin123!',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default verification status
        if (! isset($data['is_verified'])) {
            $data['is_verified'] = false;
        }

        // Set verification date if marked as verified
        if ($data['is_verified'] && empty($data['verification_date'])) {
            $data['verification_date'] = now();
        }

        // Auto-generate database name from subdomain if not provided
        if (isset($data['subdomain']) && empty($data['database'])) {
            $data['database'] = 'tenant_' . str_replace('-', '_', $data['subdomain']);
        }

        // Store admin account details in tenant_data for use during provisioning
        if (isset($data['admin_name']) && isset($data['admin_email']) && isset($data['admin_password'])) {
            $data['tenant_data'] = [
                'admin' => [
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                ],
            ];

            // Remove from main data as these aren't direct model attributes
            unset($data['admin_name'], $data['admin_email'], $data['admin_password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

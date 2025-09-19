<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\ValueObject\OrganizationCategory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement([
            OrganizationCategory::HEALTHCARE,
            OrganizationCategory::EDUCATION,
            OrganizationCategory::ENERGY,
            OrganizationCategory::NON_PROFIT,
            OrganizationCategory::TECHNOLOGY,
            OrganizationCategory::FINANCE,
            OrganizationCategory::AGRICULTURE,
            OrganizationCategory::OTHER,
        ]);

        $organizationName = $this->generateOrganizationName($category);
        $description = $this->generateDescription($category);
        $mission = $this->generateMission($category);

        return [
            // Use multi-tenancy schema fields with proper array format
            'name' => [
                'en' => $organizationName,
                'nl' => 'Nederlandse ' . fake()->word() . ' Organisatie',
                'fr' => 'Organisation ' . fake()->word() . ' Française',
            ],
            'name_translations' => [
                'en' => $organizationName,
                'nl' => 'Nederlandse ' . fake()->word() . ' Organisatie',
                'fr' => 'Organisation ' . fake()->word() . ' Française',
            ],
            'description' => [
                'en' => $description,
                'nl' => 'Nederlandse beschrijving van deze belangrijke organisatie die zich inzet voor de gemeenschap.',
                'fr' => 'Description française de cette organisation importante qui s\'engage pour la communauté.',
            ],
            'description_translations' => [
                'en' => $description,
                'nl' => 'Nederlandse beschrijving van deze belangrijke organisatie die zich inzet voor de gemeenschap.',
                'fr' => 'Description française de cette organisation importante qui s\'engage pour la communauté.',
            ],
            'mission' => [
                'en' => $mission,
                'nl' => 'Nederlandse missie voor het verbeteren van de samenleving.',
                'fr' => 'Mission française pour améliorer la société.',
            ],
            'mission_translations' => [
                'en' => $mission,
                'nl' => 'Nederlandse missie voor het verbeteren van de samenleving.',
                'fr' => 'Mission française pour améliorer la société.',
            ],
            'logo_url' => fake()->optional(0.3)->imageUrl(300, 300, 'business', true, 'Acme'),
            'website' => fake()->optional(0.8)->url(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'address' => fake()->optional(0.9)->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->optional(0.8)->postcode(),
            'country' => fake()->countryCode(),
            'registration_number' => 'REG-' . uniqid() . '-' . fake()->numerify('####'),
            'tax_id' => 'TAX-' . uniqid() . '-' . fake()->numerify('####'),
            'category' => fake()->randomElement(['nonprofit', 'charity', 'foundation', 'social', 'enterprise']),
            'type' => fake()->randomElement(['foundation', 'charity', 'ngo', 'social_enterprise']),
            'is_active' => true,
            'is_verified' => false,
            'created_at' => fake()->dateTimeBetween('-2 years', '-1 month')->setTime(12, 0, 0)->format('Y-m-d H:i:s'),
            'updated_at' => fn ($attributes) => $attributes['created_at'],
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_verified' => true,
            'is_active' => true,
            'verification_date' => fake()->dateTimeBetween($attributes['created_at'] ?? '-1 year', 'now')->setTime(12, 0, 0)->format('Y-m-d H:i:s'),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_verified' => false,
            'is_active' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function healthcare(): static
    {
        $name = $this->generateOrganizationName(OrganizationCategory::HEALTHCARE);

        return $this->state(fn (array $attributes): array => [
            'name' => [
                'en' => $name,
                'nl' => 'Medische ' . fake()->word() . ' Organisatie',
                'fr' => 'Organisation Médicale ' . fake()->word(),
            ],
            'description' => [
                'en' => $this->generateDescription(OrganizationCategory::HEALTHCARE),
                'nl' => 'Toegewijde organisatie voor het verbeteren van de gezondheidszorg in ondergeserveerde gemeenschappen.',
                'fr' => 'Organisation dédiée à l\'amélioration des soins de santé dans les communautés mal desservies.',
            ],
            'mission' => [
                'en' => 'Providing quality healthcare services to underserved communities and improving health outcomes.',
                'nl' => 'Kwaliteit zorgverlening bieden aan ondergeserveerde gemeenschappen en gezondheidsuitkomsten verbeteren.',
                'fr' => 'Fournir des services de santé de qualité aux communautés mal desservies et améliorer les résultats de santé.',
            ],
        ]);
    }

    public function education(): static
    {
        $name = $this->generateOrganizationName(OrganizationCategory::EDUCATION);

        return $this->state(fn (array $attributes): array => [
            'name' => [
                'en' => $name,
                'nl' => 'Onderwijs ' . fake()->word() . ' Stichting',
                'fr' => 'Fondation Éducative ' . fake()->word(),
            ],
            'description' => [
                'en' => $this->generateDescription(OrganizationCategory::EDUCATION),
                'nl' => 'Toegewijde organisatie voor het uitbreiden van onderwijsmogelijkheden en het verbeteren van leerresultaten.',
                'fr' => 'Organisation dédiée à l\'expansion des opportunités éducatives et à l\'amélioration des résultats d\'apprentissage.',
            ],
            'mission' => [
                'en' => 'Empowering communities through education and creating opportunities for lifelong learning.',
                'nl' => 'Gemeenschappen empoweren door onderwijs en kansen creëren voor levenslang leren.',
                'fr' => 'Autonomiser les communautés par l\'éducation et créer des opportunités d\'apprentissage tout au long de la vie.',
            ],
            'category' => 'education',
        ]);
    }

    public function environment(): static
    {
        $name = $this->generateOrganizationName(OrganizationCategory::ENERGY);

        return $this->state(fn (array $attributes): array => [
            'name' => [
                'en' => $name,
                'nl' => 'Milieu ' . fake()->word() . ' Initiatief',
                'fr' => 'Initiative Environnementale ' . fake()->word(),
            ],
            'description' => [
                'en' => $this->generateDescription(OrganizationCategory::ENERGY),
                'nl' => 'Organisatie gewijd aan het beschermen van ons milieu en het promoten van duurzame ontwikkeling.',
                'fr' => 'Organisation dédiée à la protection de notre environnement et à la promotion du développement durable.',
            ],
            'mission' => [
                'en' => 'Protecting our planet and promoting sustainable practices for future generations.',
                'nl' => 'Onze planeet beschermen en duurzame praktijken promoten voor toekomstige generaties.',
                'fr' => 'Protéger notre planète et promouvoir des pratiques durables pour les générations futures.',
            ],
            'category' => 'environmental',
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => ['en' => '', 'nl' => '', 'fr' => ''], // Empty translations
            'email' => null, // Empty email
            'status' => 'pending', // Pending status for incomplete
        ]);
    }

    public function withLogo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'logo_url' => fake()->imageUrl(300, 300, 'business', true, 'Acme'),
        ]);
    }

    public function community(): static
    {
        $name = $this->generateOrganizationName(OrganizationCategory::NON_PROFIT);

        return $this->state(fn (array $attributes): array => [
            'name' => [
                'en' => $name,
                'nl' => 'Gemeenschap ' . fake()->word() . ' Netwerk',
                'fr' => 'Réseau Communautaire ' . fake()->word(),
            ],
            'description' => [
                'en' => $this->generateDescription(OrganizationCategory::NON_PROFIT),
                'nl' => 'Organisatie die een positieve impact maakt in gemeenschappen door toegewijde dienstverlening.',
                'fr' => 'Organisation ayant un impact positif dans les communautés grâce à un service dédié.',
            ],
            'mission' => [
                'en' => 'Building stronger communities through collaborative programs and sustainable development initiatives.',
                'nl' => 'Sterkere gemeenschappen bouwen door samenwerkingsprogramma\'s en duurzame ontwikkelingsinitiatieven.',
                'fr' => 'Construire des communautés plus fortes grâce à des programmes collaboratifs et des initiatives de développement durable.',
            ],
        ]);
    }

    public function withNullValues(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
            'website' => null,
            'phone' => null,
            'category' => null,
            'type' => null,
            'registration_number' => null,
            'tax_id' => null,
            'slug' => null,
            'verification_date' => null,
            'founded_date' => null,
            'logo_url' => null,
        ]);
    }

    public function withNullDates(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => null,
            'updated_at' => null,
            'verification_date' => null,
            'founded_date' => null,
        ]);
    }

    private function generateOrganizationName(OrganizationCategory $category): string
    {
        $prefixes = [
            OrganizationCategory::HEALTHCARE->value => ['Medical', 'Health', 'Care', 'Wellness', 'Community Health'],
            OrganizationCategory::EDUCATION->value => ['Education', 'Learning', 'Knowledge', 'Academic', 'School'],
            OrganizationCategory::ENERGY->value => ['Green', 'Eco', 'Environmental', 'Nature', 'Earth'],
            OrganizationCategory::NON_PROFIT->value => ['Community', 'Social', 'Local', 'Neighborhood', 'Citizens'],
            OrganizationCategory::TECHNOLOGY->value => ['Tech', 'Digital', 'Innovation', 'Software', 'IT'],
            OrganizationCategory::FINANCE->value => ['Financial', 'Investment', 'Banking', 'Capital', 'Economic'],
        ];

        $suffixes = ['Foundation', 'Organization', 'Initiative', 'Alliance', 'Society', 'Network', 'Center', 'Institute'];

        $prefix = fake()->randomElement($prefixes[$category->value] ?? ['Community', 'Global', 'United']);
        $suffix = fake()->randomElement($suffixes);

        return "{$prefix} {$suffix}";
    }

    private function generateDescription(OrganizationCategory $category): string
    {
        $descriptions = [
            OrganizationCategory::HEALTHCARE->value => [
                'Dedicated to improving healthcare access and outcomes in underserved communities.',
                'Providing comprehensive medical services and health education to those in need.',
                'Working to eliminate health disparities and promote wellness for all.',
            ],
            OrganizationCategory::EDUCATION->value => [
                'Committed to expanding educational opportunities and improving learning outcomes.',
                'Empowering individuals through quality education and skill development programs.',
                'Building stronger communities through innovative educational initiatives.',
            ],
            OrganizationCategory::ENERGY->value => [
                'Protecting our environment and promoting sustainable development practices.',
                'Working to address climate change and preserve natural resources.',
                'Creating a sustainable future through conservation and environmental education.',
            ],
            OrganizationCategory::NON_PROFIT->value => [
                'Making a positive impact in communities through dedicated service and innovative programs.',
                'Committed to creating lasting change and improving lives through our mission-driven work.',
                'Empowering communities through collaborative efforts and sustainable solutions.',
            ],
        ];

        $categoryDescriptions = $descriptions[$category->value] ?? [
            'Making a positive impact in communities through dedicated service and innovative programs.',
            'Committed to creating lasting change and improving lives through our mission-driven work.',
        ];

        return fake()->randomElement($categoryDescriptions);
    }

    private function generateMission(OrganizationCategory $category): string
    {
        $missions = [
            OrganizationCategory::HEALTHCARE->value => [
                'Providing quality healthcare services to underserved communities and improving health outcomes.',
                'Delivering comprehensive medical care and health education to those who need it most.',
                'Working tirelessly to ensure equitable access to healthcare for all.',
            ],
            OrganizationCategory::EDUCATION->value => [
                'Empowering communities through education and creating opportunities for lifelong learning.',
                'Transforming lives through quality education and skill development programs.',
                'Building a brighter future through innovative educational initiatives.',
            ],
            OrganizationCategory::ENERGY->value => [
                'Protecting our planet and promoting sustainable practices for future generations.',
                'Leading the transition to clean energy and environmental sustainability.',
                'Creating a greener world through conservation and renewable energy solutions.',
            ],
            OrganizationCategory::NON_PROFIT->value => [
                'Building stronger communities through collaborative programs and sustainable development initiatives.',
                'Serving communities with compassion and creating lasting positive change.',
                'Empowering individuals and communities to reach their full potential.',
            ],
        ];

        $categoryMissions = $missions[$category->value] ?? [
            'Making a positive impact in communities through dedicated service and innovative programs.',
            'Committed to creating lasting change and improving lives through our mission-driven work.',
        ];

        return fake()->randomElement($categoryMissions);
    }
}

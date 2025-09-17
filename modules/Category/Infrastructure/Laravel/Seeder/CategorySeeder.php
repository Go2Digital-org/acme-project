<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\Category\Infrastructure\Laravel\Factory\CategoryFactory;

/**
 * Category Seeder for Campaign Categories.
 *
 * Creates the standard set of campaign categories with proper translations.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding campaign categories...');

        // Create the core categories that match the current hardcoded ones
        $categories = [
            [
                'name' => [
                    'en' => 'Education',
                    'nl' => 'Onderwijs',
                    'fr' => 'Éducation',
                ],
                'description' => [
                    'en' => 'Supporting educational initiatives, schools, and learning opportunities',
                    'nl' => 'Ondersteuning van onderwijsinitiatieven, scholen en leermogelijkheden',
                    'fr' => 'Soutenir les initiatives éducatives, les écoles et les opportunités d\'apprentissage',
                ],
                'slug' => 'education',
                'color' => '#3b82f6', // Blue
                'icon' => 'academic-cap',
                'sort_order' => 10,
            ],
            [
                'name' => [
                    'en' => 'Health & Medical',
                    'nl' => 'Gezondheid & Medisch',
                    'fr' => 'Santé & Médical',
                ],
                'description' => [
                    'en' => 'Healthcare access, medical equipment, and wellness programs',
                    'nl' => 'Toegang tot gezondheidszorg, medische apparatuur en wellnessprogramma\'s',
                    'fr' => 'Accès aux soins de santé, équipement médical et programmes de bien-être',
                ],
                'slug' => 'health',
                'color' => '#ef4444', // Red
                'icon' => 'heart',
                'sort_order' => 20,
            ],
            [
                'name' => [
                    'en' => 'Environment',
                    'nl' => 'Milieu',
                    'fr' => 'Environnement',
                ],
                'description' => [
                    'en' => 'Environmental protection, sustainability, and climate action projects',
                    'nl' => 'Milieubescherming, duurzaamheid en klimaatactieprojecten',
                    'fr' => 'Protection de l\'environnement, durabilité et projets d\'action climatique',
                ],
                'slug' => 'environment',
                'color' => '#22c55e', // Green
                'icon' => 'globe-alt',
                'sort_order' => 30,
            ],
            [
                'name' => [
                    'en' => 'Community Development',
                    'nl' => 'Gemeenschapsontwikkeling',
                    'fr' => 'Développement Communautaire',
                ],
                'description' => [
                    'en' => 'Building stronger communities through local initiatives and social programs',
                    'nl' => 'Sterke gemeenschappen bouwen door lokale initiatieven en sociale programma\'s',
                    'fr' => 'Construire des communautés plus fortes grâce à des initiatives locales et des programmes sociaux',
                ],
                'slug' => 'community',
                'color' => '#f59e0b', // Amber
                'icon' => 'home',
                'sort_order' => 40,
            ],
            [
                'name' => [
                    'en' => 'Disaster Relief',
                    'nl' => 'Rampenbestrijding',
                    'fr' => 'Secours en Cas de Catastrophe',
                ],
                'description' => [
                    'en' => 'Emergency response and disaster recovery support',
                    'nl' => 'Noodrespons en ondersteuning bij herstel na rampen',
                    'fr' => 'Intervention d\'urgence et soutien à la récupération après sinistre',
                ],
                'slug' => 'disaster_relief',
                'color' => '#dc2626', // Red-600
                'icon' => 'exclamation-triangle',
                'sort_order' => 50,
            ],
            [
                'name' => [
                    'en' => 'Poverty Alleviation',
                    'nl' => 'Armoedebestrijding',
                    'fr' => 'Réduction de la Pauvreté',
                ],
                'description' => [
                    'en' => 'Programs to reduce poverty and improve living conditions',
                    'nl' => 'Programma\'s om armoede te verminderen en levensomstandigheden te verbeteren',
                    'fr' => 'Programmes pour réduire la pauvreté et améliorer les conditions de vie',
                ],
                'slug' => 'poverty',
                'color' => '#92400e', // Amber-800
                'icon' => 'banknotes',
                'sort_order' => 60,
            ],
            [
                'name' => [
                    'en' => 'Animal Welfare',
                    'nl' => 'Dierenwelzijn',
                    'fr' => 'Bien-être Animal',
                ],
                'description' => [
                    'en' => 'Animal protection, rescue, and welfare programs',
                    'nl' => 'Dierenbescherming, redding en welzijnsprogramma\'s',
                    'fr' => 'Protection des animaux, sauvetage et programmes de bien-être',
                ],
                'slug' => 'animal_welfare',
                'color' => '#7c3aed', // Purple
                'icon' => 'sparkles',
                'sort_order' => 70,
            ],
            [
                'name' => [
                    'en' => 'Human Rights',
                    'nl' => 'Mensenrechten',
                    'fr' => 'Droits de l\'Homme',
                ],
                'description' => [
                    'en' => 'Human rights advocacy and social justice initiatives',
                    'nl' => 'Mensenrechtenadvocatie en sociale rechtvaardigheidsinitiatieven',
                    'fr' => 'Plaidoyer pour les droits de l\'homme et initiatives de justice sociale',
                ],
                'slug' => 'human_rights',
                'color' => '#059669', // Emerald-600
                'icon' => 'scale',
                'sort_order' => 80,
            ],
            [
                'name' => [
                    'en' => 'Arts & Culture',
                    'nl' => 'Kunst & Cultuur',
                    'fr' => 'Arts & Culture',
                ],
                'description' => [
                    'en' => 'Supporting artistic expression and cultural preservation',
                    'nl' => 'Ondersteuning van artistieke expressie en cultuurbehoud',
                    'fr' => 'Soutenir l\'expression artistique et la préservation culturelle',
                ],
                'slug' => 'arts_culture',
                'color' => '#be185d', // Pink-700
                'icon' => 'musical-note',
                'sort_order' => 90,
            ],
            [
                'name' => [
                    'en' => 'Sports & Recreation',
                    'nl' => 'Sport & Recreatie',
                    'fr' => 'Sports & Loisirs',
                ],
                'description' => [
                    'en' => 'Sports programs and recreational facilities for communities',
                    'nl' => 'Sportprogramma\'s en recreatieve voorzieningen voor gemeenschappen',
                    'fr' => 'Programmes sportifs et installations récréatives pour les communautés',
                ],
                'slug' => 'sports',
                'color' => '#ea580c', // Orange-600
                'icon' => 'trophy',
                'sort_order' => 100,
            ],
            [
                'name' => [
                    'en' => 'Other',
                    'nl' => 'Anders',
                    'fr' => 'Autre',
                ],
                'description' => [
                    'en' => 'Other charitable causes and social impact initiatives',
                    'nl' => 'Andere liefdadigheidsdoelen en sociale impactinitiatieven',
                    'fr' => 'Autres causes caritatives et initiatives d\'impact social',
                ],
                'slug' => 'other',
                'color' => '#6b7280', // Gray-500
                'icon' => 'tag',
                'sort_order' => 110,
            ],
        ];

        foreach ($categories as $categoryData) {
            CategoryFactory::new()->create($categoryData);
            $this->command->info("  Created category: {$categoryData['name']['en']}");
        }

        $this->command->info('Successfully seeded ' . count($categories) . ' campaign categories');
    }
}

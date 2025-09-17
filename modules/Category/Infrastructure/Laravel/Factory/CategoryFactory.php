<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\ValueObject\CategoryStatus;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            [
                'name' => [
                    'en' => 'Education',
                    'nl' => 'Onderwijs',
                    'fr' => 'Éducation',
                ],
                'description' => [
                    'en' => 'Supporting educational initiatives and learning opportunities',
                    'nl' => 'Ondersteuning van onderwijsinitiatieven en leermogelijkheden',
                    'fr' => 'Soutenir les initiatives éducatives et les opportunités d\'apprentissage',
                ],
                'slug' => 'education',
                'color' => '#3b82f6', // Blue
                'icon' => 'academic-cap',
            ],
            [
                'name' => [
                    'en' => 'Health & Medical',
                    'nl' => 'Gezondheid & Medisch',
                    'fr' => 'Santé & Médical',
                ],
                'description' => [
                    'en' => 'Healthcare access and medical support programs',
                    'nl' => 'Toegang tot gezondheidszorg en medische ondersteuningsprogramma\'s',
                    'fr' => 'Accès aux soins de santé et programmes de soutien médical',
                ],
                'slug' => 'health',
                'color' => '#ef4444', // Red
                'icon' => 'heart',
            ],
            [
                'name' => [
                    'en' => 'Environment',
                    'nl' => 'Milieu',
                    'fr' => 'Environnement',
                ],
                'description' => [
                    'en' => 'Environmental protection and sustainability projects',
                    'nl' => 'Milieubescherming en duurzaamheidsprojecten',
                    'fr' => 'Protection de l\'environnement et projets de durabilité',
                ],
                'slug' => 'environment',
                'color' => '#22c55e', // Green
                'icon' => 'globe-alt',
            ],
            [
                'name' => [
                    'en' => 'Community Development',
                    'nl' => 'Gemeenschapsontwikkeling',
                    'fr' => 'Développement Communautaire',
                ],
                'description' => [
                    'en' => 'Building stronger communities through local initiatives',
                    'nl' => 'Sterke gemeenschappen bouwen door lokale initiatieven',
                    'fr' => 'Construire des communautés plus fortes grâce à des initiatives locales',
                ],
                'slug' => 'community',
                'color' => '#f59e0b', // Amber
                'icon' => 'home',
            ],
        ];

        $category = fake()->randomElement($categories);

        return [
            'name' => $category['name'],
            'description' => $category['description'],
            'slug' => $category['slug'],
            'status' => CategoryStatus::ACTIVE,
            'color' => $category['color'],
            'icon' => $category['icon'],
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function education(): static
    {
        return $this->state([
            'name' => [
                'en' => 'Education',
                'nl' => 'Onderwijs',
                'fr' => 'Éducation',
            ],
            'description' => [
                'en' => 'Supporting educational initiatives and learning opportunities',
                'nl' => 'Ondersteuning van onderwijsinitiatieven en leermogelijkheden',
                'fr' => 'Soutenir les initiatives éducatives et les opportunités d\'apprentissage',
            ],
            'slug' => 'education',
            'color' => '#3b82f6',
            'icon' => 'academic-cap',
            'sort_order' => 10,
        ]);
    }

    public function health(): static
    {
        return $this->state([
            'name' => [
                'en' => 'Health & Medical',
                'nl' => 'Gezondheid & Medisch',
                'fr' => 'Santé & Médical',
            ],
            'description' => [
                'en' => 'Healthcare access and medical support programs',
                'nl' => 'Toegang tot gezondheidszorg en medische ondersteuningsprogramma\'s',
                'fr' => 'Accès aux soins de santé et programmes de soutien médical',
            ],
            'slug' => 'health',
            'color' => '#ef4444',
            'icon' => 'heart',
            'sort_order' => 20,
        ]);
    }

    public function environment(): static
    {
        return $this->state([
            'name' => [
                'en' => 'Environment',
                'nl' => 'Milieu',
                'fr' => 'Environnement',
            ],
            'description' => [
                'en' => 'Environmental protection and sustainability projects',
                'nl' => 'Milieubescherming en duurzaamheidsprojecten',
                'fr' => 'Protection de l\'environnement et projets de durabilité',
            ],
            'slug' => 'environment',
            'color' => '#22c55e',
            'icon' => 'globe-alt',
            'sort_order' => 30,
        ]);
    }

    public function community(): static
    {
        return $this->state([
            'name' => [
                'en' => 'Community Development',
                'nl' => 'Gemeenschapsontwikkeling',
                'fr' => 'Développement Communautaire',
            ],
            'description' => [
                'en' => 'Building stronger communities through local initiatives',
                'nl' => 'Sterke gemeenschappen bouwen door lokale initiatieven',
                'fr' => 'Construire des communautés plus fortes grâce à des initiatives locales',
            ],
            'slug' => 'community',
            'color' => '#f59e0b',
            'icon' => 'home',
            'sort_order' => 40,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => CategoryStatus::INACTIVE,
        ]);
    }
}

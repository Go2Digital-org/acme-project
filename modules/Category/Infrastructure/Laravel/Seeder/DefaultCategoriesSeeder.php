<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Default Categories Seeder for Tenants.
 *
 * Seeds default campaign categories for new tenant databases.
 */
class DefaultCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding default categories...');

        $categories = [
            [
                'name' => json_encode([
                    'en' => 'Education',
                    'nl' => 'Onderwijs',
                    'fr' => 'Éducation',
                ]),
                'slug' => 'education',
                'description' => json_encode([
                    'en' => 'Supporting educational initiatives and scholarships',
                    'nl' => 'Ondersteuning van onderwijsinitiatieven en beurzen',
                    'fr' => 'Soutien aux initiatives éducatives et aux bourses',
                ]),
                'status' => 'active',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Healthcare',
                    'nl' => 'Gezondheidszorg',
                    'fr' => 'Soins de santé',
                ]),
                'slug' => 'healthcare',
                'description' => json_encode([
                    'en' => 'Medical assistance and health-related campaigns',
                    'nl' => 'Medische hulp en gezondheidsgerelateerde campagnes',
                    'fr' => 'Assistance médicale et campagnes liées à la santé',
                ]),
                'status' => 'active',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Environment',
                    'nl' => 'Milieu',
                    'fr' => 'Environnement',
                ]),
                'slug' => 'environment',
                'description' => json_encode([
                    'en' => 'Environmental protection and sustainability projects',
                    'nl' => 'Milieubescherming en duurzaamheidsprojecten',
                    'fr' => 'Protection de l\'environnement et projets de durabilité',
                ]),
                'status' => 'active',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Community',
                    'nl' => 'Gemeenschap',
                    'fr' => 'Communauté',
                ]),
                'slug' => 'community',
                'description' => json_encode([
                    'en' => 'Community development and social welfare',
                    'nl' => 'Gemeenschapsontwikkeling en sociaal welzijn',
                    'fr' => 'Développement communautaire et bien-être social',
                ]),
                'status' => 'active',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Emergency Relief',
                    'nl' => 'Noodhulp',
                    'fr' => 'Secours d\'urgence',
                ]),
                'slug' => 'emergency-relief',
                'description' => json_encode([
                    'en' => 'Disaster response and emergency aid',
                    'nl' => 'Rampenrespons en noodhulp',
                    'fr' => 'Intervention en cas de catastrophe et aide d\'urgence',
                ]),
                'status' => 'active',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Arts & Culture',
                    'nl' => 'Kunst & Cultuur',
                    'fr' => 'Arts et Culture',
                ]),
                'slug' => 'arts-culture',
                'description' => json_encode([
                    'en' => 'Supporting arts, culture, and heritage preservation',
                    'nl' => 'Ondersteuning van kunst, cultuur en erfgoedbehoud',
                    'fr' => 'Soutien aux arts, à la culture et à la préservation du patrimoine',
                ]),
                'status' => 'active',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Sports & Recreation',
                    'nl' => 'Sport & Recreatie',
                    'fr' => 'Sports et Loisirs',
                ]),
                'slug' => 'sports-recreation',
                'description' => json_encode([
                    'en' => 'Sports programs and recreational activities',
                    'nl' => 'Sportprogramma\'s en recreatieve activiteiten',
                    'fr' => 'Programmes sportifs et activités récréatives',
                ]),
                'status' => 'active',
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Animal Welfare',
                    'nl' => 'Dierenwelzijn',
                    'fr' => 'Bien-être animal',
                ]),
                'slug' => 'animal-welfare',
                'description' => json_encode([
                    'en' => 'Animal protection and welfare initiatives',
                    'nl' => 'Dierenbescherming en welzijnsinitiatieven',
                    'fr' => 'Initiatives de protection et de bien-être des animaux',
                ]),
                'status' => 'active',
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Technology',
                    'nl' => 'Technologie',
                    'fr' => 'Technologie',
                ]),
                'slug' => 'technology',
                'description' => json_encode([
                    'en' => 'Technology access and digital literacy programs',
                    'nl' => 'Technologietoegang en digitale geletterdheidsprogramma\'s',
                    'fr' => 'Accès à la technologie et programmes de littératie numérique',
                ]),
                'status' => 'active',
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode([
                    'en' => 'Other',
                    'nl' => 'Overige',
                    'fr' => 'Autre',
                ]),
                'slug' => 'other',
                'description' => json_encode([
                    'en' => 'Other charitable causes and initiatives',
                    'nl' => 'Andere goede doelen en initiatieven',
                    'fr' => 'Autres causes et initiatives caritatives',
                ]),
                'status' => 'active',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Successfully seeded ' . count($categories) . ' default categories');
    }
}

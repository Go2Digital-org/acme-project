<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\Shared\Domain\Model\SocialMedia;

/**
 * Default Social Media Seeder for Tenants.
 *
 * Seeds default social media links for new tenant organizations.
 * These can be customized by each tenant after provisioning.
 */
class DefaultSocialMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Seeding default social media links for tenant...');
        }

        // Default social media links - tenants can update these with their own URLs
        $socialMediaLinks = [
            [
                'platform' => 'facebook',
                'url' => 'https://facebook.com',
                'icon' => null, // Will use default platform icon
                'is_active' => true,
                'order' => 1,
            ],
            [
                'platform' => 'twitter',
                'url' => 'https://twitter.com',
                'icon' => null,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'platform' => 'linkedin',
                'url' => 'https://linkedin.com',
                'icon' => null,
                'is_active' => true,
                'order' => 3,
            ],
            [
                'platform' => 'instagram',
                'url' => 'https://instagram.com',
                'icon' => null,
                'is_active' => true,
                'order' => 4,
            ],
            [
                'platform' => 'youtube',
                'url' => 'https://youtube.com',
                'icon' => null,
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($socialMediaLinks as $link) {
            SocialMedia::firstOrCreate(
                ['platform' => $link['platform']],
                $link
            );
        }

        if ($this->command) {
            $this->command->info('Successfully seeded ' . count($socialMediaLinks) . ' default social media links');
        }
    }
}

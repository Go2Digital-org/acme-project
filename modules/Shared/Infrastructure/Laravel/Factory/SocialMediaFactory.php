<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Domain\Model\SocialMedia;

/**
 * @extends Factory<SocialMedia>
 */
class SocialMediaFactory extends Factory
{
    protected $model = SocialMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use the platforms from the SocialMedia model
        $platforms = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'];
        $platform = fake()->randomElement($platforms);

        return [
            'platform' => $platform,
            'url' => $this->generateUrlForPlatform($platform),
            'icon' => null, // Use default icon from model
            'order' => fake()->numberBetween(1, 10),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function facebook(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => 'facebook',
            'url' => 'https://facebook.com/' . fake()->userName,
        ]);
    }

    public function twitter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => 'twitter',
            'url' => 'https://twitter.com/' . fake()->userName,
        ]);
    }

    public function linkedin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/company/' . fake()->slug(2),
        ]);
    }

    public function instagram(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/' . fake()->userName,
        ]);
    }

    public function youtube(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => 'youtube',
            'url' => 'https://youtube.com/c/' . fake()->slug(2),
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

    public function withCustomIcon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'icon' => 'fa-' . fake()->word . '-custom',
        ]);
    }

    private function generateUrlForPlatform(string $platform): string
    {
        return match ($platform) {
            'facebook' => 'https://facebook.com/' . fake()->userName,
            'twitter' => 'https://twitter.com/' . fake()->userName,
            'linkedin' => 'https://linkedin.com/company/' . fake()->slug(2),
            'instagram' => 'https://instagram.com/' . fake()->userName,
            'youtube' => 'https://youtube.com/c/' . fake()->slug(2),
            default => 'https://' . $platform . '.com/' . fake()->userName,
        };
    }
}

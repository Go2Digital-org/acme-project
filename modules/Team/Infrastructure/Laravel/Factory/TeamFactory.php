<?php

declare(strict_types=1);

namespace Modules\Team\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Organization\Domain\Model\Organization;
use Modules\Team\Domain\Model\Team;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->generateTeamName();
        $slug = Str::slug($name) . '-' . fake()->numberBetween(100, 999);

        return [
            'name' => $name,
            'description' => $this->generateDescription(),
            'slug' => $slug,
            'organization_id' => Organization::factory(),
            'owner_id' => User::factory(),
            'is_active' => true,
            'metadata' => $this->generateMetadata(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn ($attributes) => $attributes['created_at'],
        ];
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

    public function withOwner(User $owner): static
    {
        return $this->state(fn (array $attributes): array => [
            'owner_id' => $owner->id,
            'organization_id' => $owner->organization_id,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organization->id,
        ]);
    }

    public function withDescription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'description' => $this->generateDescription(),
        ]);
    }

    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'description' => null,
        ]);
    }

    public function engineering(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Engineering Team',
            'slug' => 'engineering-' . fake()->numberBetween(100, 999),
            'description' => 'Software engineering and development team focused on building innovative solutions.',
            'metadata' => [
                'department' => 'engineering',
                'skills' => ['php', 'javascript', 'python', 'docker'],
                'focus' => 'development',
            ],
        ]);
    }

    public function marketing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Marketing Team',
            'slug' => 'marketing-' . fake()->numberBetween(100, 999),
            'description' => 'Marketing and outreach team dedicated to promoting our CSR initiatives.',
            'metadata' => [
                'department' => 'marketing',
                'skills' => ['social media', 'content creation', 'analytics'],
                'focus' => 'outreach',
            ],
        ]);
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Sales Team',
            'slug' => 'sales-' . fake()->numberBetween(100, 999),
            'description' => 'Sales team focused on building partnerships for CSR campaigns.',
            'metadata' => [
                'department' => 'sales',
                'skills' => ['negotiation', 'relationship building', 'presentation'],
                'focus' => 'partnerships',
            ],
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    private function generateTeamName(): string
    {
        $prefixes = [
            'Innovation', 'Digital', 'Growth', 'Impact', 'Community', 'Strategic',
            'Creative', 'Development', 'Operations', 'Excellence', 'Quality',
            'Customer Success', 'Product', 'Research', 'Analytics',
        ];

        $suffixes = [
            'Team', 'Squad', 'Unit', 'Division', 'Group', 'Force', 'Hub',
            'Center', 'Initiative', 'Collective',
        ];

        $prefix = fake()->randomElement($prefixes);
        $suffix = fake()->randomElement($suffixes);

        return "{$prefix} {$suffix}";
    }

    private function generateDescription(): string
    {
        $descriptions = [
            'A dedicated team focused on driving organizational change and impact.',
            'Collaborative group working together to achieve strategic objectives.',
            'Cross-functional team committed to delivering exceptional results.',
            'Dynamic team of professionals passionate about making a difference.',
            'Innovative team leveraging diverse skills to create meaningful solutions.',
            'Strategic team aligned with organizational goals and values.',
            'High-performing team dedicated to continuous improvement and excellence.',
            'Agile team focused on delivering value through collaborative efforts.',
        ];

        return fake()->randomElement($descriptions);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateMetadata(): array
    {
        $departments = ['engineering', 'marketing', 'sales', 'hr', 'finance', 'operations'];
        $focusAreas = ['development', 'outreach', 'partnerships', 'innovation', 'efficiency'];

        return [
            'department' => fake()->randomElement($departments),
            'focus' => fake()->randomElement($focusAreas),
            'capacity' => fake()->numberBetween(5, 50),
            'established' => fake()->date(),
            'timezone' => fake()->timezone(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleNames = [
            'admin',
            'manager',
            'campaign-coordinator',
            'donation-manager',
            'content-editor',
            'organization-admin',
            'viewer',
            'moderator',
            'analyst',
            'support-agent',
        ];

        return [
            'name' => fake()->unique()->randomElement($roleNames) . '-' . fake()->numberBetween(1, 999),
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'admin-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'manager-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function campaignManager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'campaign-manager-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function donationManager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'donation-manager-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function contentEditor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'content-editor-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'viewer-' . fake()->numberBetween(1, 999),
        ]);
    }

    public function withApiGuard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guard_name' => 'api',
        ]);
    }

    public function withSanctumGuard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guard_name' => 'sanctum',
        ]);
    }
}
